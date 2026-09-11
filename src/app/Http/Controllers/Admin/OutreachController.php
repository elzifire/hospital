<?php

namespace App\Http\Controllers\Admin;

use App\Broadcasting\BroadcastService;
use App\Broadcasting\PhoneFormat;
use App\Broadcasting\WhatsApp\AntreanKirim;
use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use App\Models\Pnpp;
use App\Models\Satker;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Modul Outreach — riwayat pesan undangan jadwal (rule H-7 & H-1) yang
 * digenerate dari penjadwalan Digital Reminder, plus form kirim pesan
 * manual: target (nama/NIP/NRP/no HP) dari data PNPP, opsional
 * dikaitkan ke penjadwalan milik target tsb.
 */
class OutreachController extends Controller
{
    /**
     * Riwayat pesan outreach — data nyata, dengan ringkasan status,
     * pencarian, filter status/aturan, dan pembatalan.
     */
    public function index(Request $request)
    {
        $q = (string) $request->query('q', '');
        $status = (string) $request->query('status', '');
        $rule = (string) $request->query('rule', '');

        $logs = MessageLog::query()
            ->jenis('outreach')
            ->with('template:id,judul', 'reminder.poli:id,nama')
            ->when($q, fn ($query) => $query->where(
                fn ($sub) => $sub->where('penerima_nama', 'like', "%{$q}%")
                    ->orWhere('penerima_no_hp', 'like', "%{$q}%")
                    ->orWhere('konten', 'like', "%{$q}%")
            ))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($rule, fn ($query) => $query->where('rule', $rule))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $perStatus = MessageLog::jenis('outreach')
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.outreach.index', [
            'logs' => $logs,
            'perStatus' => $perStatus,
            'penerimaUnik' => MessageLog::jenis('outreach')->distinct()->count('pnpp_id'),
            'total' => (int) $perStatus->sum(),
            'filters' => ['q' => $q, 'status' => $status, 'rule' => $rule],
        ]);
    }

    /**
     * Generate pesan outreach (H-7 & H-1) dari penjadwalan aktif —
     * sekaligus menyapu status penjadwalan lewat tanpa kunjungan.
     * Pesan berstatus menunggu, lalu dikirim otomatis oleh scheduler
     * (broadcast:kirim tiap menit) atau tombol "Kirim Sekarang".
     */
    public function generate(Request $request)
    {
        $service = app(BroadcastService::class);

        $ditandai = $service->sweepStatus();
        $hasil = $service->generate('outreach', $request->user());

        $pesan = $hasil['dibuat'].' pesan outreach dibuat, '
            .$hasil['dilewati'].' dilewati (sudah pernah dibuat). '
            .'Pesan menunggu dikirim otomatis tiap menit, atau tekan "Kirim Sekarang".';

        if ($ditandai > 0) {
            $pesan .= " {$ditandai} penjadwalan lewat tanpa kunjungan ditandai tidak datang.";
        }

        return redirect()
            ->route('admin.outreach.index')
            ->with('success', $pesan);
    }

    /**
     * Form kirim pesan manual (GET) — saring pasien PNPP lalu pilih satu
     * atau beberapa pasien sekaligus (termasuk "centang semua" lewat
     * daftar penerima yang nomor WhatsApp-nya valid). Pesan memakai
     * format resmi WhatsApp Business, tanpa isian otomatis.
     */
    public function create(Request $request)
    {
        $q = (string) $request->query('q', '');
        $satkerId = (string) $request->query('satker', '');

        $pnpps = Pnpp::query()
            ->with('satker:id,nama')
            ->when($q, fn ($query) => $query->where(
                fn ($sub) => $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('nip', 'like', "%{$q}%")
                    ->orWhere('no_hp', 'like', "%{$q}%")
            ))
            ->when($satkerId, fn ($query) => $query->where('satker_id', $satkerId))
            ->orderBy('nama')
            ->get(['id', 'nama', 'nip', 'no_hp', 'satker_id']);

        $canKirimIds = $pnpps
            ->filter(fn (Pnpp $p) => PhoneFormat::toWa($p->no_hp) !== null)
            ->pluck('id')
            ->values()
            ->all();

        return view('admin.outreach.manual', [
            'pnpps' => $pnpps,
            'satkers' => Satker::orderBy('nama')->get(['id', 'nama']),
            'filters' => ['q' => $q, 'satker' => $satkerId],
            'canKirimIds' => $canKirimIds,
        ]);
    }

    /**
     * Kirim pesan manual (POST) ke satu atau beberapa pasien: "sekarang"
     * langsung terkirim, atau "jadwalkan" untuk dikirim otomatis oleh
     * sistem pada waktu yang dipilih. Setiap pasien mendapat satu pesan.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'pnpp_ids' => ['required', 'array', 'min:1'],
            'pnpp_ids.*' => ['integer', Rule::exists('pnpps', 'id')],
            'jenis' => ['required', Rule::in(MessageLog::JENIS)],
            'mode' => ['nullable', Rule::in(['sekarang', 'jadwalkan'])],
            'kirim_pada' => ['nullable', 'required_if:mode,jadwalkan', 'date', 'after:now'],
        ]);

        $mode = $data['mode'] ?? 'sekarang';

        // Route digate "manage outreach" — jenis follow up butuh izinnya sendiri.
        if ($data['jenis'] === 'follow_up') {
            abort_unless($request->user()->can('manage follow-up'), 403);
        }

        $pnpps = Pnpp::query()->whereIn('id', $data['pnpp_ids'])->get();

        $logs = $pnpps->map(function (Pnpp $pnpp) use ($request, $data, $mode) {
            $noHp = PhoneFormat::toWa($pnpp->no_hp);

            return MessageLog::create([
                'jenis' => $data['jenis'],
                'rule' => 'manual',
                'pnpp_id' => $pnpp->id,
                'created_by' => $request->user()->id,
                'penerima_nama' => (string) ($pnpp->nama ?? '—'),
                'penerima_no_hp' => $noHp ?? (string) ($pnpp->no_hp ?? ''),
                'konten' => $this->kontenManual(),
                'status' => $noHp === null ? 'gagal' : 'menunggu',
                'error' => $noHp === null ? 'Nomor WhatsApp pasien tidak valid.' : null,
                'kirim_pada' => $mode === 'jadwalkan' && $noHp !== null
                    ? \Carbon\CarbonImmutable::parse($data['kirim_pada'])
                    : null,
                'provider' => (string) config('whatsapp.driver'),
                'meta_template_name' => (string) config('whatsapp.meta.fallback_template'),
                'meta_language' => (string) config('whatsapp.meta.fallback_language', 'en_US'),
                'template_params' => [],
            ]);
        });

        $menunggu = $logs->where('status', 'menunggu');
        $tanpaNomor = $logs->where('status', 'gagal');

        $tujuan = $data['jenis'] === 'follow_up' ? 'admin.follow-up.index' : 'admin.outreach.index';
        $tambahan = $tanpaNomor->isNotEmpty()
            ? $tanpaNomor->count().' pasien tidak bisa dikirimi karena nomor WhatsApp tidak valid.'
            : null;

        if ($mode === 'jadwalkan') {
            $waktu = \Carbon\CarbonImmutable::parse($data['kirim_pada']);

            return redirect()
                ->route($tujuan)
                ->with(
                    $menunggu->isNotEmpty() ? 'success' : 'error',
                    implode(' ', array_filter([
                        $menunggu->isNotEmpty()
                            ? 'Pesan untuk '.$menunggu->count().' pasien dijadwalkan terkirim '.$waktu->translatedFormat('l, d F Y').' pukul '.$waktu->format('H:i').'.'
                            : null,
                        $tambahan,
                    ])) ?: 'Tidak ada pesan yang bisa dijadwalkan.',
                );
        }

        $hasil = $menunggu->isEmpty()
            ? ['terkirim' => 0, 'gagal' => 0, 'dilewati' => 0]
            : app(AntreanKirim::class)->kirimSinkron($menunggu->all());

        $pesan = implode(' ', array_filter([
            ($hasil['terkirim'] ?? 0) > 0 ? 'Pesan terkirim ke '.$hasil['terkirim'].' pasien.' : null,
            ($hasil['gagal'] ?? 0) > 0 ? 'Pesan gagal terkirim untuk '.$hasil['gagal'].' pasien: '.($logs->first(fn ($l) => $l->status === 'gagal')?->error ?? 'kendala di sisi WhatsApp.').' ' : '',
            $tambahan,
        ]));

        return redirect()
            ->route($tujuan)
            ->with(($hasil['terkirim'] ?? 0) > 0 ? 'success' : 'error', $pesan ?: 'Tidak ada pesan yang perlu dikirim.');
    }

    protected function kontenManual(): string
    {
        return 'Pesan resmi RS Bhayangkara Bogor melalui WhatsApp Business.';
    }
}

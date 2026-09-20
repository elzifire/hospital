<?php

namespace App\Http\Controllers\Admin;

use App\Broadcasting\BroadcastService;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Models\Pnpp;
use App\Models\Reminder;
use App\Models\Satker;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Modul Outreach — riwayat pesan undangan jadwal (rule H-7 & H-1) yang
 * digenerate dari penjadwalan Digital Reminder, plus form kirim pesan
 * manual (target PNPP + template + variabel) yang diwarisi dari
 * ManualBroadcastController. Form manual menampilkan semua template aktif
 * (kategori bebas).
 */
class OutreachController extends ManualBroadcastController
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
        $templateId = (string) $request->query('template', '');
        $satkerId = (string) $request->query('satker', '');
        $dari = (string) $request->query('dari', '');
        $sampai = (string) $request->query('sampai', '');

        // Scope bersama: tabel riwayat dan kartu statistik memakai
        // filter yang sama sehingga kartu ikut berubah.
        $scope = fn ($query) => $query
            ->jenis('outreach')
            ->when($q, fn ($sub) => $sub->where(
                fn ($inner) => $inner->where('penerima_nama', 'like', "%{$q}%")
                    ->orWhere('penerima_no_hp', 'like', "%{$q}%")
                    ->orWhere('konten', 'like', "%{$q}%")
            ))
            ->when($status, fn ($sub) => $sub->where('status', $status))
            ->when($rule, fn ($sub) => $sub->where('rule', $rule))
            ->when($templateId, fn ($sub) => $sub->where('message_template_id', $templateId))
            ->when($satkerId, fn ($sub) => $sub->whereHas('pnpp', fn ($p) => $p->where('satker_id', $satkerId)))
            ->when($dari, fn ($sub) => $sub->whereDate('created_at', '>=', $dari))
            ->when($sampai, fn ($sub) => $sub->whereDate('created_at', '<=', $sampai));

        $logs = MessageLog::query()
            ->tap($scope)
            ->with('template:id,judul', 'reminder.poli:id,nama')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $perStatus = MessageLog::query()
            ->tap($scope)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.outreach.index', [
            'logs' => $logs,
            'perStatus' => $perStatus,
            'penerimaUnik' => MessageLog::query()->tap($scope)->distinct()->count('pnpp_id'),
            'total' => (int) $perStatus->sum(),
            'templates' => MessageTemplate::query()
                ->whereIn('id', MessageLog::query()->jenis('outreach')->pluck('message_template_id'))
                ->orderBy('judul')
                ->get(['id', 'judul']),
            'satkers' => Satker::orderBy('nama')->get(['id', 'nama']),
            'filters' => ['q' => $q, 'status' => $status, 'rule' => $rule, 'template' => $templateId, 'satker' => $satkerId, 'dari' => $dari, 'sampai' => $sampai],
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

    protected function jenisManual(): string
    {
        return 'outreach';
    }

    protected function kategoriManual(): ?string
    {
        return null;
    }

    protected function viewManual(): string
    {
        return 'admin.outreach.manual';
    }

    protected function viewEdit(): string
    {
        return 'admin.outreach.edit';
    }

    protected function routeIndex(): string
    {
        return 'admin.outreach.index';
    }
}

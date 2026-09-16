<?php

namespace App\Http\Controllers\Admin;

use App\Broadcasting\BroadcastService;
use App\Broadcasting\PhoneFormat;
use App\Models\MessageLog;
use App\Models\MessageReply;
use App\Models\Pnpp;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Modul Follow Up — riwayat pesan tindak lanjut pasien plus form kirim
 * pesan manual yang diwarisi dari ManualBroadcastController. Aturan
 * generate otomatis (H-1, hari-H, tidak-datang) dinonaktifkan sementara.
 *
 * Form manual menampilkan semua template aktif (kategori bebas). Halaman
 * create menampilkan saran follow up: pasien yang di-outreach hari ini
 * dan belum membalas. Daftar penerima hanya menampilkan PNPP yang belum
 * membalas pesan respon.
 */
class FollowUpController extends ManualBroadcastController
{
    /**
     * Saring kandidat penerima: buang PNPP yang sudah pernah membalas
     * (berdasarkan pnpp_id atau nomor WhatsApp-nya), namun target yang
     * sudah dipilih tetap dipertahankan supaya tidak tersapu saat edit.
     *
     * @param  Collection<int, Pnpp>  $pnpps
     * @param  array<int, int>  $wajibTampil
     * @return Collection<int, Pnpp>
     */
    protected function saringBelumBalas(Collection $pnpps, array $wajibTampil): Collection
    {
        if ($pnpps->isEmpty()) {
            return $pnpps;
        }

        $wajibSet = array_flip(array_map('intval', $wajibTampil));

        $repliedPnppIds = MessageReply::query()
            ->whereNotNull('pnpp_id')
            ->pluck('pnpp_id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();

        $repliedNoHp = MessageReply::query()
            ->whereNotNull('no_hp')
            ->pluck('no_hp')
            ->map(fn ($n) => (string) $n)
            ->flip()
            ->all();

        return $pnpps->filter(function (Pnpp $p) use ($wajibSet, $repliedPnppIds, $repliedNoHp) {
            if (isset($wajibSet[(int) $p->id])) {
                return true;
            }
            if (isset($repliedPnppIds[(int) $p->id])) {
                return false;
            }
            $wa = PhoneFormat::toWa($p->no_hp);

            return $wa === null || ! isset($repliedNoHp[$wa]);
        })->values();
    }

    /**
     * Algoritma saran follow up: pasien yang sudah menerima pesan outreach
     * hari ini (status terkirim) namun belum membalas di hari yang sama
     * disarankan untuk di-follow up — contoh kasus "di-outreach hari ini,
     * belum balas" dari petugas. Balasan dikenali lewat pnpp_id atau nomor
     * WhatsApp pada MessageReply; pasien yang sudah di-follow up hari ini
     * dikecualikan supaya tidak dobel.
     *
     * @return Collection<int, array{pnpp: Pnpp, alasan: string}>
     */
    protected function saranPenerima(Request $request): Collection
    {
        $hariIni = today();

        $logs = MessageLog::query()
            ->jenis('outreach')
            ->where('status', 'terkirim')
            ->whereDate('created_at', $hariIni)
            ->whereNotNull('pnpp_id')
            ->with('pnpp.satker:id,nama')
            ->get()
            ->unique('pnpp_id');

        if ($logs->isEmpty()) {
            return collect();
        }

        $balasById = MessageReply::query()
            ->whereDate('waktu_masuk', $hariIni)
            ->whereNotNull('pnpp_id')
            ->pluck('pnpp_id')
            ->map(fn ($id) => (int) $id)
            ->flip()
            ->all();

        $balasByWa = MessageReply::query()
            ->whereDate('waktu_masuk', $hariIni)
            ->whereNotNull('no_hp')
            ->pluck('no_hp')
            ->map(fn ($n) => (string) $n)
            ->flip()
            ->all();

        $diFollowUpHariIni = MessageLog::query()
            ->jenis('follow_up')
            ->where('status', '!=', 'dibatalkan')
            ->whereDate('created_at', $hariIni)
            ->whereNotNull('pnpp_id')
            ->pluck('pnpp_id')
            ->map(fn ($id) => (int) $id)
            ->flip()
            ->all();

        return $logs
            ->filter(function (MessageLog $log) use ($balasById, $balasByWa, $diFollowUpHariIni) {
                $pnpp = $log->pnpp;

                if ($pnpp === null || isset($diFollowUpHariIni[(int) $pnpp->id])) {
                    return false;
                }

                if (isset($balasById[(int) $pnpp->id])) {
                    return false;
                }

                $wa = PhoneFormat::toWa($pnpp->no_hp);

                return $wa !== null && ! isset($balasByWa[$wa]);
            })
            ->map(fn (MessageLog $log) => [
                'pnpp' => $log->pnpp,
                'alasan' => 'Di-outreach hari ini, belum membalas.',
            ])
            ->values();
    }

    /**
     * Riwayat pesan follow up — data nyata, dengan ringkasan status,
     * pencarian, filter status/aturan (termasuk manual), dan pembatalan.
     */
    public function index(Request $request)
    {
        $q = (string) $request->query('q', '');
        $status = (string) $request->query('status', '');
        $rule = (string) $request->query('rule', '');
        $poliId = $request->user()?->poliId();

        // Akun poli hanya melihat pesan hasil generate dari reminder
        // polinya sendiri.
        $scopePoli = fn ($query) => $query->when(
            $poliId !== null,
            fn ($sub) => $sub->whereHas('reminder', fn ($reminder) => $reminder->where('poli_id', $poliId)),
        );

        $logs = MessageLog::query()
            ->jenis('follow_up')
            ->with('template:id,judul', 'reminder.poli:id,nama')
            ->tap($scopePoli)
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

        $perStatus = MessageLog::jenis('follow_up')
            ->tap($scopePoli)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.follow-up.index', [
            'logs' => $logs,
            'perStatus' => $perStatus,
            'penerimaUnik' => MessageLog::jenis('follow_up')->tap($scopePoli)->distinct()->count('pnpp_id'),
            'total' => (int) $perStatus->sum(),
            'filters' => ['q' => $q, 'status' => $status, 'rule' => $rule],
        ]);
    }

    /**
     * Generate pesan follow up (H-1, hari-H, tidak-datang) dari
     * penjadwalan aktif — sekaligus menyapu status penjadwalan lewat
     * tanpa kunjungan.
     */
    public function generate(Request $request)
    {
        $service = app(BroadcastService::class);
        $poliId = $request->user()?->poliId();

        $ditandai = $service->sweepStatus($poliId);
        $hasil = $service->generate('follow_up', $request->user(), $poliId);

        $pesan = $hasil['dibuat'].' pesan follow up dibuat, '
            .$hasil['dilewati'].' dilewati (sudah pernah dibuat).';

        if ($ditandai > 0) {
            $pesan .= " {$ditandai} penjadwalan lewat tanpa kunjungan ditandai tidak datang.";
        }

        return redirect()
            ->route('admin.follow-up.index')
            ->with('success', $pesan);
    }

    protected function jenisManual(): string
    {
        return 'follow_up';
    }

    protected function kategoriManual(): ?string
    {
        return null;
    }

    protected function viewManual(): string
    {
        return 'admin.follow-up.manual';
    }

    protected function viewEdit(): string
    {
        return 'admin.follow-up.edit';
    }

    protected function routeIndex(): string
    {
        return 'admin.follow-up.index';
    }
}

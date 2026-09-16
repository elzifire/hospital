<?php

namespace App\Http\Controllers\Admin;

use App\Broadcasting\BroadcastService;
use App\Models\MessageLog;
use App\Models\Pnpp;
use App\Models\Reminder;
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

<?php

namespace App\Http\Controllers\Admin;

use App\Broadcasting\BroadcastService;
use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use Illuminate\Http\Request;

/**
 * Modul Outreach — riwayat pesan undangan jadwal (rule H-7 & H-1) yang
 * digenerate dari penjadwalan Digital Reminder (broadcast_rules).
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
     */
    public function generate(Request $request)
    {
        $service = app(BroadcastService::class);

        $ditandai = $service->sweepStatus();
        $hasil = $service->generate('outreach', $request->user());

        $pesan = $hasil['dibuat'].' pesan outreach dibuat, '
            .$hasil['dilewati'].' dilewati (sudah pernah dibuat).';

        if ($ditandai > 0) {
            $pesan .= " {$ditandai} penjadwalan lewat tanpa kunjungan ditandai tidak datang.";
        }

        return redirect()
            ->route('admin.outreach.index')
            ->with('success', $pesan);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Broadcasting\BroadcastService;
use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use Illuminate\Http\Request;

/**
 * Aksi tunggal atas baris riwayat message_logs (dipakai semua modul broadcast).
 */
class BroadcastLogController extends Controller
{
    /**
     * Permission yang dibutuhkan per jenis pesan — route sengaja tidak
     * digate middleware karena izinnya bergantung jenis log yang dibatalkan.
     */
    private const PERMISSION_PER_JENIS = [
        'outreach' => 'manage outreach',
        'follow_up' => 'manage follow-up',
    ];

    /**
     * Batalkan pesan yang masih menunggu dikirim.
     */
    public function batalkan(Request $request, MessageLog $log)
    {
        $permission = self::PERMISSION_PER_JENIS[$log->jenis] ?? null;

        abort_if($permission === null || ! $request->user()->can($permission), 403);

        $jumlah = app(BroadcastService::class)->batalkan([$log->id]);

        return back()->with(
            $jumlah > 0 ? 'success' : 'error',
            $jumlah > 0
                ? 'Pesan ke '.$log->penerima_nama.' dibatalkan.'
                : 'Pesan tidak bisa dibatalkan — status sudah final (terkirim/gagal/dibatalkan).'
        );
    }
}

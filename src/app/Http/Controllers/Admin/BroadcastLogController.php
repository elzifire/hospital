<?php

namespace App\Http\Controllers\Admin;

use App\Broadcasting\BroadcastService;
use App\Broadcasting\WhatsApp\AntreanKirim;
use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use App\Models\MessageReply;
use Illuminate\Http\Request;

/**
 * Aksi atas baris riwayat message_logs (dipakai semua modul broadcast):
 * halaman detail pesan, kirim ulang (gagal), kirim sekarang (antrean
 * menunggu), dan pembatalan.
 */
class BroadcastLogController extends Controller
{
    /**
     * Permission yang dibutuhkan per jenis pesan — route sengaja tidak
     * digate middleware karena izinnya bergantung jenis log terkait.
     */
    private const PERMISSION_PER_JENIS = [
        'outreach' => 'manage outreach',
        'follow_up' => 'manage follow-up',
        'respon' => 'manage respon',
    ];

    /**
     * Maksimum pesan yang dikirim sinkron oleh tombol "Kirim Sekarang".
     */
    private const MAKS_KIRIM_SEKARANG = 10;

    /**
     * Detail satu pesan: isi, status pengiriman, pemetaan template
     * Meta, penjadwalan yang dikaitkan, penerima, dan balasan pasien.
     */
    public function show(Request $request, MessageLog $log)
    {
        $this->otorisasi($request, $log);

        $log->load(
            'template:id,judul,kode,channel,meta_template_name,meta_language',
            'reminder.poli:id,nama',
            'reminder.dokter:id,nama',
            'reminder.pnpp.satker:id,nama',
            'pnpp.satker:id,nama',
            'creator:id,name',
        );

        $balasan = MessageReply::query()
            ->where('no_hp', $log->penerima_no_hp)
            ->orderByDesc('waktu_masuk')
            ->limit(10)
            ->get();

        return view('admin.broadcast.show', [
            'log' => $log,
            'balasan' => $balasan,
        ]);
    }

    /**
     * Kirim ulang pesan yang gagal — dikirim sekarang secara sinkron.
     */
    public function kirimUlang(Request $request, MessageLog $log)
    {
        $this->otorisasi($request, $log);

        if ($log->status !== 'gagal') {
            return back()->with('error', 'Hanya pesan berstatus gagal yang bisa dikirim ulang.');
        }

        $log->update(['status' => 'menunggu', 'error' => null]);

        $hasil = app(AntreanKirim::class)->kirimSinkron([$log]);

        return back()->with(
            ($hasil['terkirim'] ?? 0) > 0 ? 'success' : 'error',
            ($hasil['terkirim'] ?? 0) > 0
                ? 'Pesan ke '.$log->penerima_nama.' berhasil dikirim ulang.'
                : 'Pengiriman ulang gagal: '.($log->refresh()->error ?? 'tidak diketahui.'),
        );
    }

    /**
     * Kirim antrean pesan menunggu sekarang (sinkron, maks 10) — untuk
     * jumlah kecil tanpa menunggu scheduler/worker.
     */
    public function kirimSekarang(Request $request, string $jenis)
    {
        abort_unless(in_array($jenis, MessageLog::JENIS, true), 404);
        abort_unless($request->user()->can(self::PERMISSION_PER_JENIS[$jenis]), 403);

        $logs = MessageLog::query()
            ->jenis($jenis)
            ->where('status', 'menunggu')
            ->orderBy('id')
            ->limit(self::MAKS_KIRIM_SEKARANG)
            ->get();

        if ($logs->isEmpty()) {
            return back()->with('error', 'Tidak ada pesan berstatus menunggu untuk dikirim.');
        }

        $hasil = app(AntreanKirim::class)->kirimSinkron($logs);

        return back()->with(
            'success',
            'Pesan terkirim: '.$hasil['terkirim'].' · gagal: '.$hasil['gagal'].' · tidak diproses: '.$hasil['dilewati'].'.'
        );
    }

    /**
     * Batalkan pesan yang masih menunggu dikirim.
     */
    public function batalkan(Request $request, MessageLog $log)
    {
        $this->otorisasi($request, $log);

        $jumlah = app(BroadcastService::class)->batalkan([$log->id]);

        return back()->with(
            $jumlah > 0 ? 'success' : 'error',
            $jumlah > 0
                ? 'Pesan ke '.$log->penerima_nama.' dibatalkan.'
                : 'Pesan tidak bisa dibatalkan — status sudah final (terkirim/gagal/dibatalkan).'
        );
    }

    protected function otorisasi(Request $request, MessageLog $log): void
    {
        $permission = self::PERMISSION_PER_JENIS[$log->jenis] ?? null;

        abort_if($permission === null || ! $request->user()->can($permission), 403);
    }
}

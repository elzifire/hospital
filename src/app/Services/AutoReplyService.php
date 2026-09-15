<?php

namespace App\Services;

use App\Broadcasting\WhatsApp\AntreanKirim;
use App\Models\AutoReply;
use App\Models\MessageLog;
use App\Models\MessageReply;
use Carbon\CarbonImmutable;

/**
 * Balasan otomatis saat pesan masuk via webhook — memilih jawaban dari
 * bank data auto_replies berdasarkan pencocokan pola (mengandung, sama
 * persis, diawali, diakhiri, atau semua pesan). Di luar jam operasional
 * pesan hanya dicatat — petugas menindaklanjuti setelah jam buka.
 */
class AutoReplyService
{
    public function __construct(protected AntreanKirim $antrean) {}

    public function balasOtomatis(MessageReply $balasan): void
    {
        if (! (bool) config('whatsapp.auto_reply.enabled', true)) {
            return;
        }

        if (! $this->dalamJamOperasional($balasan->waktu_masuk)) {
            return;
        }

        $aturan = $this->cariAturan($balasan->isi_pesan);

        if ($aturan === null) {
            return;
        }

        $log = MessageLog::create([
            'jenis' => 'respon',
            'rule' => 'auto',
            'pnpp_id' => $balasan->pnpp_id,
            'penerima_nama' => $balasan->nama ?? 'Nomor Tak Dikenal',
            'penerima_no_hp' => $balasan->no_hp,
            'konten' => $aturan->isi,
            'status' => 'menunggu',
            'provider' => (string) config('whatsapp.driver'),
            'meta_template_name' => null,
            'template_params' => [],
        ]);

        $this->antrean->kirimSinkron([$log]);
    }

    // ── Internal ───────────────────────────────────────────────

    protected function cariAturan(string $pesan): ?AutoReply
    {
        return AutoReply::query()
            ->aktif()
            ->urut()
            ->get()
            ->first(fn (AutoReply $a) => $a->cocok($pesan));
    }

    protected function dalamJamOperasional(mixed $waktu): bool
    {
        $zona = $this->zona();
        $lokal = $waktu instanceof CarbonImmutable
            ? $waktu->tz($zona)
            : CarbonImmutable::parse($waktu)->tz($zona);

        $jam = (int) $lokal->format('G');
        $buka = (int) str_replace(':', '', (string) config('whatsapp.auto_reply.jam_buka', '08:00'));
        $tutup = (int) str_replace(':', '', (string) config('whatsapp.auto_reply.jam_tutup', '22:00'));

        $diBuka = (int) substr((string) $buka, 0, 2) * 60 + (int) substr((string) $buka, 2, 2);
        $diTutup = (int) substr((string) $tutup, 0, 2) * 60 + (int) substr((string) $tutup, 2, 2);
        $menit = $jam * 60 + (int) $lokal->format('i');

        return $menit >= $diBuka && $menit < $diTutup;
    }

    protected function zona(): string
    {
        return (string) config('whatsapp.auto_reply.timezone', 'Asia/Jakarta');
    }
}

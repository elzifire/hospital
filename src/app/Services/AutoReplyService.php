<?php

namespace App\Services;

use App\Broadcasting\WhatsApp\AntreanKirim;
use App\Models\AutoReply;
use App\Models\MessageLog;
use App\Models\MessageReply;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Balasan otomatis saat pesan masuk via webhook — memilih jawaban dari
 * bank data auto_replies berdasarkan pencocokan pola (mengandung, sama
 * persis, diawali, diakhiri, atau semua pesan). Di luar jam operasional
 * pesan hanya dicatat — petugas menindaklanjuti setelah jam buka.
 *
 * Setiap langkah dicatat ke log (channel default / storage/logs/laravel.log)
 * dengan awalan [auto-reply] agar mudah diketelesuri kalau balasan
 * otomatis tidak terkirim.
 */
class AutoReplyService
{
    public function __construct(protected AntreanKirim $antrean) {}

    public function balasOtomatis(MessageReply $balasan): void
    {
        Log::debug('[auto-reply] mulai proses', $this->konteksBalasan($balasan));

        try {
            if (! (bool) config('whatsapp.auto_reply.enabled', true)) {
                Log::info('[auto-reply] dilewati — fitur nonaktif (whatsapp.auto_reply.enabled=false)', $this->konteksBalasan($balasan));

                return;
            }

            if (! $this->dalamJamOperasional($balasan->waktu_masuk)) {
                Log::info(
                    '[auto-reply] dilewati — di luar jam operasional '
                    .'(jam_buka='.config('whatsapp.auto_reply.jam_buka', '08:00')
                    .', jam_tutup='.config('whatsapp.auto_reply.jam_tutup', '22:00')
                    .', waktu_masuk_zona='.$this->tampilanWaktu($balasan->waktu_masuk).')',
                    $this->konteksBalasan($balasan),
                );

                return;
            }

            $aturan = $this->cariAturan($balasan->isi_pesan);

            if ($aturan === null) {
                Log::info('[auto-reply] tidak ada aturan aktif yang cocok dengan pesan', $this->konteksBalasan($balasan));

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

            Log::info(
                "[auto-reply] aturan dipakai — #{$aturan->id} \"{$aturan->nama}\" → message_logs #{$log->id}",
                $this->konteksBalasan($balasan) + ['aturan_id' => $aturan->id, 'aturan_nama' => $aturan->nama, 'konten' => $aturan->isi, 'message_log_id' => $log->id],
            );

            $hasil = $this->antrean->kirimSinkron([$log]);
            Log::info('[auto-reply] hasil kirim', $this->konteksBalasan($balasan) + ['message_log_id' => $log->id, 'hasil' => $hasil]);
        } catch (\Throwable $e) {
            Log::error('[auto-reply] GAGAL — '.$e->getMessage(), $this->konteksBalasan($balasan) + [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    // ── Internal ───────────────────────────────────────────────

    protected function cariAturan(string $pesan): ?AutoReply
    {
        $semua = AutoReply::query()->aktif()->urut()->get();

        Log::debug('[auto-reply] pencocokan aturan', [
            'pesan' => $pesan,
            'jumlah_aturan_aktif' => $semua->count(),
            'aturan' => $semua->map(fn (AutoReply $a) => [
                'id' => $a->id,
                'nama' => $a->nama,
                'cara_cocok' => $a->cara_cocok,
                'pola' => $a->pola,
            ])->all(),
        ]);

        return $semua->first(fn (AutoReply $a) => $a->cocok($pesan));
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

    protected function tampilanWaktu(mixed $waktu): string
    {
        $zona = $this->zona();
        $lokal = $waktu instanceof CarbonImmutable
            ? $waktu->tz($zona)
            : CarbonImmutable::parse($waktu)->tz($zona);

        return $lokal->format('Y-m-d H:i:s T');
    }

    protected function konteksBalasan(MessageReply $balasan): array
    {
        return [
            'no_hp' => $balasan->no_hp,
            'nama' => $balasan->nama,
            'pesan' => $balasan->isi_pesan,
            'waktu_masuk' => $balasan->waktu_masuk?->toDateTimeString(),
            'pnpp_id' => $balasan->pnpp_id,
            'message_reply_id' => $balasan->id,
        ];
    }
}

<?php

namespace App\Broadcasting;

use App\Models\MessageReply;
use App\Models\Pnpp;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Pemroses event webhook WhatsApp (WAHA) — mengubah balasan masuk pasien
 * menjadi baris message_replies:
 *  - hanya event 'message' yang diproses (event lain cukup di-ack)
 *  - hanya chat pribadi (@c.us) yang bukan pesan kita sendiri (fromMe)
 *  - pengirim dicocokkan ke pasien PNPP lewat normalisasi nomor (PhoneFormat)
 *  - event yang dikirim ulang WAHA diabaikan (dedup no_hp + waktu)
 *
 * Balasan tidak diikat ke baris message_logs tertentu — timeline
 * percakapan digabung per nomor HP di halaman Respon.
 */
class WebhookService
{
    /**
     * @return MessageReply|null null = event diabaikan / duplikat
     */
    public function proses(array $event): ?MessageReply
    {
        if (($event['event'] ?? null) !== 'message') {
            return null;
        }

        $pesan = (array) ($event['payload'] ?? []);
        $dari = (string) ($pesan['from'] ?? '');

        // Hanya balasan masuk dari chat pribadi (bukan group, bukan pesan kita).
        if (($pesan['fromMe'] ?? true) !== false || ! str_contains($dari, '@c.us')) {
            return null;
        }

        $isi = trim((string) ($pesan['body'] ?? ''));
        if ($isi === '') {
            return null;
        }

        $nomorMentah = Str::before($dari, '@');
        $noHp = PhoneFormat::toWa($nomorMentah) ?? $nomorMentah;
        $waktu = $this->waktu($pesan);

        // WAHA mengirim ulang event yang gagal di-ack — lewati duplikat.
        if (MessageReply::query()->where('no_hp', $noHp)->where('waktu_masuk', $waktu)->exists()) {
            return null;
        }

        $pnpp = $this->cariPnpp($noHp);

        return MessageReply::create([
            'pnpp_id' => $pnpp?->id,
            'no_hp' => $noHp,
            'nama' => $pnpp?->nama,
            'isi_pesan' => $isi,
            'waktu_masuk' => $waktu,
            'driver' => 'waha',
            'payload' => $event,
        ]);
    }

    /**
     * Cocokkan nomor balasan ke pasien PNPP terdaftar.
     */
    protected function cariPnpp(string $noHp): ?Pnpp
    {
        return Pnpp::query()
            ->whereNotNull('no_hp')
            ->get()
            ->first(fn (Pnpp $p) => PhoneFormat::toWa($p->no_hp) === $noHp);
    }

    /**
     * Waktu pesan dari payload (unix detik) — fallback waktu sekarang.
     */
    protected function waktu(array $pesan): Carbon
    {
        $ts = $pesan['timestamp'] ?? null;

        if (is_numeric($ts) && (int) $ts > 0) {
            try {
                return Carbon::createFromTimestamp((int) $ts);
            } catch (\Throwable) {
                return now();
            }
        }

        return now();
    }
}

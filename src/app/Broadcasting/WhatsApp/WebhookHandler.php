<?php

namespace App\Broadcasting\WhatsApp;

use App\Broadcasting\PhoneFormat;
use App\Models\MessageLog;
use App\Models\MessageReply;
use App\Models\Pnpp;
use App\Services\AutoReplyService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Menerima notifikasi masuk (pesan/balasan pasien) dari Meta WhatsApp
 * Cloud API lalu menyimpannya ke message_replies untuk modul Respon.
 *
 * Payload Meta berstruktur berlapis:
 *   { object, entry: [{ id, changes: [{ field: 'messages', value: {
 *       messaging_product, metadata: { display_phone_number, phone_number_id },
 *       contacts: [{ profile: { name }, wa_id }],
 *       messages: [{ from, id, timestamp, type, text: { body }, ... }],
 *       statuses: [...] } }] } }
 *
 * Event field 'messages' berisi balasan pasien (disimpan ke
 * MessageReply), sedangkan array statuses berisi status pengiriman pesan
 * keluar (sent/delivered/failed) — dipakai memutakhirkan MessageLog
 * supaya riwayat mencerminkan hasil sesungguhnya dari WhatsApp, bukan
 * sekadar "accepted" saat request diterima Meta.
 */
class WebhookHandler
{
    public function __construct(protected AutoReplyService $autoReply) {}

    /**
     * Zona waktu target untuk waktu masuk (WIB) — dipakai menyimpan
     * waktu_masuk & memutuskan jam operasional balasan otomatis.
     */
    protected function zona(): string
    {
        return (string) config('whatsapp.auto_reply.timezone', 'Asia/Jakarta');
    }

    /**
     * Verifikasi tanda tangan X-Hub-Signature-256 (HMAC-SHA256 atas raw
     * body dengan WA_APP_SECRET). Bila secret belum dikonfigurasi,
     * verifikasi dilewati agar pengembangan lokal tetap berjalan.
     */
    public function validasi(Request $request): bool
    {
        $appSecret = (string) config('whatsapp.webhook.app_secret');

        if ($appSecret === '') {
            Log::warning('WA_APP_SECRET belum dikonfigurasi — verifikasi tanda tangan webhook dilewati.');

            return true;
        }

        $signature = (string) $request->header('X-Hub-Signature-256');
        $harapan = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return $signature !== '' && hash_equals($harapan, $signature);
    }

    /**
     * Parse payload Meta, simpan tiap pesan balasan sebagai MessageReply.
     *
     * @return int jumlah pesan yang berhasil diproses
     */
    public function proses(Request $request): int
    {
        $payload = (array) $request->input();
        $diproses = 0;

        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $perubahan) {
                if (($perubahan['field'] ?? 'messages') !== 'messages') {
                    continue;
                }

                $nilai = (array) ($perubahan['value'] ?? []);

                foreach ((array) ($nilai['messages'] ?? []) as $pesan) {
                    if ($this->simpan($pesan, $nilai)) {
                        $diproses++;
                    }
                }

                foreach ((array) ($nilai['statuses'] ?? []) as $status) {
                    if ($this->catatStatus($status)) {
                        $diproses++;
                    }
                }
            }
        }

        return $diproses;
    }

    /**
     * Catat status pengiriman pesan keluar dari Meta: cocokkan wamid
     * dengan provider_message_id di message_logs, lalu perbarui status
     * sesuai fakta. "failed" menuliskan alasan dari Meta ke kolom error
     * supaya riwayat tidak menyesatkan (HTTP accepted ≠ terkirim).
     *
     * @param  array<string, mixed>  $status
     */
    protected function catatStatus(array $status): bool
    {
        $id = trim((string) ($status['id'] ?? ''));
        $keadaan = strtolower(trim((string) ($status['status'] ?? '')));

        if ($id === '' || ! in_array($keadaan, ['sent', 'delivered', 'read', 'failed'], true)) {
            return false;
        }

        $log = MessageLog::query()->where('provider_message_id', $id)->first();

        if ($log === null) {
            Log::channel('whatsapp')->warning('Webhook status pengiriman tanpa pesan terdaftar', [
                'id' => $id,
                'status' => $keadaan,
                'recipient_id' => $status['recipient_id'] ?? null,
            ]);

            return false;
        }

        Log::channel('whatsapp')->info('Webhook status pengiriman', [
            'id' => $id,
            'log_id' => $log->id,
            'status' => $keadaan,
            'recipient_id' => $status['recipient_id'] ?? null,
            'error' => $keadaan === 'failed' ? $this->galatStatus($status) : null,
        ]);

        if ($keadaan === 'failed') {
            $log->update([
                'status' => 'gagal',
                'error' => $this->galatStatus($status),
            ]);
        } elseif ($keadaan !== 'read' && $log->status !== 'terkirim') {
            // sent/delivered — konfirmasi sesungguhnya dari WhatsApp.
            $log->update(['status' => 'terkirim', 'sent_at' => now()]);
        }

        return true;
    }

    /**
     * Baca alasan kegagalan dari error array Meta.
     *
     * @param  array<string, mixed>  $status
     */
    protected function galatStatus(array $status): string
    {
        $galat = (array) ($status['errors'][0] ?? []);

        return 'WhatsApp menolak pesan ('.$this->bacaStatus($status).'): '
            .(string) ($galat['message'] ?? 'kendala di sisi Meta.')
            .(isset($galat['code']) ? ' (kode '.$galat['code'].')' : '');
    }

    /**
     * Label ringkas status Meta untuk pesan error.
     *
     * @param  array<string, mixed>  $status
     */
    protected function bacaStatus(array $status): string
    {
        return strtoupper((string) ($status['status'] ?? 'failed'));
    }

    /**
     * Simpan satu pesan balasan. Identity no_hp + waktu_masuk mengikuti
     * indeks unik tabel, jadi payload yang dikirim ulang Meta cukup
     * memperbarui data lama (idempoten).
     *
     * @param  array<string, mixed>  $pesan
     * @param  array<string, mixed>  $nilai
     */
    protected function simpan(array $pesan, array $nilai): bool
    {
        $waId = (string) ($pesan['from'] ?? '');
        $isi = $this->bacaIsi($pesan);

        if ($waId === '' || $isi === null) {
            return false;
        }

        $pnpp = $this->cariPnpp($waId);

        $namaProf = $nilai['contacts'][0]['profile']['name'] ?? null;
        $nama = is_string($namaProf) && trim($namaProf) !== ''
            ? $namaProf
            : ($pnpp?->nama ?? null);

        $waktu = ($ts = (int) ($pesan['timestamp'] ?? 0)) > 0
            ? CarbonImmutable::createFromTimestamp($ts, $this->zona())
            : CarbonImmutable::now($this->zona());

        $balasan = MessageReply::updateOrCreate(
            ['no_hp' => $waId, 'waktu_masuk' => $waktu],
            [
                'pnpp_id' => $pnpp?->id,
                'nama' => $nama,
                'isi_pesan' => $isi,
                'driver' => 'meta',
                'payload' => [
                    'pesan' => $pesan,
                    'metadata' => $nilai['metadata'] ?? null,
                ],
            ],
        );

        // Balasan otomatis dari bank data auto_replies (dipilih lewat
        // pencocokan pola) di jam operasional, dikirim segera.
        Log::debug('[webhook] pesan masuk — panggil auto-reply', [
            'no_hp' => $waId,
            'nama' => $nama,
            'isi' => $isi,
            'waktu' => $waktu->format('Y-m-d H:i:s T'),
            'message_reply_id' => $balasan->id,
        ]);

        $this->autoReply->balasOtomatis($balasan);

        return true;
    }

    /**
     * Ambil isi teks pesan. Pesan text tanpa body diabaikan (null);
     * jenis non-teks (image, audio, dst.) disimpan sebagai penanda
     * beserta caption/filename bila ada.
     *
     * @param  array<string, mixed>  $pesan
     */
    protected function bacaIsi(array $pesan): ?string
    {
        $tipe = (string) ($pesan['type'] ?? 'text');

        if ($tipe === 'text') {
            $isi = trim((string) ($pesan['text']['body'] ?? ''));

            return $isi === '' ? null : $isi;
        }

        if ($tipe === 'button' || $tipe === 'interactive') {
            return $this->bacaIsiTombol($tipe, $pesan[$tipe] ?? []);
        }

        $rincian = array_values(array_filter([
            $pesan[$tipe]['caption'] ?? null,
            $pesan[$tipe]['filename'] ?? null,
        ], fn ($v) => filled($v)));

        return '['.$tipe.']'.($rincian !== [] ? ' '.implode(' — ', $rincian) : '');
    }

    /**
     * Ambil label pilihan tombol dari pesan `button` (template quick-reply)
     * maupun `interactive` (button_reply / list_reply). Kembalikan null
     * bila tidak ada label yang bisa dibaca (payload mentah tetap disimpan
     * di kolom `payload` message_replies).
     *
     * @param  array<string, mixed>  $objek  isi `$pesan[$tipe]`
     */
    protected function bacaIsiTombol(string $tipe, array $objek): ?string
    {
        if ($tipe === 'button') {
            $label = trim((string) ($objek['text'] ?? ''));

            return $label !== '' ? $label : null;
        }

        $sub = match ($objek['type'] ?? '') {
            'button_reply', 'list_reply' => (string) ($objek[$objek['type']]['title'] ?? ''),
            default => '',
        };
        $label = trim($sub);

        return $label !== '' ? $label : null;
    }

    /**
     * Cocokkan nomor WhatsApp pengirim dengan data PNPP (format 62xx).
     */
    protected function cariPnpp(string $waId): ?Pnpp
    {
        return Pnpp::query()
            ->whereNotNull('no_hp')
            ->get(['id', 'nama', 'no_hp'])
            ->first(fn (Pnpp $p) => PhoneFormat::toWa($p->no_hp) === $waId);
    }
}

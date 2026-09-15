<?php

namespace App\Broadcasting\WhatsApp;

use App\Broadcasting\PhoneFormat;
use App\Models\MessageLog;
use App\Models\MessageReply;
use App\Models\Pnpp;
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
 * Hanya event field 'messages' yang diproses; statuses/read-receipt
 * diabaikan. Nomor pengirim dinormalisasi ke format 62xx agar bisa
 * dicocokkan dengan data PNPP.
 */
class WebhookHandler
{
    public function __construct(protected AntreanKirim $antrean) {}

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
            }
        }

        return $diproses;
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

        MessageReply::updateOrCreate(
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

        $this->balasOtomatis($waId, $pnpp?->id, $nama, $waktu);

        return true;
    }

    /**
     * Balasan otomatis saat pesan masuk di jam operasional (08:00–22:00
     * WIB default). Di luar jam tersebut pesan hanya dicatat — petugas
     * menindaklanjuti setelah jam buka.
     */
    protected function balasOtomatis(string $waId, ?int $pnppId, ?string $nama, CarbonImmutable $waktuMasuk): void
    {
        if (! (bool) config('whatsapp.auto_reply.enabled', true)) {
            return;
        }

        $zona = $this->zona();
        $lokal = $waktuMasuk->tz($zona);
        $jam = (int) $lokal->format('G');
        $buka = (int) str_replace(':', '', (string) config('whatsapp.auto_reply.jam_buka', '08:00'));
        $tutup = (int) str_replace(':', '', (string) config('whatsapp.auto_reply.jam_tutup', '22:00'));

        // Konversi "HH:MM" → menit-dari-tengah-malam agar mudah dibandingkan.
        $diBuka = (int) substr((string) $buka, 0, 2) * 60 + (int) substr((string) $buka, 2, 2);
        $diTutup = (int) substr((string) $tutup, 0, 2) * 60 + (int) substr((string) $tutup, 2, 2);
        $menit = $jam * 60 + (int) $lokal->format('i');

        if ($menit < $diBuka || $menit >= $diTutup) {
            return;
        }

        $pesan = (string) config('whatsapp.auto_reply.pesan', '');
        if (trim($pesan) === '') {
            return;
        }

        $log = MessageLog::create([
            'jenis' => 'respon',
            'rule' => 'auto',
            'pnpp_id' => $pnppId,
            'penerima_nama' => $nama ?? 'Nomor Tak Dikenal',
            'penerima_no_hp' => $waId,
            'konten' => $pesan,
            'status' => 'menunggu',
            'provider' => (string) config('whatsapp.driver'),
            'meta_template_name' => null,
            'template_params' => [],
        ]);

        // Kirim sinkron — balasan otomatis harus segera sampai.
        $this->antrean->kirimSinkron([$log]);
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

        $rincian = array_values(array_filter([
            $pesan[$tipe]['caption'] ?? null,
            $pesan[$tipe]['filename'] ?? null,
        ], fn ($v) => filled($v)));

        return '['.$tipe.']'.($rincian !== [] ? ' '.implode(' — ', $rincian) : '');
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

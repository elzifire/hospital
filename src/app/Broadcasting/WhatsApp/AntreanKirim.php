<?php

namespace App\Broadcasting\WhatsApp;

use App\Jobs\KirimPesanJob;
use App\Models\MessageLog;
use Illuminate\Support\Collection;

/**
 * Pengatur antrean kirim: memilih pesan berstatus menunggu lalu
 * mengantrekannya ke queue (dipanggil scheduler tiap menit), atau
 * mengirim sinkron untuk tombol "Kirim Sekarang" / kirim ulang /
 * form manual. Jatah per jam/hari dihitung dari pesan yang sudah
 * diproses dalam jendela waktu terkait.
 */
class AntreanKirim
{
    public function __construct(protected WhatsAppSender $sender) {}

    /**
     * Antrekan pesan menunggu ke queue worker — dijalankan command
     * broadcast:kirim (scheduler tiap menit), menghormati jatah
     * per jam/hari dari config whatsapp.rate_limit.
     *
     * @return array{diantrekan: int, alasan: ?string}
     */
    public function antarkan(int $maks = 50, ?string $jenis = null): array
    {
        $ids = MessageLog::query()
            ->where('status', 'menunggu')
            // Pesan manual berjadwal baru diambil setelah waktunya tiba.
            ->where(fn ($query) => $query->whereNull('kirim_pada')->orWhere('kirim_pada', '<=', now()))
            ->when($jenis, fn ($query) => $query->where('jenis', $jenis))
            ->orderBy('id')
            ->limit($this->jatahAntar($maks))
            ->pluck('id');

        if ($ids->isEmpty()) {
            return ['diantrekan' => 0, 'alasan' => 'Tidak ada pesan yang menunggu dikirim.'];
        }

        foreach ($ids as $id) {
            KirimPesanJob::dispatch($id);
        }

        return ['diantrekan' => $ids->count(), 'alasan' => null];
    }

    /**
     * Kirim sekarang secara sinkron (tanpa lewat queue) — dipakai untuk
     * jumlah kecil: tombol "Kirim Sekarang", kirim ulang, dan form
     * kirim manual.
     *
     * @param  Collection<int, mixed>|array  $logs  model MessageLog atau id-nya
     * @return array{terkirim: int, gagal: int, dilewati: int}
     */
    public function kirimSinkron(Collection|array $logs): array
    {
        $ringkas = ['terkirim' => 0, 'gagal' => 0, 'dilewati' => 0];

        foreach (collect($logs) as $item) {
            $log = $item instanceof MessageLog ? $item : MessageLog::find($item);

            if (! $log) {
                $ringkas['dilewati']++;

                continue;
            }

            // Klaim atomik menunggu → mengirim agar tidak terkirim ganda.
            $diklaim = MessageLog::query()
                ->whereKey($log->id)
                ->where('status', 'menunggu')
                ->update(['status' => 'mengirim']);

            if ($diklaim === 0) {
                $ringkas['dilewati']++;

                continue;
            }

            $log->refresh();

            try {
                $hasil = $this->sender->kirim($log);
            } catch (\Throwable $e) {
                $log->update(['status' => 'gagal', 'error' => 'Koneksi ke WhatsApp gagal: '.$e->getMessage()]);
                $ringkas['gagal']++;

                continue;
            }

            if ($hasil->ok) {
                $log->update([
                    'status' => 'terkirim',
                    'sent_at' => now(),
                    'provider' => $hasil->provider,
                    'provider_message_id' => $hasil->messageId,
                    'error' => null,
                ]);
                $ringkas['terkirim']++;
            } else {
                $log->update([
                    'status' => 'gagal',
                    'provider' => $hasil->provider,
                    'error' => $hasil->error,
                ]);
                $ringkas['gagal']++;
            }
        }

        return $ringkas;
    }

    /**
     * Jumlah maksimum yang boleh diantrekan sekarang: sekalian dibatasi
     * permintaan, jatah per jam, dan jatah per hari.
     */
    protected function jatahAntar(int $maks): int
    {
        $diproses = fn ($sejak) => MessageLog::query()
            ->whereIn('status', ['mengirim', 'terkirim', 'gagal'])
            ->where('updated_at', '>=', $sejak)
            ->count();

        $sisaJam = max(0, (int) config('whatsapp.rate_limit.max_per_jam', 20) - $diproses(now()->subHour()));
        $sisaHari = max(0, (int) config('whatsapp.rate_limit.max_per_hari', 200) - $diproses(now()->subDay()));

        return max(0, min($maks, $sisaJam, $sisaHari));
    }
}

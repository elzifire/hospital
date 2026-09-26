<?php

namespace App\Console\Commands;

use App\Support\GoogleCalendarSync;
use Illuminate\Console\Command;

/**
 * Sinkronkan hari libur dari Google Calendar publik ke tabel hari_liburs.
 *
 * Contoh: php artisan hari-libur:google-sync
 *         php artisan hari-libur:google-sync --months=6
 */
class HariLiburGoogleSyncCommand extends Command
{
    protected $signature = 'hari-libur:google-sync {--months= : Berapa bulan ke depan yang ditarik (default dari konfigurasi).}';

    protected $description = 'Sinkronkan hari libur dari Google Calendar (read-only) ke tabel hari_liburs';

    public function handle(GoogleCalendarSync $sync): int
    {
        $bulan = $this->option('months') !== null ? (int) $this->option('months') : 0;
        $hasil = $sync->sync($bulan);

        if ($hasil['error'] !== null) {
            $this->error('Sinkronisasi gagal: '.$hasil['error']);

            return self::FAILURE;
        }

        $this->info(
            "Sinkronisasi selesai: {$hasil['jumlah']} hari libur"
            ." (baru: {$hasil['dibuat']}, diperbarui: {$hasil['diperbarui']}, dihapus: {$hasil['dihapus']})."
        );

        return self::SUCCESS;
    }
}
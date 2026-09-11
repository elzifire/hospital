<?php

namespace App\Console\Commands;

use App\Broadcasting\WhatsApp\AntreanKirim;
use Illuminate\Console\Command;

class KirimBroadcastCommand extends Command
{
    protected $signature = 'broadcast:kirim
        {--jumlah=50 : Maksimum pesan yang diantrekan per eksekusi}
        {--jenis= : Batasi jenis pesan (outreach|follow_up)}';

    protected $description = 'Antrekan pesan berstatus menunggu ke queue pengiriman WhatsApp (dijalankan scheduler tiap menit)';

    public function handle(AntreanKirim $antrean): int
    {
        $hasil = $antrean->antarkan(
            (int) $this->option('jumlah'),
            $this->option('jenis') ?: null,
        );

        if (($hasil['diantrekan'] ?? 0) > 0) {
            $this->info($hasil['diantrekan'].' pesan masuk antrean pengiriman WhatsApp.');
        } else {
            $this->info($hasil['alasan'] ?? 'Tidak ada pesan yang menunggu dikirim.');
        }

        return self::SUCCESS;
    }
}

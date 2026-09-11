<?php

namespace App\Console\Commands;

use App\Broadcasting\BroadcastService;
use Illuminate\Console\Command;

class GenerateBroadcastCommand extends Command
{
    protected $signature = 'broadcast:generate
        {--jenis= : Batasi jenis pesan (outreach|follow_up; kosong = keduanya)}';

    protected $description = 'Sapu status penjadwalan lalu generate pesan outreach/follow up dari jadwal aktif';

    public function handle(BroadcastService $service): int
    {
        $jenis = $this->option('jenis');

        if ($jenis !== null && ! in_array($jenis, ['outreach', 'follow_up'], true)) {
            $this->error('Jenis harus outreach atau follow_up.');

            return self::FAILURE;
        }

        $ditandai = $service->sweepStatus();

        foreach ($jenis !== null ? [$jenis] : ['outreach', 'follow_up'] as $target) {
            $hasil = $service->generate($target);

            $this->info(ucfirst(str_replace('_', ' ', $target)).": {$hasil['dibuat']} dibuat, {$hasil['dilewati']} dilewati.");
        }

        if ($ditandai > 0) {
            $this->info("{$ditandai} penjadwalan lewat tanpa kunjungan ditandai tidak datang.");
        }

        $this->info('Pesan berstatus menunggu dikirim oleh broadcast:kirim (scheduler tiap menit).');

        return self::SUCCESS;
    }
}

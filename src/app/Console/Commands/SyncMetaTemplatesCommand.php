<?php

namespace App\Console\Commands;

use App\Broadcasting\WhatsApp\MetaTemplateSync;
use Illuminate\Console\Command;

/**
 * Sinkronkan template pesan dari Meta WhatsApp ke message_templates.
 *
 * Contoh: php artisan meta-templates:sync
 *         php artisan meta-templates:sync --status=APPROVED
 */
class SyncMetaTemplatesCommand extends Command
{
    protected $signature = 'meta-templates:sync {--status= : Filter status Meta (APPROVED, PENDING, REJECTED, dst.).}';

    protected $description = 'Sinkronkan template pesan dari Meta WhatsApp ke tabel message_templates';

    public function handle(MetaTemplateSync $sync): int
    {
        $status = $this->option('status') ? strtoupper((string) $this->option('status')) : null;

        $hasil = $sync->sync($status);

        if ($hasil['error'] !== null) {
            $this->error('Sinkronisasi gagal: '.$hasil['error']);

            return self::FAILURE;
        }

        $this->info(
            "Sinkronisasi selesai: {$hasil['jumlah']} template dari Meta"
            ." (baru: {$hasil['dibuat']}, diperbarui: {$hasil['diperbarui']})."
        );

        return self::SUCCESS;
    }
}

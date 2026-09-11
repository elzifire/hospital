<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah status "mengirim" (sedang diproses worker) ke enum status
     * message_logs supaya alur antrean → pengiriman terlihat jelas di
     * UI: menunggu (Dalam Proses) → mengirim (Sedang Dikirim) →
     * terkirim / gagal.
     */
    protected const STATUS = ['menunggu', 'mengirim', 'terkirim', 'gagal', 'dibatalkan'];

    protected const STATUS_LAMA = ['menunggu', 'terkirim', 'gagal', 'dibatalkan'];

    public function up(): void
    {
        $this->ubahEnum(self::STATUS);
    }

    public function down(): void
    {
        $this->ubahEnum(self::STATUS_LAMA);
    }

    protected function ubahEnum(array $status): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            $daftar = "'".implode("','", $status)."'";

            DB::statement('ALTER TABLE message_logs DROP CONSTRAINT IF EXISTS message_logs_status_check');
            DB::statement("ALTER TABLE message_logs ADD CONSTRAINT message_logs_status_check CHECK (status IN ({$daftar}))");

            return;
        }

        Schema::table('message_logs', function (Blueprint $table) use ($status) {
            $table->enum('status', $status)->default('menunggu')->change();
        });
    }
};

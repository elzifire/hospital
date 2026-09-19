<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah status "jadwal_ulang" (kunjungan dijadwalkan ulang) ke enum
     * status reminders. Baris lama hasil reschedule ditandai jadwal_ulang
     * (riwayat), baris baru dibuat dengan status terjadwal supaya pesan
     * undangan baru tetap ter-generate (dedup keyed id reminder).
     */
    protected const STATUS = ['terjadwal', 'selesai', 'tidak_datang', 'dibatalkan', 'jadwal_ulang'];

    protected const STATUS_LAMA = ['terjadwal', 'selesai', 'tidak_datang', 'dibatalkan'];

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

            DB::statement('ALTER TABLE reminders DROP CONSTRAINT IF EXISTS reminders_status_check');
            DB::statement("ALTER TABLE reminders ADD CONSTRAINT reminders_status_check CHECK (status IN ({$daftar}))");

            return;
        }

        Schema::table('reminders', function (Blueprint $table) use ($status) {
            $table->enum('status', $status)->default('terjadwal')->change();
        });
    }
};

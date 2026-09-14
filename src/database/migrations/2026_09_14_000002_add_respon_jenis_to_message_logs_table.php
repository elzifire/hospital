<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jenis pesan "respon" = balasan langsung petugas ke pasien dari
     * modul Respon (teks bebas, dikirim sinkron). Ditambahkan ke enum
     * jenis message_logs supaya riwayat balasan masuk ke outbox yang
     * sama tanpa tercampur ke modul follow-up/outreach.
     */
    protected const JENIS = ['outreach', 'follow_up', 'respon'];

    protected const JENIS_LAMA = ['outreach', 'follow_up'];

    public function up(): void
    {
        $this->ubahEnum(self::JENIS);
    }

    public function down(): void
    {
        $this->ubahEnum(self::JENIS_LAMA);
    }

    protected function ubahEnum(array $jenis): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            $daftar = "'".implode("','", $jenis)."'";

            DB::statement('ALTER TABLE message_logs DROP CONSTRAINT IF EXISTS message_logs_jenis_check');
            DB::statement("ALTER TABLE message_logs ADD CONSTRAINT message_logs_jenis_check CHECK (jenis IN ({$daftar}))");

            return;
        }

        Schema::table('message_logs', function (Blueprint $table) use ($jenis) {
            $table->enum('jenis', $jenis)->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Soft delete pada PNPP menyisakan baris di tabel. Agar NIP / No. BPJS
     * milik pasien yang dihapus (deleted_at IS NOT NULL) bisa dipakai lagi,
     * index unik diganti menjadi parsial: hanya mengunci baris aktif.
     */
    public function up(): void
    {
        Schema::table('pnpps', function (Blueprint $table) {
            $table->dropUnique(['nip']);
            $table->dropUnique(['no_bpjs']);
        });

        DB::statement('CREATE UNIQUE INDEX pnpps_nip_unique_not_deleted ON pnpps (nip) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX pnpps_no_bpjs_unique_not_deleted ON pnpps (no_bpjs) WHERE deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS pnpps_no_bpjs_unique_not_deleted');
        DB::statement('DROP INDEX IF EXISTS pnpps_nip_unique_not_deleted');

        Schema::table('pnpps', function (Blueprint $table) {
            $table->unique('nip');
            $table->unique('no_bpjs');
        });
    }
};

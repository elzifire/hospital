<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jam layanan poli (buka & tutup). Tetap nullable: poli yang kosong
     * dianggap buka 24 jam — dipakai sebagai panduan saat pasien memilih
     * jam kunjungan pada pendaftaran PNPP.
     */
    public function up(): void
    {
        Schema::table('polis', function (Blueprint $table) {
            $table->time('jam_buka')->nullable();
            $table->time('jam_tutup')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('polis', function (Blueprint $table) {
            $table->dropColumn(['jam_buka', 'jam_tutup']);
        });
    }
};
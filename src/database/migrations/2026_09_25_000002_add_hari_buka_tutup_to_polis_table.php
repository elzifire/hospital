<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hari buka poli sebagai daftar per hari (checklist). Semua kolom
     * bernilai false = poli buka setiap hari; true = poli buka pada hari itu
     * (boleh tak berurutan, mis. Senin & Rabu). Dipakai pasien untuk memilih
     * tanggal kunjungan pada pendaftaran PNPP.
     */
    public function up(): void
    {
        Schema::table('polis', function (Blueprint $table) {
            foreach (['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'] as $hari) {
                $table->boolean('hari_'.$hari)->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('polis', function (Blueprint $table) {
            foreach (['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'] as $hari) {
                $table->dropColumn('hari_'.$hari);
            }
        });
    }
};
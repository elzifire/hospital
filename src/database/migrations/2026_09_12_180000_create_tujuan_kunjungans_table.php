<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tujuan kunjungan dipisah ke tabel sendiri supaya daftar pilihannya
     * bisa terus bertambah (ditambah via seeder/admin) tanpa mengubah struktur.
     * "Yang lain: ..." tetap tampung di kolom register_pnpp.tujuan_lainnya.
     */
    public function up(): void
    {
        Schema::create('tujuan_kunjungans', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->timestamps();
        });

        // Pivot: poli tujuan (boleh pilih banyak).
        Schema::create('register_pnpp_poli', function (Blueprint $table) {
            $table->id();
            $table->foreignId('register_pnpp_id')->constrained('register_pnpp')->cascadeOnDelete();
            $table->foreignId('poli_id')->constrained('polis')->cascadeOnDelete();
            $table->unique(['register_pnpp_id', 'poli_id']);
        });

        // Pivot: tujuan kunjungan (boleh pilih banyak).
        Schema::create('register_pnpp_tujuan_kunjungan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('register_pnpp_id')->constrained('register_pnpp')->cascadeOnDelete();
            $table->foreignId('tujuan_kunjungan_id')->constrained('tujuan_kunjungans')->cascadeOnDelete();
            $table->unique(['register_pnpp_id', 'tujuan_kunjungan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('register_pnpp_tujuan_kunjungan');
        Schema::dropIfExists('register_pnpp_poli');
        Schema::dropIfExists('tujuan_kunjungans');
    }
};

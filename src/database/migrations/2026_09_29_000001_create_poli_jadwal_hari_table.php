<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jadwal layanan poli per hari — jam buka/tutup bisa BERBEDA tiap hari
     * (mis. Senin 08:00–12:00, Selasa 13:00–16:00). Tabel ini menimpa pola
     * lama (jam_buka/jam_tutup + hari_* boolean di polis): selama poli punya
     * baris di sini, baris inilah yang dipakai untuk cek hari/jam layanan.
     *
     * Data lama tidak diubah — poli tanpa baris tetap memakai kolom lama
     * (fallback penuh), sehingga migrasi ini additif dan tidak merusak
     * data yang sudah ada.
     */
    public function up(): void
    {
        Schema::create('poli_jadwal_hari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poli_id')->constrained('polis')->cascadeOnDelete();
            $table->string('hari', 10);
            $table->boolean('buka')->default(true);
            $table->time('jam_buka')->nullable();
            $table->time('jam_tutup')->nullable();
            $table->timestamps();

            $table->unique(['poli_id', 'hari']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poli_jadwal_hari');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel hari libur (tabel terpisah dari polis). Setiap baris punya flag
     * sumber: 'manual' (input petugas) atau 'google_calendar' (hasil sinkron
     * dari Google Calendar). event_id dipakai agar sinkronasi berulang tidak
     * menciptakan duplikat. poli_id nullable: null = berlaku untuk semua poli,
     * terisi = khusus poli tersebut.
     */
    public function up(): void
    {
        Schema::create('hari_liburs', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('nama');
            $table->unsignedBigInteger('poli_id')->nullable();
            $table->foreign('poli_id')->references('id')->on('polis')->nullOnDelete();
            $table->string('sumber', 20)->default('manual'); // manual | google_calendar
            $table->string('event_id', 191)->nullable()->unique();
            $table->timestamps();
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hari_liburs');
    }
};
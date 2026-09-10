<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penjadwalan kunjungan pasien — inti modul Digital Reminder.
     * Murni data jadwal (tanpa pesan); 1 pasien bisa dijadwalkan ke
     * beberapa poli = beberapa baris. Pesan outreach/follow up
     * digenerate dari tabel ini (lihat broadcast_rules).
     */
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnpp_id')->constrained('pnpps')->cascadeOnDelete();
            $table->foreignId('poli_id')->constrained('polis')->cascadeOnDelete();
            $table->foreignId('dokter_id')->nullable()->constrained('dokters')->nullOnDelete();
            $table->date('tanggal');
            $table->time('jam');
            $table->boolean('home_visit')->default(false);
            $table->enum('status', ['terjadwal', 'selesai', 'tidak_datang', 'dibatalkan'])->default('terjadwal');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->index('tanggal');
            $table->index('status');
            $table->index('pnpp_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};

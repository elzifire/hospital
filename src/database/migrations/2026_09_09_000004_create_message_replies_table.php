<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Balasan pesan WhatsApp dari pasien yang masuk via webhook
     * (modul Respon). Tidak diikat ke message_logs — timeline
     * percakapan digabung per nomor HP (ResponController::show).
     */
    public function up(): void
    {
        Schema::create('message_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnpp_id')->nullable()->constrained('pnpps')->nullOnDelete();
            $table->string('no_hp');
            $table->string('nama')->nullable();
            $table->text('isi_pesan');
            $table->timestamp('waktu_masuk');
            $table->string('driver')->nullable();
            $table->jsonb('payload')->nullable();
            $table->timestamps();

            // WAHA mengirim ulang event yang gagal di-ack — dedup no_hp + waktu.
            $table->unique(['no_hp', 'waktu_masuk']);
            $table->index('waktu_masuk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_replies');
    }
};

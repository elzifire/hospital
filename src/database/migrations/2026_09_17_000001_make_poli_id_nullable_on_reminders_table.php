<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penjadwalan home visit tidak wajib memiliki poli (kunjungan ke
     * rumah pasien) — poli_id boleh kosong. Untuk akun poli, poli
     * diturunkan otomatis ke polinya sendiri saat form dikosongkan.
     */
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->foreignId('poli_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->foreignId('poli_id')->nullable(false)->change();
        });
    }
};

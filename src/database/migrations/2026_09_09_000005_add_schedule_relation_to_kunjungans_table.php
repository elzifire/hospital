<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kunjungan nyata kini terhubung ke penjadwalan (reminders):
     *  - reminder_id unik → satu penjadwalan maksimal satu realisasi kunjungan
     *  - poli_id → "1 pasien bisa ke beberapa poli" (diprefill dari reminder)
     */
    public function up(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->foreignId('reminder_id')->nullable()->unique()->constrained('reminders')->nullOnDelete();
            $table->foreignId('poli_id')->nullable()->constrained('polis')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reminder_id');
            $table->dropConstrainedForeignId('poli_id');
        });
    }
};

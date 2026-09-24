<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pendaftaran PNPP kini disetujui per poli tujuan (bukan satu status
     * global) dan saat disetujui datanya otomatis menjadi penjadwalan
     * Digital Reminder. Perubahan struktur:
     *  1. pnpps.nik            — kolom NIK baru supaya pendaftar bisa
     *                            dicocokkan (dan ditandai) lewat NIK.
     *  2. register_pnpp_poli   — approved_at/approved_by menandai poli
     *                            mana saja yang sudah disetujui.
     *  3. reminders.register_pnpp_id — jejak sumber reminder yang dibuat
     *                            otomatis dari pendaftaran (dipakai saat
     *                            membatalkan persetujuan / melacak duplikat).
     */
    public function up(): void
    {
        Schema::table('pnpps', function (Blueprint $table) {
            $table->string('nik', 16)->nullable()->after('nip');
            $table->index('nik');
        });

        Schema::table('register_pnpp_poli', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('poli_id');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->foreignId('register_pnpp_id')->nullable()->after('pnpp_id')
                ->constrained('register_pnpp')->nullOnDelete();
            $table->index('poli_id');
        });
    }

    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropIndex(['poli_id']);
            $table->dropConstrainedForeignId('register_pnpp_id');
            $table->dropColumn('register_pnpp_id');
        });

        Schema::table('register_pnpp_poli', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_at', 'approved_by']);
        });

        Schema::table('pnpps', function (Blueprint $table) {
            $table->dropIndex(['nik']);
            $table->dropColumn('nik');
        });
    }
};
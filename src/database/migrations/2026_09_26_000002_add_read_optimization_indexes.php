<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambah index untuk mempercepat pembacaan data (read path):
     * filter, pencarian, dan laporan yang sering memakai kolom-kolom ini.
     */
    public function up(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->index('penerima_no_hp');
            $table->index('created_at');
            $table->index('message_template_id');
        });

        Schema::table('kunjungans', function (Blueprint $table) {
            $table->index('tanggal_kunjungan');
            $table->index('pnpp_id');
            $table->index('poli_id');
            $table->index(['pnpp_id', 'tanggal_kunjungan']); // komposit, untuk riwayat pasien
        });

        Schema::table('pnpps', function (Blueprint $table) {
            $table->index('satker_id');
            $table->index('no_hp');
        });

        Schema::table('message_replies', function (Blueprint $table) {
            $table->index('read_at');
            $table->index('pnpp_id');
        });

        Schema::table('message_templates', fn (Blueprint $table) => $table->index('is_active'));
        Schema::table('respon_manuals', fn (Blueprint $table) => $table->index('sumber'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->dropIndex(['penerima_no_hp']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['message_template_id']);
        });

        Schema::table('kunjungans', function (Blueprint $table) {
            $table->dropIndex(['tanggal_kunjungan']);
            $table->dropIndex(['pnpp_id']);
            $table->dropIndex(['poli_id']);
            $table->dropIndex(['pnpp_id', 'tanggal_kunjungan']);
        });

        Schema::table('pnpps', function (Blueprint $table) {
            $table->dropIndex(['satker_id']);
            $table->dropIndex(['no_hp']);
        });

        Schema::table('message_replies', function (Blueprint $table) {
            $table->dropIndex(['read_at']);
            $table->dropIndex(['pnpp_id']);
        });

        Schema::table('message_templates', fn (Blueprint $table) => $table->dropIndex(['is_active']));
        Schema::table('respon_manuals', fn (Blueprint $table) => $table->dropIndex(['sumber']));
    }
};
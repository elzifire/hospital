<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Waktu jadwal kirim pesan manual (mode "jadwalkan"). Selama masih
     * bernilai di masa depan, pesan menunggu tidak diambil AntreanKirim;
     * begitu waktunya tiba, scheduler broadcast:kirim yang berjalan tiap
     * menit langsung mengirim. NULL berarti kirim sesegera mungkin.
     */
    public function up(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->timestamp('kirim_pada')->nullable()->after('error');
        });
    }

    public function down(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->dropColumn('kirim_pada');
        });
    }
};
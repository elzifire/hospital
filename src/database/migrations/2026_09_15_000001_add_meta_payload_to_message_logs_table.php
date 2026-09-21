<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payload jenis pesan non-teks dari modul Respon: media (image/audio/
     * video/document) yang disimpan ke storage lokal lalu diunggah ke
     * WABA saat kirim, atau pesan interactive CTA-URL. Disimpan benih
     * JSON agar pengiriman (MetaSender) & render timeline bisa membaca
     * tanpa menebak dari teks.
     */
    public function up(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->json('meta_payload')->nullable()->after('template_params');
        });
    }

    public function down(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->dropColumn('meta_payload');
        });
    }
};
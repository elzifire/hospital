<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foto/gambar sampul template pesan WhatsApp — dipakai sebagai komponen
     * HEADER (type image) saat mengirim lewat Meta Cloud API.
     */
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->string('image_url')->nullable()->after('deskripsi');
        });
    }

    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropColumn('image_url');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache media id No WhatsApp untuk header gambar template. Media yang
 * diunggah via Media Upload API berlaku 30 hari; id disimpan supaya
 * tidak perlu unggah ulang setiap kirim.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->string('meta_media_id')->nullable()->after('image_url');
            $table->timestamp('meta_media_at')->nullable()->after('meta_media_id');
        });
    }

    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropColumn(['meta_media_id', 'meta_media_at']);
        });
    }
};

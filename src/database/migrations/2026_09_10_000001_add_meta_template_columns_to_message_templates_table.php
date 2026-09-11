<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pemetaan template internal ke template Meta (WhatsApp Cloud API):
     * pesan inisiasi bisnis wajib memakai template yang sudah approved.
     * meta_param_tokens = urutan token internal yang menjadi parameter
     * posisi {{1}}, {{2}}, … di template Meta.
     */
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->string('meta_template_name')->nullable()->after('channel');
            $table->string('meta_language', 10)->nullable()->after('meta_template_name');
            $table->json('meta_param_tokens')->nullable()->after('meta_language');
        });
    }

    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropColumn(['meta_template_name', 'meta_language', 'meta_param_tokens']);
        });
    }
};

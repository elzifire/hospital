<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom pengiriman nyata ke Meta Cloud API. Snapshot pemetaan
     * template Meta (nama & bahasa) + parameter ter-render disimpan
     * saat pesan dibuat agar pengiriman tidak bergantung template yang
     * berubah setelahnya; provider_message_id dipakai callback status
     * Meta untuk mencocokkan balasan ke baris pesan.
     */
    public function up(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('error');
            $table->string('provider_message_id')->nullable()->after('provider');
            $table->string('meta_template_name')->nullable()->after('provider_message_id');
            $table->string('meta_language', 10)->nullable()->after('meta_template_name');
            $table->json('template_params')->nullable()->after('meta_language');
            $table->index('provider_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->dropIndex(['provider_message_id']);
            $table->dropColumn(['provider', 'provider_message_id', 'meta_template_name', 'meta_language', 'template_params']);
        });
    }
};

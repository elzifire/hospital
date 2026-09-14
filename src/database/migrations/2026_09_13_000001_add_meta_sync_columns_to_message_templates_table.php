<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom sinkronisasi template Meta WhatsApp — hasil GET
     * /{business_account_id}/message_templates disimpan ke message_templates.
     */
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->string('meta_template_id', 64)->nullable()->unique()->after('id');
            $table->string('meta_status', 32)->nullable()->index()->after('meta_language');
            $table->string('meta_category', 32)->nullable()->after('meta_status');
            $table->json('meta_components')->nullable()->after('meta_category');
            $table->timestamp('meta_updated_at')->nullable()->after('meta_components');
            $table->timestamp('last_synced_at')->nullable()->after('meta_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropUnique(['meta_template_id']);
            $table->dropIndex(['meta_status']);
            $table->dropColumn([
                'meta_template_id',
                'meta_status',
                'meta_category',
                'meta_components',
                'meta_updated_at',
                'last_synced_at',
            ]);
        });
    }
};

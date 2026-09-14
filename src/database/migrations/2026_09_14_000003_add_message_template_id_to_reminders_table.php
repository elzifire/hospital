<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Template pesan yang dipilih di form penjadwalan (opsional) —
     * menggantikan template default rule saat generate outreach/follow up.
     * Null = ikuti template dari aturan modul.
     */
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->foreignId('message_template_id')
                ->nullable()
                ->after('dokter_id')
                ->constrained('message_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('message_template_id');
        });
    }
};

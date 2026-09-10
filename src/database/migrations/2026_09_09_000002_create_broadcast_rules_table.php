<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aturan generate pesan broadcast per jenis & jarak hari dari
     * tanggal jadwal (H-7, H-1, hari-H, tidak datang). Template
     * dipilih per rule lewat modul Setting (tab Aturan Pesan).
     */
    public function up(): void
    {
        Schema::create('broadcast_rules', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis', ['outreach', 'follow_up']);
            $table->string('rule');
            $table->foreignId('message_template_id')->nullable()->constrained('message_templates')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['jenis', 'rule']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_rules');
    }
};

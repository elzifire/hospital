<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Outbox pesan broadcast — diisi oleh BroadcastService::generate()
     * dari penjadwalan (reminders) sesuai aturan (broadcast_rules).
     *
     * Pengiriman nyata ke WhatsApp belum aktif (menunggu pihak ketiga):
     * pesan hasil generate berstatus "menunggu" sampai infrastruktur
     * kirim dibangun kembali (kolom sent_at/error disiapkan untuk itu).
     */
    public function up(): void
    {
        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis', ['outreach', 'follow_up']);
            $table->string('rule')->nullable();
            $table->foreignId('reminder_id')->nullable()->constrained('reminders')->nullOnDelete();
            $table->foreignId('message_template_id')->nullable()->constrained('message_templates')->nullOnDelete();
            $table->foreignId('pnpp_id')->nullable()->constrained('pnpps')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('penerima_nama');
            $table->string('penerima_no_hp');
            $table->text('konten');
            $table->enum('status', ['menunggu', 'terkirim', 'gagal', 'dibatalkan'])->default('menunggu');
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            // Satu reminder hanya dapat satu pesan per jenis + rule (dedup generate).
            $table->unique(['jenis', 'rule', 'reminder_id']);
            $table->index(['jenis', 'status']);
            $table->index('reminder_id');
            $table->index('pnpp_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_logs');
    }
};

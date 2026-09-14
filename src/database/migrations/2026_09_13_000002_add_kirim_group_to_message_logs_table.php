<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengelompokkan kirim manual outreach: satu sesi "kirim pesan"
     * menghasilkan satu grup pesan (uuid). Grup ini menjadi unit edit —
     * petugas bisa mengubah template, variabel, dan target sebelum
     * pesan dikirim (status masih "menunggu").
     *
     * NULL untuk pesan dari generate otomatis (rule H-7/H-1/dst).
     */
    public function up(): void
    {
        Schema::table('message_logs', function (Blueprint $table): void {
            $table->uuid('kirim_group')->nullable()->after('created_by');
            $table->index('kirim_group');
        });
    }

    public function down(): void
    {
        Schema::table('message_logs', function (Blueprint $table): void {
            $table->dropIndex(['kirim_group']);
            $table->dropColumn('kirim_group');
        });
    }
};

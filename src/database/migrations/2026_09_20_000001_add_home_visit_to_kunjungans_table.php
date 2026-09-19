<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kunjungan kini bisa bertipe home visit (kunjungan ke rumah,
     * tanpa poli). Penanda eksplisit agar baris tanpa poli tidak
     * tertukar dengan catatan kosong.
     */
    public function up(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->boolean('home_visit')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->dropColumn('home_visit');
        });
    }
};

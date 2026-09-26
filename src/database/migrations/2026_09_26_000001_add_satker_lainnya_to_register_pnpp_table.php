<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('register_pnpp', function (Blueprint $table) {
            // Nama satker yang diketik manual namun tidak cocok dengan data
            // master — disimpan apa adanya, padanannya adalah satker cadangan
            // "Satker Lainnya" (satker_id). Tidak membuat baris master baru.
            $table->string('satker_lainnya', 255)->nullable()->after('satker_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('register_pnpp', function (Blueprint $table) {
            $table->dropColumn('satker_lainnya');
        });
    }
};
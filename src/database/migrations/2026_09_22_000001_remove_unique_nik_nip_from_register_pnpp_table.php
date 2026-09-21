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
            // Pendaftaran ini bersifat reservasi: calon boleh mendaftar lebih
            // dari sekali, sehingga NIK dan NIP tidak harus unik.
            $table->dropUnique(['nik']);
            $table->dropUnique(['nip']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('register_pnpp', function (Blueprint $table) {
            $table->unique('nik');
            $table->unique('nip');
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Variabel kustom per reminder — nilai yang diisi manual di form
     * digital reminder (misal nama dokter custom) menimpa auto-fill
     * saat generate pesan. Kosong = ikuti auto-fill dari meta jadwal.
     */
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->json('vars_kustom')
                ->nullable()
                ->after('catatan');
        });
    }

    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn('vars_kustom');
        });
    }
};

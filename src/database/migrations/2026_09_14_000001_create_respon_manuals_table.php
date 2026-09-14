<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data balasan pasien yang dicatat manual / diimpor dari Excel/CSV
     * (modul Respon). Dipisah dari message_replies (balasan webhook Meta)
     * agar tidak tercampur — kolomnya khusus: nama, nrp/nip, no_hp, satker,
     * dan isi (lihat ResponManual & tab "Data Respon").
     */
    public function up(): void
    {
        Schema::create('respon_manuals', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->nullable();
            $table->string('nrp_nip')->nullable();
            $table->string('no_hp');
            $table->string('satker')->nullable();
            $table->text('isi');
            $table->timestamp('waktu');
            $table->string('sumber')->default('manual'); // manual | import
            $table->timestamps();

            $table->index('no_hp');
            $table->index('waktu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respon_manuals');
    }
};

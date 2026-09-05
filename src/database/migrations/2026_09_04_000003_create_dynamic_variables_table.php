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
        Schema::create('dynamic_variables', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique(); // e.g. {nama}
            $table->string('nama');           // e.g. Nama Lengkap PNPP
            $table->string('sumber_data')->nullable(); // e.g. pnpps.nama
            $table->string('contoh')->nullable();      // e.g. Bripka Joko Susanto
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dynamic_variables');
    }
};

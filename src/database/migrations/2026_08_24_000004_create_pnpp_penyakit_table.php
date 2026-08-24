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
        Schema::create('pnpp_penyakit', function (Blueprint $table) {
            $table->foreignId('pnpp_id')->constrained('pnpps')->cascadeOnDelete();
            $table->foreignId('penyakit_kronis_id')->constrained('penyakit_kronis')->cascadeOnDelete();
            $table->string('keterangan')->nullable();

            $table->primary(['pnpp_id', 'penyakit_kronis_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pnpp_penyakit');
    }
};

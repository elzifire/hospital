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
        Schema::create('pnpps', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('nip')->unique()->nullable();
            $table->string('no_bpjs')->unique()->nullable();
            $table->foreignId('satker_id')->nullable()->constrained('satkers')->nullOnDelete();
            $table->string('no_hp')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pnpps');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_replies', function (Blueprint $table) {
            $table->id();
            $table->string('kategori', 60)->default('umum');
            $table->string('nama', 120);
            $table->string('cara_cocok', 20)->default('mengandung');
            $table->string('pola', 255)->nullable();
            $table->text('isi');
            $table->boolean('aktif')->default(true);
            $table->unsignedSmallInteger('prioritas')->default(0);
            $table->timestamps();

            $table->index(['aktif', 'prioritas']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_replies');
    }
};

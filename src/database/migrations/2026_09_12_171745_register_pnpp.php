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
        Schema::create('register_pnpp', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 255);
            $table->string('nik', 16)->nullable()->unique();
            $table->string('nip', 50)->nullable()->unique();
            $table->string('jabatan', 255);
            // Referensi satker dari tabel satkers. Satker baru yang belum ada
            // boleh ditambahkan manual lewat form publik (dibuat otomatis).
            $table->foreignId('satker_id')->nullable()->constrained('satkers')->nullOnDelete();
            $table->string('unit', 255)->nullable();
            $table->string('ttl', 255);          // tempat, tanggal lahir
            $table->text('alamat');
            $table->string('no_hp', 20);
            $table->date('rencana_tanggal_kunjungan');
            $table->string('rencana_jam_kunjungan', 5); // format H:i
            $table->text('tujuan_lainnya')->nullable(); // "Yang lain: ..." (opsional)
            $table->string('status', 20)->default('belum disetujui');
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('register_pnpp');
    }
};

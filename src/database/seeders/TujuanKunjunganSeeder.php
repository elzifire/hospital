<?php

namespace Database\Seeders;

use App\Models\TujuanKunjungan;
use Illuminate\Database\Seeder;

class TujuanKunjunganSeeder extends Seeder
{
    /**
     * Daftar pilihan tujuan kunjungan default. Aman dijalankan berulang;
     * bisa ditambah opsi baru tanpa mengubah struktur tabel.
     */
    public function run(): void
    {
        $opsi = [
            'Konsultasi dokter',
            'Kontrol',
            'Pemeriksaan kesehatan',
            'Pemeriksaan laboratorium',
            'Pemeriksaan radiologi',
            'Pengambilan obat',
            'Medical check-up',
        ];

        foreach ($opsi as $nama) {
            TujuanKunjungan::firstOrCreate(['nama' => $nama]);
        }
    }
}

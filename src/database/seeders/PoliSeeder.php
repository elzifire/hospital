<?php

namespace Database\Seeders;

use App\Models\Poli;
use Illuminate\Database\Seeder;

class PoliSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $polis = [
            [
                'kode' => 'IGD',
                'nama' => 'Instalasi Gawat Darurat',
            ],
            [
                'kode' => 'UMUM',
                'nama' => 'Poli Umum',
            ],
            [
                'kode' => 'GIGI',
                'nama' => 'Poli Gigi',
            ],
            [
                'kode' => 'Spesialis Penyakit Dalam',
                'nama' => 'Poli Penyakit Dalam',
            ],
            [
                'kode' => 'ANAK',
                'nama' => 'Poli Anak',
            ],
            [
                'kode' => 'Kebidanan dan Kandungan',
                'nama' => 'Poli Kebidanan dan Kandungan',
            ],
            [
                'kode' => 'THT',
                'nama' => 'Poli Telinga Hidung Tenggorokan',
            ],
            [
                'kode' => 'Endodonsi',
                'nama' => 'Poli Endodonsi',
            ],
            [
                'kode' => 'Bedah Mulut',
                'nama' => 'Poli Bedah Mulut',
            ],
            [
                'kode' => 'Farmasi',
                'nama' => 'Farmasi',
            ],
            [
                'kode' => 'Laboratorium',
                'nama' => 'Laboratorium',
            ],
            [
                'kode' => 'Radiologi',
                'nama' => 'Radiologi',
            ],
            [
                'kode' => 'MCU',
                'nama' => 'Medical Check Up',
            ],
        ];

        foreach ($polis as $data) {
            Poli::firstOrCreate(['kode' => $data['kode']], $data);
        }
    }
}

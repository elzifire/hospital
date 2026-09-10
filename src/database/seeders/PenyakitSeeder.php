<?php

namespace Database\Seeders;

use App\Models\PenyakitKronis;
use Illuminate\Database\Seeder;

class PenyakitSeeder extends Seeder
{
    /**
     * Seed data tambahan ke tabel penyakit kronis.
     */
    public function run(): void
    {
        $penyakits = [
            ['kode' => 'DM2CVD',  'nama' => 'DM TYPE 2 & CVD'],
            ['kode' => 'DMCKD',   'nama' => 'DM + CKD'],
            ['kode' => 'FIB9',    'nama' => 'FRAKTUR FIBULA9'],
            ['kode' => 'JANTUNG', 'nama' => 'JANTUNG'],
            ['kode' => 'SKZ',     'nama' => 'SKRIZOFRENIA'],
            ['kode' => 'ANX',     'nama' => 'ANXIETY DISORDER'],
            ['kode' => 'POCCVA',  'nama' => 'POST OP CRANIOTOMY EC CVA/STROKE'],
            ['kode' => 'IVDD',    'nama' => 'INTERVERTEBRAL DISC DISORDER UNSPECIFIED'],
            ['kode' => 'PSTROKE', 'nama' => 'POST STROKE'],
        ];

        foreach ($penyakits as $data) {
            PenyakitKronis::firstOrCreate(['kode' => $data['kode']], $data);
        }
    }
}

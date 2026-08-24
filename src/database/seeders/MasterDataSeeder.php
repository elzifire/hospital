<?php

namespace Database\Seeders;

use App\Models\Kunjungan;
use App\Models\PenyakitKronis;
use App\Models\Pnpp;
use App\Models\Satker;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Seed data master: satker, penyakit kronis, PNPP, dan riwayat kunjungan.
     */
    public function run(): void
    {
        $satkers = [
            ['kode' => 'DINKES', 'nama' => 'Dinas Kesehatan'],
            ['kode' => 'BPJS',   'nama' => 'BPJS Kesehatan'],
            ['kode' => 'KEMKES', 'nama' => 'Kementerian Kesehatan'],
            ['kode' => 'DINDIK', 'nama' => 'Dinas Pendidikan'],
        ];
        foreach ($satkers as $data) {
            Satker::firstOrCreate(['kode' => $data['kode']], $data);
        }

        $penyakits = [
            ['kode' => 'HTN',  'nama' => 'Hipertensi'],
            ['kode' => 'DM',   'nama' => 'Diabetes Melitus'],
            ['kode' => 'ASMA', 'nama' => 'Asma'],
            ['kode' => 'JTG',  'nama' => 'Jantung Koroner'],
            ['kode' => 'GU',   'nama' => 'Asam Urat'],
        ];
        foreach ($penyakits as $data) {
            PenyakitKronis::firstOrCreate(['kode' => $data['kode']], $data);
        }

        $dinkes  = Satker::where('kode', 'DINKES')->first();
        $bpjs    = Satker::where('kode', 'BPJS')->first();
        $kemkes  = Satker::where('kode', 'KEMKES')->first();

        $hipertensi = PenyakitKronis::where('kode', 'HTN')->first();
        $dm         = PenyakitKronis::where('kode', 'DM')->first();
        $asma       = PenyakitKronis::where('kode', 'ASMA')->first();
        $jantung    = PenyakitKronis::where('kode', 'JTG')->first();

        $pnpps = [
            [
                'nama' => 'Budi Santoso', 'nip' => '198501012010011001', 'no_bpjs' => '0001234567890',
                'satker_id' => $dinkes?->id, 'no_hp' => '081234567890',
                'tanggal_lahir' => '1985-01-01', 'jenis_kelamin' => 'L',
                'penyakit' => [$hipertensi?->id, $dm?->id],
                'kunjungan' => [
                    ['tanggal_kunjungan' => '2026-08-01', 'keluhan' => 'Pusing berulang', 'diagnosa' => 'Hipertensi derajat 1'],
                    ['tanggal_kunjungan' => '2026-08-20', 'keluhan' => 'Kontrol rutin', 'diagnosa' => 'Stabil, lanjut obat'],
                ],
            ],
            [
                'nama' => 'Siti Aminah', 'nip' => '199003152015122002', 'no_bpjs' => '0001234567891',
                'satker_id' => $bpjs?->id, 'no_hp' => '081298765432',
                'tanggal_lahir' => '1990-03-15', 'jenis_kelamin' => 'P',
                'penyakit' => [$asma?->id],
                'kunjungan' => [
                    ['tanggal_kunjungan' => '2026-07-12', 'keluhan' => 'Sesak napas', 'diagnosa' => 'Asma eksaserbasi'],
                ],
            ],
            [
                'nama' => 'Agus Wijaya', 'nip' => '197812302008121003', 'no_bpjs' => '0001234567892',
                'satker_id' => $kemkes?->id, 'no_hp' => '082112345678',
                'tanggal_lahir' => '1978-12-30', 'jenis_kelamin' => 'L',
                'penyakit' => [$jantung?->id, $hipertensi?->id],
                'kunjungan' => [
                    ['tanggal_kunjungan' => '2026-08-25', 'keluhan' => 'Nyeri dada', 'diagnosa' => 'Angina pektoris'],
                    ['tanggal_kunjungan' => '2026-08-10', 'keluhan' => 'Kontrol jantung', 'diagnosa' => 'Stabil'],
                ],
            ],
        ];

        foreach ($pnpps as $data) {
            $penyakit  = $data['penyakit'];
            $kunjungan = $data['kunjungan'];
            unset($data['penyakit'], $data['kunjungan']);

            $pnpp = Pnpp::firstOrCreate(['nip' => $data['nip']], $data);
            $pnpp->penyakit()->sync(array_filter($penyakit));

            if ($pnpp->kunjungans()->doesntExist()) {
                foreach ($kunjungan as $k) {
                    $pnpp->kunjungans()->create($k);
                }
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Dokter;
use App\Models\Jadwal;
use App\Models\Kunjungan;
use App\Models\PenyakitKronis;
use App\Models\Pnpp;
use App\Models\Poli;
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
                'satker_id' => $dinkes?->id, 'satuan_kerja' => 'Dinas Kesehatan',
                'status_kepegawaian' => 'Anggota Polri', 'pangkat' => 'Bripka', 'jabatan' => 'Bintara',
                'bagian' => 'Bagian Umum', 'email' => 'budi@contoh.id', 'alamat' => 'Jl. Merdeka No. 1',
                'status_aktif' => 'aktif', 'no_hp' => '081234567890',
                'tanggal_lahir' => '1985-01-01', 'jenis_kelamin' => 'L',
                'penyakit' => [$hipertensi?->id, $dm?->id],
                'kunjungan' => [
                    ['tanggal_kunjungan' => '2026-08-01', 'keluhan' => 'Pusing berulang', 'diagnosa' => 'Hipertensi derajat 1'],
                    ['tanggal_kunjungan' => '2026-08-20', 'keluhan' => 'Kontrol rutin', 'diagnosa' => 'Stabil, lanjut obat'],
                ],
            ],
            [
                'nama' => 'Siti Aminah', 'nip' => '199003152015122002', 'no_bpjs' => '0001234567891',
                'satker_id' => $bpjs?->id, 'satuan_kerja' => 'BPJS Kesehatan',
                'status_kepegawaian' => 'PNS', 'pangkat' => 'Penata Muda', 'jabatan' => 'Staf',
                'bagian' => 'Bagian Pelayanan', 'email' => 'siti@contoh.id', 'alamat' => 'Jl. Ahmad Yani No. 2',
                'status_aktif' => 'aktif', 'no_hp' => '081298765432',
                'tanggal_lahir' => '1990-03-15', 'jenis_kelamin' => 'P',
                'penyakit' => [$asma?->id],
                'kunjungan' => [
                    ['tanggal_kunjungan' => '2026-07-12', 'keluhan' => 'Sesak napas', 'diagnosa' => 'Asma eksaserbasi'],
                ],
            ],
            [
                'nama' => 'Agus Wijaya', 'nip' => '197812302008121003', 'no_bpjs' => '0001234567892',
                'satker_id' => $kemkes?->id, 'satuan_kerja' => 'Kementerian Kesehatan',
                'status_kepegawaian' => 'TNI', 'pangkat' => 'Sersan Satu', 'jabatan' => 'Babinsa',
                'bagian' => 'Bagian Kesehatan', 'email' => 'agus@contoh.id', 'alamat' => 'Jl. Sudirman No. 3',
                'status_aktif' => 'aktif', 'no_hp' => '082112345678',
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

        // ===== Poli, Dokter & Jadwal =====
        $polis = [
            ['kode' => 'UMUM', 'nama' => 'Poli Umum'],
            ['kode' => 'GIGI',  'nama' => 'Poli Gigi'],
            ['kode' => 'PDLM',  'nama' => 'Poli Penyakit Dalam'],
            ['kode' => 'ANAK',  'nama' => 'Poli Anak'],
        ];
        foreach ($polis as $data) {
            Poli::firstOrCreate(['kode' => $data['kode']], $data);
        }

        $poliUmum = Poli::where('kode', 'UMUM')->first();
        $poliGigi = Poli::where('kode', 'GIGI')->first();
        $poliDalam = Poli::where('kode', 'PDLM')->first();
        $poliAnak = Poli::where('kode', 'ANAK')->first();

        $dokters = [
            ['poli_id' => $poliUmum?->id,  'nama' => 'dr. Rina Pratiwi',   'spesialisasi' => 'Dokter Umum'],
            ['poli_id' => $poliGigi?->id,  'nama' => 'drg. Andi Saputra',  'spesialisasi' => 'Dokter Gigi'],
            ['poli_id' => $poliDalam?->id, 'nama' => 'dr. Bambang Haryanto', 'spesialisasi' => 'Spesialis Penyakit Dalam'],
            ['poli_id' => $poliAnak?->id,  'nama' => 'dr. Sari Wulandari', 'spesialisasi' => 'Spesialis Anak'],
        ];
        foreach ($dokters as $data) {
            Dokter::firstOrCreate(['nama' => $data['nama']], $data);
        }

        $drRina    = Dokter::where('nama', 'dr. Rina Pratiwi')->first();
        $drgAndi   = Dokter::where('nama', 'drg. Andi Saputra')->first();
        $drBambang = Dokter::where('nama', 'dr. Bambang Haryanto')->first();
        $drSari    = Dokter::where('nama', 'dr. Sari Wulandari')->first();

        $jadwals = [
            ['dokter_id' => $drRina?->id,    'hari' => 'Senin', 'jam_mulai' => '08:00', 'jam_selesai' => '12:00'],
            ['dokter_id' => $drRina?->id,    'hari' => 'Rabu',  'jam_mulai' => '08:00', 'jam_selesai' => '14:00'],
            ['dokter_id' => $drgAndi?->id,   'hari' => 'Selasa','jam_mulai' => '09:00', 'jam_selesai' => '13:00'],
            ['dokter_id' => $drBambang?->id, 'hari' => 'Kamis', 'jam_mulai' => '08:00', 'jam_selesai' => '11:00'],
            ['dokter_id' => $drSari?->id,    'hari' => 'Jumat', 'jam_mulai' => '08:00', 'jam_selesai' => '12:00'],
        ];
        foreach ($jadwals as $data) {
            if ($data['dokter_id'] && ! Jadwal::where($data)->exists()) {
                Jadwal::create($data);
            }
        }
    }
}

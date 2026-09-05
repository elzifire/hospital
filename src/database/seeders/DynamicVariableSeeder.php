<?php

namespace Database\Seeders;

use App\Models\DynamicVariable;
use Illuminate\Database\Seeder;

class DynamicVariableSeeder extends Seeder
{
    public function run(): void
    {
        $vars = [
            [
                'kode'        => '{nama}',
                'nama'        => 'Nama Lengkap PNPP',
                'sumber_data' => 'pnpps.nama',
                'contoh'      => 'Bripka Joko Susanto',
                'deskripsi'   => 'Nama lengkap personel Polri atau PNS Polri penerima pesan pengingat.',
            ],
            [
                'kode'        => '{nip}',
                'nama'        => 'NIP / NRP Anggota',
                'sumber_data' => 'pnpps.nip_nrp',
                'contoh'      => '85031234',
                'deskripsi'   => 'Nomor Registrasi Pokok / Nomor Induk Pegawai PNPP.',
            ],
            [
                'kode'        => '{satker}',
                'nama'        => 'Satuan Kerja / Kesatuan',
                'sumber_data' => 'satkers.nama',
                'contoh'      => 'Polresta Bogor Kota',
                'deskripsi'   => 'Satuan kerja atau institusi tempat PNPP bertugas.',
            ],
            [
                'kode'        => '{poli}',
                'nama'        => 'Poliklinik / Instalasi',
                'sumber_data' => 'polis.nama',
                'contoh'      => 'Poli Penyakit Dalam',
                'deskripsi'   => 'Poliklinik tujuan jadwal kontrol atau konsultasi.',
            ],
            [
                'kode'        => '{dokter}',
                'nama'        => 'Nama Dokter Pemeriksa',
                'sumber_data' => 'dokters.nama',
                'contoh'      => 'dr. Hendra Pratama, Sp.PD',
                'deskripsi'   => 'Dokter spesialis atau dokter penanggung jawab pelayanan.',
            ],
            [
                'kode'        => '{tanggal}',
                'nama'        => 'Tanggal Kontrol / Kunjungan',
                'sumber_data' => 'jadwals.tanggal',
                'contoh'      => 'Senin, 08 Sep 2026',
                'deskripsi'   => 'Hari dan tanggal pemeriksaan atau pengambilan obat.',
            ],
            [
                'kode'        => '{jam}',
                'nama'        => 'Waktu / Jam Kunjungan',
                'sumber_data' => 'jadwals.jam_mulai',
                'contoh'      => '09:00 WIB',
                'deskripsi'   => 'Jam perkiraan pelayanan di rumah sakit.',
            ],
            [
                'kode'        => '{obat}',
                'nama'        => 'Nama Obat Kronis',
                'sumber_data' => 'penyakit_kronis.nama_obat',
                'contoh'      => 'Amlodipine 10mg & Metformin 500mg',
                'deskripsi'   => 'Nama obat berkala yang perlu dikonsumsi atau diambil di farmasi.',
            ],
            [
                'kode'        => '{no_antrian}',
                'nama'        => 'Nomor Antrean Poliklinik',
                'sumber_data' => 'kunjungans.no_antrian',
                'contoh'      => 'A-024',
                'deskripsi'   => 'Nomor tiket antrean registrasi atau loket dokter.',
            ],
            [
                'kode'        => '{link_konfirmasi}',
                'nama'        => 'Tautan Konfirmasi Hadir',
                'sumber_data' => 'system.generated_url',
                'contoh'      => 'https://rs-bhayangkara.id/c/8f2a',
                'deskripsi'   => 'Link satu kali klik untuk konfirmasi kehadiran atau penjadwalan ulang.',
            ],
        ];

        foreach ($vars as $v) {
            DynamicVariable::updateOrCreate(
                ['kode' => $v['kode']],
                [
                    'nama'        => $v['nama'],
                    'sumber_data' => $v['sumber_data'],
                    'contoh'      => $v['contoh'],
                    'deskripsi'   => $v['deskripsi'],
                    'is_active'   => true,
                ]
            );
        }
    }
}

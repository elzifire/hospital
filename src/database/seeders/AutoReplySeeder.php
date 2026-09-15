<?php

namespace Database\Seeders;

use App\Models\AutoReply;
use Illuminate\Database\Seeder;

/**
 * Bank data Auto Reply — daftar aturan awal yang bisa langsung dipakai
 * webhook untuk membalas pesan masuk secara otomatis. Superadmin bisa
 * menambah/mengedit lewat menu Pengaturan → Auto Reply.
 */
class AutoReplySeeder extends Seeder
{
    public function run(): void
    {
        $aturan = [
            [
                'kategori' => 'operasional',
                'nama' => 'Jam Operasional',
                'cara_cocok' => 'mengandung',
                'pola' => 'jam operasional',
                'prioritas' => 0,
                'isi' => 'Jam operasional kami tersedia dari jam 08.00 pagi sampai jam 22.00 malam. Terima kasih.',
            ],
            [
                'kategori' => 'operasional',
                'nama' => 'Sapaan Umum (catch-all)',
                'cara_cocok' => 'semua',
                'pola' => null,
                'prioritas' => 10,
                'isi' => 'Terima kasih, pesan Anda sudah kami terima. Mohon tunggu, petugas kami akan segera membalas.',
            ],
            [
                'kategori' => 'jadwal_dokter',
                'nama' => 'Jadwal Dokter',
                'cara_cocok' => 'mengandung',
                'pola' => 'jadwal dokter',
                'prioritas' => 1,
                'isi' => 'Untuk jadwal dokter, silakan hubungi nomor WhatsApp admin untuk informasi jadwal praktik terkini.',
            ],
            [
                'kategori' => 'company_profile',
                'nama' => 'Company Profile',
                'cara_cocok' => 'mengandung',
                'pola' => 'profil rumah sakit',
                'prioritas' => 2,
                'isi' => 'Kami adalah rumah sakit yang melayani Pasien Non Penerima Pembayaran (PNPP) dengan layanan kesehatan terbaik.',
            ],
            [
                'kategori' => 'kontak_admin',
                'nama' => 'Nomor WhatsApp Admin',
                'cara_cocok' => 'mengandung',
                'pola' => 'whatsapp admin',
                'prioritas' => 3,
                'isi' => 'Silakan hubungi nomor WhatsApp admin kami untuk bantuan lebih lanjut. Terima kasih.',
            ],
        ];

        foreach ($aturan as $item) {
            AutoReply::query()->updateOrCreate(
                ['nama' => $item['nama']],
                $item,
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use App\Models\TemplateCategory;
use Illuminate\Database\Seeder;

class MessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'nama' => 'Jadwal & Kontrol',
                'slug' => 'jadwal-kontrol',
                'warna' => 'emerald',
                'deskripsi' => 'Pengingat jadwal kunjungan rutin, poliklinik, dan kontrol dokter.',
            ],
            [
                'nama' => 'Pengobatan & Farmasi',
                'slug' => 'pengobatan-farmasi',
                'warna' => 'amber',
                'deskripsi' => 'Pengingat konsumsi obat berkala dan pengambilan resep obat kronis.',
            ],
            [
                'nama' => 'Hasil Lab & Diagnostik',
                'slug' => 'hasil-lab-diagnostik',
                'warna' => 'sky',
                'deskripsi' => 'Pemberitahuan kesiapan hasil tes laboratorium, radiologi, dan pemeriksaan penunjang.',
            ],
            [
                'nama' => 'Layanan Pasca Rawat',
                'slug' => 'layanan-pasca-rawat',
                'warna' => 'purple',
                'deskripsi' => 'Follow up evaluasi kondisi kesehatan pasien pasca rawat inap atau tindakan medis.',
            ],
            [
                'nama' => 'Informasi & Pengumuman',
                'slug' => 'informasi-pengumuman',
                'warna' => 'indigo',
                'deskripsi' => 'Edukasi kesehatan PNPP, sapaan hari besar, dan pengumuman operasional rumah sakit.',
            ],
        ];

        $categoryModels = [];
        foreach ($categories as $cat) {
            $categoryModels[$cat['nama']] = TemplateCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                $cat
            );
        }

        $templates = [
            [
                'category' => 'Jadwal & Kontrol',
                'judul' => 'Pengingat Kontrol Rawat Jalan',
                'kode' => 'TMP-KTRL-01',
                'channel' => 'WhatsApp',
                'konten' => "Halo {nama} ({nip}), kami dari RS Bhayangkara Bogor mengingatkan jadwal kontrol Anda di {poli} bersama {dokter} pada hari {tanggal} pukul {jam}. Mohon hadir tepat waktu dengan membawa kartu PNPP/BPJS. Terima kasih.",
                'deskripsi' => 'Template utama untuk pengingat kontrol rutin H-1 sebelum jadwal.',
                'dipakai_count' => 142,
            ],
            [
                'category' => 'Jadwal & Kontrol',
                'judul' => 'Konfirmasi Kehadiran Poliklinik',
                'kode' => 'TMP-KTRL-02',
                'channel' => 'WhatsApp',
                'konten' => "Yth. {nama}, mohon konfirmasi kehadiran Anda untuk konsultasi di {poli} pada {tanggal}. Balas 'HADIR' untuk konfirmasi, atau hubungi kami jika perlu menjadwalkan ulang.",
                'deskripsi' => 'Untuk konfirmasi slot antrean pemeriksaan dokter.',
                'dipakai_count' => 98,
            ],
            [
                'category' => 'Pengobatan & Farmasi',
                'judul' => 'Pengingat Minum Obat Teratur',
                'kode' => 'TMP-OBAT-01',
                'channel' => 'WhatsApp',
                'konten' => "Halo {nama}, jangan lupa minum obat {obat} sesuai anjuran {dokter}. Kepatuhan konsumsi obat sangat penting untuk percepatan pemulihan Anda. Salam sehat RS Bhayangkara Bogor.",
                'deskripsi' => 'Pengingat teratur harian untuk pasien penyakit kronis.',
                'dipakai_count' => 76,
            ],
            [
                'category' => 'Pengobatan & Farmasi',
                'judul' => 'Pemberitahuan Resep Siap Diambil',
                'kode' => 'TMP-OBAT-02',
                'channel' => 'WhatsApp',
                'konten' => "Kepada {nama}, paket obat dan resep berkala Anda di Instalasi Farmasi RS Bhayangkara Bogor telah siap diambil. Silakan tunjukkan pesan ini ke loket farmasi.",
                'deskripsi' => 'Pemberitahuan saat obat kronis selesai disiapkan instalasi farmasi.',
                'dipakai_count' => 45,
            ],
            [
                'category' => 'Hasil Lab & Diagnostik',
                'judul' => 'Hasil Pemeriksaan Laboratorium Selesai',
                'kode' => 'TMP-LAB-01',
                'channel' => 'WhatsApp',
                'konten' => "Yth. {nama}, hasil pemeriksaan laboratorium Anda telah selesai dianalisis. Anda dapat mengunduh salinan digital atau berkonsultasi langsung ke {poli} dengan {dokter}.",
                'deskripsi' => 'Notifikasi otomatis saat hasil lab diverifikasi analis.',
                'dipakai_count' => 88,
            ],
            [
                'category' => 'Layanan Pasca Rawat',
                'judul' => 'Follow Up Kondisi Pasca Rawat Inap',
                'kode' => 'TMP-POST-01',
                'channel' => 'WhatsApp',
                'konten' => "Halo {nama}, tim medis RS Bhayangkara Bogor menanyakan perkembangan kesehatan Anda setelah pulang perawatan pada {tanggal}. Apakah ada keluhan yang dirasakan saat ini?",
                'deskripsi' => 'Follow up pasca pulang hari ke-3 rawat inap.',
                'dipakai_count' => 63,
            ],
            [
                'category' => 'Informasi & Pengumuman',
                'judul' => 'Sapaan Hari Bhayangkara & Kesehatan',
                'kode' => 'TMP-INFO-01',
                'channel' => 'WhatsApp',
                'konten' => "Keluarga besar RS Bhayangkara Bogor mengucapkan selamat kepada seluruh jajaran anggota {satker}. Tetap jaga kesehatan dan kebugaran tubuh dalam mengabdi kepada negeri.",
                'deskripsi' => 'Pesan ucapan dan edukasi berkala untuk anggota Polri / Satker.',
                'dipakai_count' => 210,
            ],
        ];

        foreach ($templates as $t) {
            $catId = $categoryModels[$t['category']]->id ?? null;
            MessageTemplate::updateOrCreate(
                ['kode' => $t['kode']],
                [
                    'template_category_id' => $catId,
                    'judul' => $t['judul'],
                    'channel' => $t['channel'],
                    'konten' => $t['konten'],
                    'deskripsi' => $t['deskripsi'],
                    'is_active' => true,
                    'dipakai_count' => $t['dipakai_count'],
                ]
            );
        }
    }
}

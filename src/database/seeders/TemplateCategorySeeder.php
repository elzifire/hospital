<?php

namespace Database\Seeders;

use App\Models\TemplateCategory;
use Illuminate\Database\Seeder;

class TemplateCategorySeeder extends Seeder
{
    /**
     * Kategori template yang dipetakan ke modul broadcast rumah sakit:
     * outreach (undangan jadwal), digital reminder (pengingat), respon
     * (balasan otomatis), dan follow up (tindak lanjut).
     */
    public function run(): void
    {
        $categories = [
            [
                'nama' => 'Outreach',
                'slug' => 'outreach',
                'warna' => 'emerald',
                'deskripsi' => 'Template undangan & ajakan kunjungan (rule H-7 / H-1) untuk penjadwalan digital reminder.',
            ],
            [
                'nama' => 'Digital Reminder',
                'slug' => 'digital-reminder',
                'warna' => 'sky',
                'deskripsi' => 'Template pengingat otomatis kunjungan, kontrol rutin, dan pengambilan obat.',
            ],
            [
                'nama' => 'Respon',
                'slug' => 'respon',
                'warna' => 'amber',
                'deskripsi' => 'Template balasan otomatis atas respon/pertanyaan masuk via WhatsApp gateway.',
            ],
            [
                'nama' => 'Follow Up',
                'slug' => 'follow-up',
                'warna' => 'purple',
                'deskripsi' => 'Template tindak lanjut pasien (H-1, hari-H, dan tidak datang) pasca penjadwalan.',
            ],
        ];

        foreach ($categories as $c) {
            TemplateCategory::updateOrCreate(
                ['slug' => $c['slug']],
                $c
            );
        }
    }
}

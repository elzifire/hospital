<?php

namespace Tests\Feature;

use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Models\TemplateCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Estimasi biaya template pesan: jumlah pesan terpakai diambil dari
 * database (message_logs berstatus terkirim), harga satuan di-hardcode
 * di TemplateBiayaController (MARKETING & UTILITY).
 */
class BiayaTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function superadmin(): User
    {
        return User::where('email', 'superadmin@gmail.com')->firstOrFail();
    }

    #[Test]
    public function biaya_dihitung_dari_pesan_terkirim_per_template(): void
    {
        $kategori = TemplateCategory::create(['nama' => 'Promosi', 'slug' => 'promosi', 'is_active' => true]);

        $marketing = MessageTemplate::create([
            'template_category_id' => $kategori->id,
            'judul' => 'Promo Program Kesehatan',
            'kode' => 'TMP-PROMO-01',
            'channel' => 'WhatsApp',
            'meta_category' => 'MARKETING',
            'konten' => 'Halo {nama}, nikmati promo layanan kesehatan.',
        ]);
        $utility = MessageTemplate::create([
            'template_category_id' => $kategori->id,
            'judul' => 'Notifikasi Hasil Lab',
            'kode' => 'TMP-LABX-01',
            'channel' => 'WhatsApp',
            'meta_category' => 'UTILITY',
            'konten' => 'Hasil lab {nama} telah selesai.',
        ]);

        $log = function (MessageTemplate $template, string $status) {
            return MessageLog::create([
                'jenis' => 'follow_up',
                'message_template_id' => $template->id,
                'penerima_nama' => 'Budi Santoso',
                'penerima_no_hp' => '08123456789',
                'konten' => 'Isi pesan.',
                'status' => $status,
            ]);
        };

// Marketing: 2 terkirim + 1 gagal (gagal tdk dihitung → subtotal 2 × 650).
            $log($marketing, 'terkirim');
            $log($marketing, 'terkirim');
            $log($marketing, 'gagal');
            // Utility: 1 terkirim → subtotal 1 × 548.
            $log($utility, 'terkirim');

            $this->actingAs($this->superadmin())
                ->get(route('admin.setting.biaya'))
                ->assertOk()
                ->assertSee('Biaya Template Pesan')
                ->assertSee('TMP-PROMO-01')
                ->assertSee('TMP-LABX-01')
                // Ringkasan tipe template.
                ->assertSee('MARKETING')
                ->assertSee('UTILITY')
                // Jumlah pesan terpakai & subtotal.
                ->assertSee('Rp 1.300')
                ->assertSee('Rp 548')
                ->assertSee('Rp 1.848');
    }

    #[Test]
    public function user_biasa_ditolak_dari_halaman_biaya_template(): void
    {
        $user = User::where('email', 'user@gmail.com')->firstOrFail();

        $this->actingAs($user)->get(route('admin.setting.biaya'))->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Models\MessageTemplate;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateRegistrarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        config([
            'whatsapp.meta.token' => 'test-token',
            'whatsapp.meta.business_account_id' => '123456789',
            'whatsapp.meta.base_url' => 'https://graph.facebook.com',
            'whatsapp.meta.version' => 'v25.0',
            'whatsapp.meta.default_language' => 'en_US',
        ]);
    }

    protected function superadmin()
    {
        return User::where('email', 'superadmin@gmail.com')->firstOrFail();
    }

    private function isiForm(array $data = []): array
    {
        return array_merge([
            'judul' => 'Pengingat Kontrol Rawat Jalan',
            'channel' => 'WhatsApp',
            'konten' => "Yth. Bapak/Ibu {nama},\n\nKontrol berikutnya di {poli} pada {tanggal} pukul {jam} bersama {dokter}.",
            'deskripsi' => 'Pengingat jadwal kontrol rawat jalan.',
            'is_active' => '1',
        ], $data);
    }

    #[Test]
    public function halaman_tambah_template_dapat_diakses_admin(): void
    {
        $this->actingAs($this->superadmin())
            ->get(route('admin.setting.template.create'))
            ->assertOk()
            ->assertSee('Tambah Template Pesan')
            ->assertSee('Daftarkan ke Meta WhatsApp')
            ->assertSee('Pratinjau WhatsApp')
            ->assertSee('{nama}');
    }

    #[Test]
    public function store_local_tanpa_meta_menyimpan_template(): void
    {
        Http::fake();

        $this->actingAs($this->superadmin())
            ->post(route('admin.setting.template.store'), $this->isiForm())
            ->assertRedirect(route('admin.setting.index'));

        $template = MessageTemplate::where('judul', 'Pengingat Kontrol Rawat Jalan')->firstOrFail();

        $this->assertNull($template->meta_template_id);
        $this->assertTrue($template->is_active);
        Http::assertNothingSent();
    }

    #[Test]
    public function halaman_index_merender_daftar_kartu_template(): void
    {
        $template = MessageTemplate::create([
            'judul' => 'Kartu Jadwal',
            'kode' => 'TMP-KARTU',
            'channel' => 'WhatsApp',
            'konten' => 'Halo {nama}, jadwal Anda {tanggal}.',
            'deskripsi' => 'Kartu jadwal kontrol.',
            'is_active' => true,
            'image_url' => '/storage/whatsapp/templates/kartu.jpg',
            'meta_template_id' => 'T-KARTU-1',
            'meta_template_name' => 'kartu_jadwal',
            'meta_language' => 'id',
            'meta_status' => 'APPROVED',
            'meta_components' => [
                ['type' => 'BODY', 'text' => 'Halo {{1}}, jadwal Anda {{2}}.'],
                ['type' => 'BUTTON', 'buttons' => [['type' => 'URL', 'text' => 'Detail Jadwal']]],
            ],
        ]);

        $this->actingAs($this->superadmin())
            ->get(route('admin.setting.index'))
            ->assertOk()
            ->assertSee('Kartu Jadwal')
            ->assertSee('Tambah Template')
            ->assertSee('Detail Jadwal')
            ->assertSee('kartu_jadwal')
            ->assertSee('Foto header')
            ->assertDontSee("No'>");
    }

    #[Test]
    public function halaman_edit_merender_panel_status_meta(): void
    {
        $template = MessageTemplate::create([
            'judul' => 'Panel Meta',
            'kode' => 'TMP-PANEL',
            'channel' => 'WhatsApp',
            'konten' => 'Tes panel.',
            'is_active' => true,
            'meta_template_id' => 'T-PANEL-1',
            'meta_template_name' => 'panel_meta',
            'meta_language' => 'id_ID',
            'meta_status' => 'PENDING',
            'last_synced_at' => now(),
        ]);

        $this->actingAs($this->superadmin())
            ->get(route('admin.setting.template.edit', $template))
            ->assertOk()
            ->assertSee('Status Meta WhatsApp')
            ->assertSee('panel_meta')
            ->assertSee('PENDING')
            ->assertSee('id_ID');
    }

    #[Test]
    public function store_mendaftarkan_ke_meta_dan_menyimpan_status(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => '986543210',
                'status' => 'PENDING',
            ], 200),
        ]);

        $this->actingAs($this->superadmin())
            ->post(route('admin.setting.template.store'), $this->isiForm([
                'daftar_ke_meta' => '1',
                'meta_language' => 'id',
                'meta_category' => 'UTILITY',
            ]))
            ->assertRedirect(route('admin.setting.index'))
            ->assertSessionHas('success');

        $template = MessageTemplate::where('judul', 'Pengingat Kontrol Rawat Jalan')->firstOrFail();

        $this->assertSame('986543210', $template->meta_template_id);
        $this->assertSame('PENDING', $template->meta_status);
        $this->assertSame('id', $template->meta_language);
        $this->assertFalse($template->is_active);

        $komponen = collect($template->meta_components)->firstWhere('type', 'BODY');
        $this->assertStringContainsString('{{nama}}', $komponen['text']);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/123456789/message_templates')
            && $request['name'] === $template->meta_template_name
            && $request['language'] === 'id');
    }

    #[Test]
    public function store_template_menyetujui_status_approval_langsung_aktif(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => '986543211',
                'status' => 'APPROVED',
            ], 200),
        ]);

        $this->actingAs($this->superadmin())
            ->post(route('admin.setting.template.store'), $this->isiForm([
                'daftar_ke_meta' => '1',
                'meta_language' => 'en_US',
                'meta_category' => 'UTILITY',
            ]))
            ->assertRedirect(route('admin.setting.index'));

        $template = MessageTemplate::where('judul', 'Pengingat Kontrol Rawat Jalan')->firstOrFail();

        $this->assertSame('APPROVED', $template->meta_status);
        $this->assertTrue($template->is_active);
    }

    #[Test]
    public function store_dengan_meta_tanpa_konfigurasi_tetap_tersimpan_lokal(): void
    {
        config([
            'whatsapp.meta.business_account_id' => '',
        ]);

        $this->actingAs($this->superadmin())
            ->post(route('admin.setting.template.store'), $this->isiForm([
                'daftar_ke_meta' => '1',
            ]))
            ->assertRedirect(route('admin.setting.index'))
            ->assertSessionHas('error');

        $template = MessageTemplate::where('judul', 'Pengingat Kontrol Rawat Jalan')->firstOrFail();

        $this->assertNull($template->meta_template_id);
        $this->assertTrue($template->is_active);
    }

    #[Test]
    public function store_konten_tanpa_variabel_tetap_daftar_ke_meta(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => '986543212',
                'status' => 'PENDING',
            ], 200),
        ]);

        $this->actingAs($this->superadmin())
            ->post(route('admin.setting.template.store'), $this->isiForm([
                'konten' => 'Jadwal layanan Anda dapat dicek di portal.',
                'daftar_ke_meta' => '1',
                'meta_language' => 'id',
                'meta_category' => 'UTILITY',
            ]))
            ->assertRedirect(route('admin.setting.index'));

        $template = MessageTemplate::where('judul', 'Pengingat Kontrol Rawat Jalan')->firstOrFail();

        Http::assertSent(function ($request) {
            $komponen = collect($request['components'])->firstWhere('type', 'BODY');

            return ! isset($komponen['example']);
        });

        $this->assertNotNull($template->meta_template_id);
    }
}

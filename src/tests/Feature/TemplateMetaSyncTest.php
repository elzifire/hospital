<?php

namespace Tests\Feature;

use App\Broadcasting\WhatsApp\MetaTemplateSync;
use App\Models\MessageTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateMetaSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'whatsapp.meta.token' => 'test-token',
            'whatsapp.meta.business_account_id' => '123456789',
            'whatsapp.meta.base_url' => 'https://graph.facebook.com',
            'whatsapp.meta.version' => 'v25.0',
        ]);
    }

    private function faktaRespons(): array
    {
        return [
            'data' => [[
                'id' => 'T-EDU-1',
                'name' => 'edukasi_gigi_mulut',
                'language' => 'id',
                'status' => 'APPROVED',
                'category' => 'UTILITY',
                'components' => [
                    [
                        'type' => 'HEADER',
                        'format' => 'IMAGE',
                        'example' => ['header_handle' => ['https://scontent.whatsapp.net/logo.jpg']],
                    ],
                    ['type' => 'BODY', 'text' => 'Halo {{1}}, jaga gigi Anda.'],
                ],
                'updated_time' => '2026-09-15T00:00:00+0000',
            ]],
            'paging' => ['cursors' => ['after' => null]],
        ];
    }

    #[Test]
    public function sinkron_menyimpan_media_header_dari_snapshot_ke_storage_public(): void
    {
        Storage::fake('public');

        Http::fake([
            'graph.facebook.com/*' => Http::response($this->faktaRespons(), 200),
            'scontent.whatsapp.net/*' => Http::response('byte-gambar', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        app(MetaTemplateSync::class)->sync();

        $template = MessageTemplate::where('meta_template_name', 'edukasi_gigi_mulut')->firstOrFail();
        $this->assertSame('/storage/whatsapp/templates/edukasi_gigi_mulut-id.jpg', $template->image_url);
        $this->assertSame('byte-gambar', Storage::disk('public')->get(Str::after((string) $template->image_url, '/storage/')));
    }

    #[Test]
    public function sinkron_media_baru_mereset_cache_media_lama(): void
    {
        Storage::fake('public');

        $template = MessageTemplate::create([
            'kode' => 'META-EDU-ID',
            'judul' => 'Edukasi Gigi',
            'channel' => 'WhatsApp',
            'konten' => 'Teks lama.',
            'is_active' => false,
            'meta_template_id' => 'T-EDU-1',
            'meta_template_name' => 'edukasi_gigi_mulut',
            'meta_language' => 'id',
            'image_url' => '/storage/whatsapp/templates/lama.jpg',
            'meta_media_id' => '555888777666555',
            'meta_media_at' => now(),
            'last_synced_at' => now(),
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response($this->faktaRespons(), 200),
            'scontent.whatsapp.net/*' => Http::response('byte-baru', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        app(MetaTemplateSync::class)->sync();

        $this->assertSame('/storage/whatsapp/templates/edukasi_gigi_mulut-id.jpg', $template->refresh()->image_url);
        $this->assertNull($template->meta_media_id);
        $this->assertNull($template->meta_media_at);
    }

    #[Test]
    public function sinkron_media_tanpa_url_menjaga_image_url_lokal(): void
    {
        Storage::fake('public');

        $template = MessageTemplate::create([
            'kode' => 'META-EDU-ID2',
            'judul' => 'Edukasi Gigi',
            'channel' => 'WhatsApp',
            'konten' => 'Teks lama.',
            'is_active' => true,
            'meta_template_id' => 'T-EDU-1',
            'meta_template_name' => 'edukasi_gigi_mulut',
            'meta_language' => 'id',
            'image_url' => '/storage/whatsapp/templates/edisi-lokal.jpg',
            'meta_media_id' => '999888777666555',
            'meta_media_at' => now(),
            'last_synced_at' => now(),
        ]);

        // Snapshot berheader IMAGE tapi header_handle bukan URL (media
        // handle) — tidak ada yang bisa diunduh, konfigurasi lokal dipertahankan.
        $respons = $this->faktaRespons();
        $respons['data'][0]['components'][0]['example']['header_handle'] = ['4::aW1hZ2UvanBlZw=='];

        Http::fake([
            'graph.facebook.com/*' => Http::response($respons, 200),
            'scontent.whatsapp.net/*' => Http::response('', 200),
        ]);

        app(MetaTemplateSync::class)->sync();

        $this->assertSame('/storage/whatsapp/templates/edisi-lokal.jpg', $template->refresh()->image_url);
        $this->assertSame('999888777666555', $template->meta_media_id);
        $this->assertNotNull($template->meta_media_at);
    }
}

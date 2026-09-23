<?php

namespace Tests\Feature;

use App\Models\MessageLog;
use App\Models\MessageReply;
use App\Models\Pnpp;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Halaman percakapan Respon: composer hanya teks (fitur "Lampirkan media"
 * dan CTA URL dihapus), sedangkan timeline mampu menampilkan media keluar
 * (dari storage lokal) maupun media masuk pasien (diunduh on-demand dari
 * API Meta lalu di-cache ke storage publik) — dengan tombol buka & unduh.
 */
class ResponMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
    }

    protected function superadmin(): User
    {
        return User::where('email', 'superadmin@gmail.com')->firstOrFail();
    }

    protected function pasien(): Pnpp
    {
        return Pnpp::create(['nama' => 'Budi Santoso', 'no_hp' => '081234567890']);
    }

    protected function balasanMasuk(array $objek, string $tipe, array $ekstra = []): MessageReply
    {
        $pesan = array_merge([
            'from' => '6281234567890',
            'id' => 'WAMEDIA-1',
            'type' => $tipe,
            'mime_type' => 'application/octet-stream',
            $tipe => $objek,
        ], $ekstra);

        return MessageReply::create([
            'pnpp_id' => $this->pasien()->id,
            'no_hp' => '6281234567890',
            'nama' => 'Budi Santoso',
            'isi_pesan' => '['.$tipe.']',
            'waktu_masuk' => now(),
            'driver' => 'meta',
            'payload' => ['pesan' => $pesan, 'metadata' => null],
        ]);
    }

    #[Test]
    public function composer_hanya_teks_tanpa_fitur_media_dan_cta(): void
    {
        $this->balasanMasuk(['body' => 'Halo dok'], 'text');

        $this->actingAs($this->superadmin())
            ->get(route('admin.respon.show', '6281234567890'))
            ->assertOk()
            ->assertSee('Tulis balasan…')
            ->assertDontSee('Lampirkan media')
            ->assertDontSee('fileInput')
            ->assertDontSee('panelCta')
            ->assertDontSee('Kirim tombol CTA URL');
    }

    #[Test]
    public function media_keluar_ditampilkan_dan_bisa_dibuka_diunduh(): void
    {
        $this->pasien();

        Storage::disk('public')->put('respon-media/gambar.jpg', 'JPG');

        MessageLog::create([
            'jenis' => 'respon',
            'rule' => 'balasan',
            'pnpp_id' => null,
            'created_by' => $this->superadmin()->id,
            'penerima_nama' => 'Budi Santoso',
            'penerima_no_hp' => '6281234567890',
            'konten' => 'Hasil foto',
            'status' => 'terkirim',
            'provider' => 'meta',
            'meta_payload' => [
                'kind' => 'media', 'tipe' => 'image', 'nama' => 'gambar.jpg',
                'mime' => 'image/jpeg', 'path' => 'respon-media/gambar.jpg', 'caption' => 'Hasil foto',
            ],
        ]);

        $this->actingAs($this->superadmin())
            ->get(route('admin.respon.show', '6281234567890'))
            ->assertOk()
            ->assertSee('respon-media/gambar.jpg')
            ->assertSee('Unduh gambar');
    }

    #[Test]
    public function media_document_keluar_tampil_dengan_tombol_buka_dan_unduh(): void
    {
        $this->pasien();

        Storage::disk('public')->put('respon-media/laporan.pdf', 'PDF');

        MessageLog::create([
            'jenis' => 'respon',
            'rule' => 'balasan',
            'pnpp_id' => null,
            'created_by' => $this->superadmin()->id,
            'penerima_nama' => 'Budi Santoso',
            'penerima_no_hp' => '6281234567890',
            'konten' => '[document] laporan.pdf',
            'status' => 'terkirim',
            'provider' => 'meta',
            'meta_payload' => [
                'kind' => 'media', 'tipe' => 'document', 'nama' => 'laporan.pdf',
                'mime' => 'application/pdf', 'path' => 'respon-media/laporan.pdf', 'caption' => '',
            ],
        ]);

        $this->actingAs($this->superadmin())
            ->get(route('admin.respon.show', '6281234567890'))
            ->assertOk()
            ->assertSee('laporan.pdf')
            ->assertSee('Buka')
            ->assertSee('Unduh');
    }

    #[Test]
    public function media_masuk_image_ditampilkan_dan_bisa_diunduh(): void
    {
        $reply = $this->balasanMasuk(
            ['id' => 'WAMEDIA-1', 'caption' => 'Foto hasil lab'],
            'image',
            ['mime_type' => 'image/jpeg'],
        );
        $url = route('admin.respon.media', ['nomor' => '6281234567890', 'balasan' => $reply->id]);

        $this->actingAs($this->superadmin())
            ->get(route('admin.respon.show', '6281234567890'))
            ->assertOk()
            ->assertSee($url)
            ->assertSee('Unduh gambar');
    }

    #[Test]
    public function media_masuk_document_tampil_dengan_tombol_buka_dan_unduh(): void
    {
        $reply = $this->balasanMasuk(
            ['id' => 'WAMEDIA-1', 'filename' => 'laporan-lab.pdf'],
            'document',
            ['mime_type' => 'application/pdf'],
        );
        $url = route('admin.respon.media', ['nomor' => '6281234567890', 'balasan' => $reply->id]);

        $this->actingAs($this->superadmin())
            ->get(route('admin.respon.show', '6281234567890'))
            ->assertOk()
            ->assertSee('laporan-lab.pdf')
            ->assertSee($url)
            ->assertSee('Unduh');
    }

    #[Test]
    public function endpoint_media_mengunduh_dari_meta_dan_meng_cache_lokal(): void
    {
        $reply = $this->balasanMasuk(
            ['id' => 'WAMEDIA-1', 'caption' => 'Foto'],
            'image',
            ['mime_type' => 'image/jpeg'],
        );

        Http::fake([
            'graph.facebook.com/*/WAMEDIA-1' => Http::response([
                'url' => 'https://lookaside.fbsbx.com/unduhan/wamedia-1.jpeg',
                'mime_type' => 'image/jpeg',
                'id' => 'WAMEDIA-1',
            ], 200),
            'lookaside.fbsbx.com/*' => Http::response('BYTES-GAMBAR', 200, ['Content-Type' => 'image/jpeg']),
        ]);
        config()->set('whatsapp.meta.token', 'token-tes');
        config()->set('whatsapp.meta.phone_number_id', '123456789');

        $this->actingAs($this->superadmin())
            ->get(route('admin.respon.media', ['nomor' => '6281234567890', 'balasan' => $reply->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        // Berkas ter-cache ke storage publik, dan path-nya disimpan di payload.
        $berkas = Storage::disk('public')->files('respon-media/masuk');
        $this->assertCount(1, $berkas);
        $this->assertTrue(Storage::disk('public')->exists($reply->refresh()->payload['media_lokal']));

        // Akses unduh memaksa Content-Disposition attachment.
        $this->actingAs($this->superadmin())
            ->get(route('admin.respon.media', ['nomor' => '6281234567890', 'balasan' => $reply->id, 'unduh' => 1]))
            ->assertOk()
            ->assertHeaderContains('Content-Disposition', 'attachment');
    }

    #[Test]
    public function endpoint_media_404_bila_bukan_milik_percakapan(): void
    {
        $reply = $this->balasanMasuk(['id' => 'WAMEDIA-1'], 'image');

        $this->actingAs($this->superadmin())
            ->get(route('admin.respon.media', ['nomor' => '6281355557777', 'balasan' => $reply->id]))
            ->assertNotFound();
    }

    #[Test]
    public function endpoint_media_404_bila_meta_menolak_media(): void
    {
        $reply = $this->balasanMasuk(['id' => 'WAMEDIA-1'], 'image');

        Http::fake(['graph.facebook.com/*' => Http::response(['error' => 'not found'], 404)]);
        config()->set('whatsapp.meta.token', 'token-tes');
        config()->set('whatsapp.meta.phone_number_id', '123456789');

        $this->actingAs($this->superadmin())
            ->get(route('admin.respon.media', ['nomor' => '6281234567890', 'balasan' => $reply->id]))
            ->assertNotFound();
    }
}

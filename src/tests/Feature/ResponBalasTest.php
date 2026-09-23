<?php

namespace Tests\Feature;

use App\Models\MessageLog;
use App\Models\MessageReply;
use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Balas balasan pasien dari halaman percakapan respon — superadmin/admin
 * boleh semua, akun poli dibatasi ke pasien polinya sendiri. Pengiriman
 * teks bebas berjalan sinkron (tanpa websocket; frontend memakai polling
 * JSON ke endpoint timeline).
 */
class ResponBalasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    protected function superadmin(): User
    {
        return User::where('email', 'superadmin@gmail.com')->firstOrFail();
    }

    protected function pasangan(): array
    {
        $poliUmum = Poli::where('kode', 'UMUM')->firstOrFail();
        $poliGigi = Poli::where('kode', 'GIGI')->firstOrFail();

        $budi = Pnpp::create(['nama' => 'Budi Santoso', 'no_hp' => '081234567890']);
        $budi->kunjungans()->create([
            'poli_id' => $poliUmum->id,
            'tanggal_kunjungan' => today()->format('Y-m-d'),
        ]);

        $siti = Pnpp::create(['nama' => 'Siti Aminah', 'no_hp' => '081355557777']);
        $siti->kunjungans()->create([
            'poli_id' => $poliGigi->id,
            'tanggal_kunjungan' => today()->format('Y-m-d'),
        ]);

        MessageReply::create([
            'pnpp_id' => $budi->id, 'no_hp' => '6281234567890', 'nama' => 'Budi Santoso',
            'isi_pesan' => 'Siap dok, saya hadir.', 'waktu_masuk' => now(), 'driver' => 'waha',
        ]);
        MessageReply::create([
            'pnpp_id' => $siti->id, 'no_hp' => '6281355557777', 'nama' => 'Siti Aminah',
            'isi_pesan' => 'Boleh diubah jadwalnya?', 'waktu_masuk' => now(), 'driver' => 'waha',
        ]);

        return compact('poliUmum', 'poliGigi', 'budi', 'siti');
    }

    #[Test]
    public function admin_membalas_pasien_mengirim_teks_bebas(): void
    {
        extract($this->pasangan());

        $this->actingAs($this->superadmin())
            ->postJson(route('admin.respon.balas', '6281234567890'), ['isi' => 'Terima kasih sudah konfirmasi.'])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('message', 'Balasan terkirim ke Budi Santoso.');

        $this->assertDatabaseHas('message_logs', [
            'jenis' => 'respon',
            'rule' => 'balasan',
            'pnpp_id' => $budi->id,
            'penerima_no_hp' => '6281234567890',
            'konten' => 'Terima kasih sudah konfirmasi.',
            'status' => 'terkirim',
        ]);

        $log = MessageLog::where('penerima_no_hp', '6281234567890')->firstOrFail();
        $this->assertNull($log->meta_template_name);
        $this->assertSame([], $log->template_params);
    }

    #[Test]
    public function timeline_json_memuat_percakapan_untuk_polling_js(): void
    {
        extract($this->pasangan());

        $this->actingAs($this->superadmin())
            ->postJson(route('admin.respon.balas', '6281234567890'), ['isi' => 'Baik, sampai jumpa.']);

        $this->actingAs($this->superadmin())
            ->getJson(route('admin.respon.timeline', '6281234567890'))
            ->assertOk()
            ->assertJsonStructure(['signature', 'html'])
            ->assertJsonPath('html', fn ($html) => str_contains($html, 'Siap dok, saya hadir.') && str_contains($html, 'Baik, sampai jumpa.'));
    }

    #[Test]
    public function timeline_tetap_berfungsi_saat_belum_ada_pesan_keluar(): void
    {
        extract($this->pasangan());

        // Skenario pasien baru membalas (hanya pesan masuk, belum ada
        // outbound untuk nomor ini) — sebelumnya crash di merge Eloquent
        // Collection kosong → "Call to a member function getKey() on array".
        $this->assertSame(0, MessageLog::where('penerima_no_hp', '6281234567890')->count());

        $this->actingAs($this->superadmin())
            ->getJson(route('admin.respon.timeline', '6281234567890'))
            ->assertOk()
            ->assertJsonStructure(['signature', 'html'])
            ->assertJsonPath('signature', fn ($signature) => $signature !== '' && $signature !== '0');
    }

    #[Test]
    public function admin_mengirim_gambar_sebagai_balasan(): void
    {
        extract($this->pasangan());
        Storage::fake('public');

        $this->actingAs($this->superadmin())
            ->postJson(route('admin.respon.balas', '6281234567890'), [
                'tipe' => 'image',
                'media' => UploadedFile::fake()->image('resep-dokter.jpg'),
                'caption' => 'Ini foto resepnya.',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('log.tipe', 'image');

        $log = MessageLog::where('penerima_no_hp', '6281234567890')->firstOrFail();
        $this->assertSame('terkirim', $log->status);
        $this->assertSame('Ini foto resepnya.', $log->konten);
        $this->assertSame('media', $log->meta_payload['kind'] ?? null);
        $this->assertSame('image', $log->meta_payload['tipe'] ?? null);
        $this->assertStringContainsString('respon-media/', (string) ($log->meta_payload['path'] ?? ''));
        Storage::disk('public')->assertExists($log->meta_payload['path']);

        $html = $this->actingAs($this->superadmin())
            ->getJson(route('admin.respon.timeline', '6281234567890'))
            ->assertOk()
            ->json('html');
        $this->assertStringContainsString('respon-media/', $html);
        $this->assertStringContainsString('Ini foto resepnya.', $html);
    }

    #[Test]
    public function balasan_media_wajib_mengirim_berkas(): void
    {
        extract($this->pasangan());

        $this->actingAs($this->superadmin())
            ->postJson(route('admin.respon.balas', '6281234567890'), [
                'tipe' => 'image',
                'caption' => 'Tanpa berkas.',
            ])
            ->assertUnprocessable();
    }

    #[Test]
    public function nama_tersimpan_memakai_ekstensi_deteksi_server_bukan_klien(): void
    {
        extract($this->pasangan());
        Storage::fake('public');

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
        $path = tempnam(sys_get_temp_dir(), 'respon');
        file_put_contents($path, $png);
        $file = new UploadedFile($path, '../foto.jpg', 'image/jpeg', null, true);

        $this->actingAs($this->superadmin())
            ->postJson(route('admin.respon.balas', '6281234567890'), [
                'tipe' => 'image',
                'media' => $file,
                'caption' => 'Hasil pemeriksaan.',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $log = MessageLog::where('penerima_no_hp', '6281234567890')->firstOrFail();
        $tersimpan = (string) ($log->meta_payload['path'] ?? '');

        $this->assertStringStartsWith('respon-media/', $tersimpan);
        $this->assertMatchesRegularExpression('/\.png$/i', $tersimpan, 'Ekstensi harus mengikuti isi berkas (PNG), bukan ekstensi klien (.jpg).');
        $this->assertStringNotContainsString('.jpg', $tersimpan);
        // Nama asli tersimpan untuk display, tapi jalur direktori sudah
        // dibuang (path traversal).
        $this->assertSame('foto.jpg', $log->meta_payload['nama'] ?? null);
        // MIME dicatat dari isi berkas, bukan klaim klien.
        $this->assertSame('image/png', $log->meta_payload['mime'] ?? null);
        Storage::disk('public')->assertExists($tersimpan);
    }

    #[Test]
    public function berkas_berekstensi_php_ditolak_walaupun_isi_gambar(): void
    {
        extract($this->pasangan());

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
        $path = tempnam(sys_get_temp_dir(), 'respon');
        file_put_contents($path, $png);
        $file = new UploadedFile($path, 'serangan.php', 'image/png', null, true);

        $this->actingAs($this->superadmin())
            ->postJson(route('admin.respon.balas', '6281234567890'), [
                'tipe' => 'image',
                'media' => $file,
            ])
            ->assertUnprocessable();
    }

    #[Test]
    public function berkas_dengan_ekstensi_tipuan_disimpan_menurut_isi_sebenarnya(): void
    {
        extract($this->pasangan());
        Storage::fake('public');

        $pdf = "%PDF-1.4\n1 0 obj<</Pages 1 0 R>>\nendobj\ntrailer<</Root 1 0 R>>\n%%EOF";
        $path = tempnam(sys_get_temp_dir(), 'respon');
        file_put_contents($path, $pdf);
        $file = new UploadedFile($path, 'resep.pdf.png', 'image/png', null, true);

        $this->actingAs($this->superadmin())
            ->postJson(route('admin.respon.balas', '6281234567890'), [
                'tipe' => 'document',
                'media' => $file,
                'caption' => 'Resep dokter.',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $log = MessageLog::where('penerima_no_hp', '6281234567890')->firstOrFail();
        $tersimpan = (string) ($log->meta_payload['path'] ?? '');

        $this->assertMatchesRegularExpression('/\.pdf$/i', $tersimpan);
        $this->assertSame('application/pdf', $log->meta_payload['mime'] ?? null);
        $this->assertSame('resep.pdf.png', $log->meta_payload['nama'] ?? null);
        Storage::disk('public')->assertExists($tersimpan);
    }

    #[Test]
    public function berkas_dengan_isi_tidak_cocok_tipe_ditolak(): void
    {
        extract($this->pasangan());

        // Nama & klaim MIME klien "pdf", tapi isi sebenarnya PNG.
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
        $path = tempnam(sys_get_temp_dir(), 'respon');
        file_put_contents($path, $png);
        $file = new UploadedFile($path, 'surat.pdf', 'application/pdf', null, true);

        $this->actingAs($this->superadmin())
            ->postJson(route('admin.respon.balas', '6281234567890'), [
                'tipe' => 'document',
                'media' => $file,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('media');
    }

    #[Test]
    public function admin_mengirim_tombol_cta_url(): void
    {
        extract($this->pasangan());

        $this->actingAs($this->superadmin())
            ->postJson(route('admin.respon.balas', '6281234567890'), [
                'tipe' => 'interactive',
                'isi' => 'Silakan buka tautan pendaftaran berikut.',
                'cta_url' => 'https://rs-bhayangkara.id/daftar',
                'cta_label' => 'Daftar Sekarang',
                'cta_header' => 'Pendaftaran Online',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('log.tipe', 'interactive');

        $log = MessageLog::where('penerima_no_hp', '6281234567890')->firstOrFail();
        $this->assertSame('terkirim', $log->status);
        $this->assertSame('Silakan buka tautan pendaftaran berikut.', $log->konten);
        $this->assertSame('interactive', $log->meta_payload['kind'] ?? null);
        $this->assertSame('https://rs-bhayangkara.id/daftar', $log->meta_payload['url'] ?? null);
        $this->assertSame('Daftar Sekarang', $log->meta_payload['label'] ?? null);

        $html = $this->actingAs($this->superadmin())
            ->getJson(route('admin.respon.timeline', '6281234567890'))
            ->assertOk()
            ->json('html');
        $this->assertStringContainsString('rs-bhayangkara.id/daftar', $html);
        $this->assertStringContainsString('Daftar Sekarang', $html);
    }

    #[Test]
    public function cta_url_wajib_mengisi_url_dan_label(): void
    {
        extract($this->pasangan());

        $this->actingAs($this->superadmin())
            ->postJson(route('admin.respon.balas', '6281234567890'), [
                'tipe' => 'interactive',
                'isi' => 'Tes.',
            ])
            ->assertUnprocessable();

        $this->actingAs($this->superadmin())
            ->postJson(route('admin.respon.balas', '6281234567890'), [
                'tipe' => 'interactive',
                'isi' => 'Tes.',
                'cta_url' => 'bukan-url',
                'cta_label' => 'Buka',
            ])
            ->assertUnprocessable();
    }

    #[Test]
    public function form_balas_tanpa_isi_ditolak(): void
    {
        extract($this->pasangan());

        $this->actingAs($this->superadmin())
            ->postJson(route('admin.respon.balas', '6281234567890'), ['isi' => ''])
            ->assertUnprocessable();
    }

    #[Test]
    public function akun_poli_hanya_melihat_dan_membalas_pasien_polinya(): void
    {
        extract($this->pasangan());

        $petugas = User::create([
            'name' => 'Petugas Poli Umum',
            'email' => 'petugas-umum@test.dev',
            'password' => 'rahasia',
        ]);
        $petugas->assignRole('poli');
        $petugas->userDetail()->updateOrCreate([], ['poli_id' => $poliUmum->id]);

        // Index balasan → hanya pasien poli sendiri.
        $this->actingAs($petugas)
            ->get(route('admin.respon.index'))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Siti Aminah');

        // Boleh membalas pasien polinya.
        $this->actingAs($petugas)
            ->postJson(route('admin.respon.balas', '6281234567890'), ['isi' => 'Ingatkan kontrol besok ya.'])
            ->assertOk()
            ->assertJsonPath('ok', true);

        // Pasien poli lain → ditolak 403 (form & halaman percakapan).
        $this->actingAs($petugas)
            ->postJson(route('admin.respon.balas', '6281355557777'), ['isi' => 'Halo.'])
            ->assertForbidden();
        $this->actingAs($petugas)
            ->getJson(route('admin.respon.timeline', '6281355557777'))
            ->assertForbidden();
        $this->actingAs($petugas)
            ->get(route('admin.respon.show', '6281355557777'))
            ->assertForbidden();

        // Memastikan tidak ada balasan ke pasien poli lain yang terkirim.
        $this->assertDatabaseCount('message_logs', 1);
        $this->assertDatabaseHas('message_logs', ['penerima_no_hp' => '6281234567890']);
    }

    #[Test]
    public function user_biasa_ditolak_dari_modul_respon_balas(): void
    {
        extract($this->pasangan());

        $user = User::where('email', 'user@gmail.com')->firstOrFail();

        $this->actingAs($user)->postJson(route('admin.respon.balas', '6281234567890'), ['isi' => 'Halo'])
            ->assertForbidden();
        $this->actingAs($user)->getJson(route('admin.respon.timeline', '6281234567890'))
            ->assertForbidden();
        $this->actingAs($user)->get(route('admin.respon.show', '6281234567890'))
            ->assertForbidden();
    }
}

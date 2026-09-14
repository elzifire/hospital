<?php

namespace Tests\Feature;

use App\Models\MessageLog;
use App\Models\MessageReply;
use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
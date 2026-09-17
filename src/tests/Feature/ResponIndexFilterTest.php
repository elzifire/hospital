<?php

namespace Tests\Feature;

use App\Models\MessageReply;
use App\Models\Pnpp;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Sortir & filter daftar percakapan di halaman admin/respon: percakapan
 * dengan balasan belum dibaca tampil lebih dulu, lalu urut pesan terbaru.
 * Filter "Status baca" dan "Kontak" menyaring lewat query string yang
 * sama untuk halaman maupun endpoint polling.
 */
class ResponIndexFilterTest extends TestCase
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

    protected function buatBalasan(string $noHp, ?string $nama, string $isi, ?Carbon $waktu = null, bool $dibaca = false, ?int $pnppId = null): MessageReply
    {
        return MessageReply::create([
            'pnpp_id' => $pnppId,
            'no_hp' => $noHp,
            'nama' => $nama,
            'isi_pesan' => $isi,
            'waktu_masuk' => $waktu ?? now(),
            'read_at' => $dibaca ? now() : null,
            'driver' => 'waha',
        ]);
    }

    #[Test]
    public function percakapan_belum_dibaca_urut_lebih_dulu_dari_pesan_terbaru(): void
    {
        // Belum dibaca, lebih lama.
        $this->buatBalasan('6281111111111', 'Andi Belum', 'Belum saya balas dok.', now()->subMinutes(5));
        // Sudah dibaca, lebih baru.
        $this->buatBalasan('6282222222222', 'Ratna Baca', 'Mau tanya jadwal.', now()->subMinutes(2), dibaca: true);

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.respon.index'))
            ->assertOk()
            ->getContent();

        $posBelum = strpos($html, '6281111111111');
        $posBaca = strpos($html, '6282222222222');
        $this->assertNotFalse($posBelum, 'Percakapan belum dibaca muncul di daftar');
        $this->assertNotFalse($posBaca, 'Percakapan sudah dibaca muncul di daftar');
        $this->assertLessThan($posBaca, $posBelum, 'Belum dibaca harus diurutkan lebih dulu daripada yang baru dibaca');
    }

    #[Test]
    public function filter_status_baca_belum_hanya_menampilkan_belum_dibaca(): void
    {
        $this->buatBalasan('6281111111111', 'Andi Belum', 'Belum saya balas dok.', now()->subMinutes(5));
        $this->buatBalasan('6282222222222', 'Ratna Baca', 'Mau tanya jadwal.', now()->subMinutes(2), dibaca: true);

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.respon.index', ['status_baca' => 'belum']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('6281111111111', $html);
        $this->assertStringNotContainsString('6282222222222', $html);
    }

    #[Test]
    public function filter_status_baca_dibaca_hanya_menampilkan_sudah_dibaca(): void
    {
        $this->buatBalasan('6281111111111', 'Andi Belum', 'Belum saya balas dok.', now()->subMinutes(5));
        $this->buatBalasan('6282222222222', 'Ratna Baca', 'Mau tanya jadwal.', now()->subMinutes(2), dibaca: true);

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.respon.index', ['status_baca' => 'dibaca']))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('6281111111111', $html);
        $this->assertStringContainsString('6282222222222', $html);
    }

    #[Test]
    public function filter_asal_tak_terdaftar_hanya_menampilkan_nomor_asing(): void
    {
        $budi = Pnpp::create(['nama' => 'Budi Santoso', 'no_hp' => '081234567890']);
        $this->buatBalasan('6281234567890', 'Budi Santoso', 'Saya hadir.', now(), pnppId: $budi->id);
        $this->buatBalasan('6289999999999', null, 'Siapa ini?', now());

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.respon.index', ['asal' => 'tak_terdaftar']))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('6281234567890', $html);
        $this->assertStringContainsString('6289999999999', $html);
    }

    #[Test]
    public function polling_mengikuti_query_filter_yang_sama(): void
    {
        $this->buatBalasan('6281111111111', 'Andi Belum', 'Belum saya balas dok.', now()->subMinutes(5));
        $this->buatBalasan('6282222222222', 'Ratna Baca', 'Mau tanya jadwal.', now()->subMinutes(2), dibaca: true);

        $json = $this->actingAs($this->superadmin())
            ->getJson(route('admin.respon.poll', ['status_baca' => 'belum']))
            ->assertOk();

        $this->assertStringContainsString('6281111111111', $json->json('html'));
        $this->assertStringNotContainsString('6282222222222', $json->json('html'));
    }
}

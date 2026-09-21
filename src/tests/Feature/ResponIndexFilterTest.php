<?php

namespace Tests\Feature;

use App\Models\MessageLog;
use App\Models\MessageReply;
use App\Models\MessageTemplate;
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

    #[Test]
    public function filter_rentang_tanggal_menyaring_percakapan(): void
    {
        $this->buatBalasan('6281111111111', 'Andi Lama', 'Pesan kemarin.', now()->subDays(2));
        $this->buatBalasan('6282222222222', 'Ratna Baru', 'Pesan hari ini.', now());

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.respon.index', [
                'dari' => today()->format('Y-m-d'),
                'sampai' => today()->format('Y-m-d'),
            ]))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('6281111111111', $html);
        $this->assertStringContainsString('6282222222222', $html);
    }

    #[Test]
    public function filter_template_terkirim_menyaring_percakapan(): void
    {
        $template = MessageTemplate::create([
            'judul' => 'Ingatkan Kontrol',
            'konten' => 'Ingat kontrol besok.',
            'is_active' => true,
        ]);

        $this->buatBalasan('6281111111111', 'Andi Terima', 'Siap dok.', now());
        $this->buatBalasan('6282222222222', 'Ratna Tanpa', 'Siapa ini?', now());

        MessageLog::create([
            'jenis' => 'outreach',
            'rule' => 'h-7',
            'message_template_id' => $template->id,
            'penerima_nama' => 'Andi Terima',
            'penerima_no_hp' => '6281111111111',
            'konten' => 'Ingat kontrol besok.',
            'status' => 'terkirim',
        ]);

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.respon.index', ['template' => $template->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('6281111111111', $html);
        $this->assertStringNotContainsString('6282222222222', $html);
    }

    #[Test]
    public function konten_endpoint_mendukung_infinite_scroll(): void
    {
        for ($i = 16; $i >= 1; $i--) {
            $nomor = '6280000000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $this->buatBalasan($nomor, "Kontak $i", "Pesan $i", now()->subMinutes($i));
        }

        $halaman1 = $this->actingAs($this->superadmin())
            ->getJson(route('admin.respon.konten'))
            ->assertOk()
            ->json();

        $this->assertGreaterThan(0, substr_count($halaman1['html'], '<li'));
        $this->assertTrue($halaman1['hasMore']);
        $this->assertSame(1, $halaman1['halaman']);

        $halaman2 = $this->actingAs($this->superadmin())
            ->getJson(route('admin.respon.konten', ['page' => 2]))
            ->assertOk()
            ->json();

        $this->assertFalse($halaman2['hasMore']);
        $this->assertSame(2, $halaman2['halaman']);
        $this->assertStringContainsString('628000000016', $halaman2['html']);
    }
}

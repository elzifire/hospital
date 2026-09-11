<?php

namespace Tests\Feature;

use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Models\Pnpp;
use App\Models\Satker;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BroadcastDetailTest extends TestCase
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

    protected function buatLog(string $status = 'menunggu'): MessageLog
    {
        $satker = Satker::create(['kode' => 'TD-'.uniqid(), 'nama' => 'Satker Detail']);
        $pnpp = Pnpp::create(['nama' => 'Budi Santoso', 'nip' => '123-'.Str::random(6), 'satker_id' => $satker->id, 'no_hp' => '081234567890']);
        $template = MessageTemplate::create([
            'judul' => 'Template Detail',
            'channel' => 'WhatsApp',
            'konten' => 'Halo {nama}.',
            'is_active' => true,
            'meta_template_name' => 'promo_h1',
            'meta_language' => 'en_US',
        ]);

        return MessageLog::create([
            'jenis' => 'outreach',
            'rule' => 'h-7',
            'message_template_id' => $template->id,
            'pnpp_id' => $pnpp->id,
            'penerima_nama' => 'Budi Santoso',
            'penerima_no_hp' => '6281234567890',
            'konten' => 'Halo Budi Santoso.',
            'status' => $status,
            'template_params' => ['Budi Santoso'],
        ]);
    }

    #[Test]
    public function halaman_detail_bisa_diakses(): void
    {
        $log = $this->buatLog();

        $this->actingAs($this->superadmin())
            ->get(route('admin.broadcast.show', $log))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Detail Pesan');
    }

    #[Test]
    public function user_biasa_tidak_bisa_akses_detail(): void
    {
        $log = $this->buatLog();
        $user = User::where('email', 'user@gmail.com')->first();

        $this->actingAs($user)
            ->get(route('admin.broadcast.show', $log))
            ->assertForbidden();
    }

    #[Test]
    public function kirim_ulang_pesan_gagal_menjadi_terkirim(): void
    {
        $log = $this->buatLog('gagal');

        $this->actingAs($this->superadmin())
            ->post(route('admin.broadcast.kirim-ulang', $log))
            ->assertRedirect();

        $this->assertSame('terkirim', $log->refresh()->status);
        $this->assertNull($log->error);
    }

    #[Test]
    public function kirim_ulang_ditolak_untuk_pesan_bukan_gagal(): void
    {
        $log = $this->buatLog('terkirim');

        $this->actingAs($this->superadmin())
            ->post(route('admin.broadcast.kirim-ulang', $log))
            ->assertRedirect();

        // Status tidak berubah
        $this->assertSame('terkirim', $log->refresh()->status);
    }

    #[Test]
    public function kirim_sekarang_mengirim_pesan_menunggu(): void
    {
        $log1 = $this->buatLog('menunggu');
        $log2 = $this->buatLog('menunggu');

        $this->actingAs($this->superadmin())
            ->post(route('admin.broadcast.kirim-sekarang', 'outreach'))
            ->assertRedirect();

        $this->assertSame('terkirim', $log1->refresh()->status);
        $this->assertSame('terkirim', $log2->refresh()->status);
    }

    #[Test]
    public function kirim_sekarang_tidak_ada_menunggu(): void
    {
        $this->actingAs($this->superadmin())
            ->post(route('admin.broadcast.kirim-sekarang', 'outreach'))
            ->assertRedirect();

        // Tidak ada error — cukup redirect dengan flash
    }

    #[Test]
    public function kirim_sekarang_jenis_invalid_404(): void
    {
        $this->actingAs($this->superadmin())
            ->post(route('admin.broadcast.kirim-sekarang', 'tidak_ada'))
            ->assertNotFound();
    }

    #[Test]
    public function kirim_sekarang_tanpa_izini_403(): void
    {
        $user = User::where('email', 'user@gmail.com')->first();

        $this->actingAs($user)
            ->post(route('admin.broadcast.kirim-sekarang', 'outreach'))
            ->assertForbidden();
    }
}

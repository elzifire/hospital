<?php

namespace Tests\Feature;

use App\Models\MessageLog;
use App\Models\MessageReply;
use App\Models\Pnpp;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResponFollowUpViewTest extends TestCase
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

    #[Test]
    public function halaman_respon_menampilkan_balasan_dan_statistik(): void
    {
        MessageReply::create([
            'no_hp' => '6281234567890', 'nama' => 'Budi Santoso',
            'isi_pesan' => 'Baik dok, saya hadir.', 'waktu_masuk' => now(), 'driver' => 'waha',
        ]);
        MessageReply::create([
            'no_hp' => '6289999999999', 'nama' => null,
            'isi_pesan' => 'Siapa ini?', 'waktu_masuk' => now(), 'driver' => 'waha',
        ]);

        $this->actingAs($this->superadmin())
            ->get(route('admin.respon.index'))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Baik dok, saya hadir.')
            ->assertSee('Nomor Tak Dikenal');
    }

    #[Test]
    public function halaman_percakapan_menampilkan_pesan_keluar_dan_balasan(): void
    {
        $budi = Pnpp::create(['nama' => 'Budi Santoso', 'no_hp' => '081234567890']);

        MessageLog::create([
            'jenis' => 'follow_up', 'rule' => 'h-1', 'pnpp_id' => $budi->id, 'penerima_nama' => 'Budi Santoso',
            'penerima_no_hp' => '6281234567890', 'konten' => 'Jangan lupa kontrol besok ya.', 'status' => 'menunggu',
        ]);
        MessageReply::create([
            'pnpp_id' => $budi->id, 'no_hp' => '6281234567890', 'nama' => 'Budi Santoso',
            'isi_pesan' => 'Siap dok, saya hadir.', 'waktu_masuk' => now()->addMinute(), 'driver' => 'waha',
        ]);

        $this->actingAs($this->superadmin())
            ->get(route('admin.respon.show', '6281234567890'))
            ->assertOk()
            ->assertSee('Jangan lupa kontrol besok ya.')
            ->assertSee('Siap dok, saya hadir.')
            ->assertSee('Budi Santoso');
    }

    #[Test]
    public function halaman_riwayat_follow_up_hanya_menampilkan_jenis_follow_up(): void
    {
        $budi = Pnpp::create(['nama' => 'Budi Santoso', 'no_hp' => '081234567890']);

        MessageLog::create([
            'jenis' => 'follow_up', 'rule' => 'h-1', 'pnpp_id' => $budi->id, 'penerima_nama' => 'Budi Santoso',
            'penerima_no_hp' => '6281234567890', 'konten' => 'Follow up kabar Anda.', 'status' => 'terkirim',
        ]);
        MessageLog::create([
            'jenis' => 'outreach', 'rule' => 'h-7', 'pnpp_id' => $budi->id, 'penerima_nama' => 'Budi Santoso',
            'penerima_no_hp' => '6281234567890', 'konten' => 'Pesan outreach.', 'status' => 'terkirim',
        ]);

        $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.index'))
            ->assertOk()
            ->assertSee('Follow up kabar Anda.')
            ->assertDontSee('Pesan outreach.');
    }

    #[Test]
    public function user_biasa_ditolak_dari_modul_follow_up_dan_outreach(): void
    {
        $user = User::where('email', 'user@gmail.com')->firstOrFail();

        $this->actingAs($user)->get(route('admin.follow-up.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.follow-up.generate'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.outreach.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.outreach.generate'))->assertForbidden();
    }
}
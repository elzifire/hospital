<?php

namespace Tests\Feature;

use App\Models\MessageLog;
use App\Models\Pnpp;
use App\Models\Satker;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Kirim pesan manual (teks bebas) ke target kontak PNPP dari modul Respon —
 * pengiriman tanpa tipe template Meta (meta_template_name null) agar lebih
 * hemat biaya. Kontak dengan nomor WhatsApp tidak valid dilewati.
 */
class ResponPesanManualTest extends TestCase
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
        $satkerA = Satker::create(['nama' => 'Polresta Bogor']);
        $satkerB = Satker::create(['nama' => 'Polsek Bogor Timur']);

        $budi = Pnpp::create(['nama' => 'Budi Santoso', 'nip' => '85010101', 'no_hp' => '081234567890', 'satker_id' => $satkerA->id]);
        $siti = Pnpp::create(['nama' => 'Siti Aminah', 'nip' => '85020202', 'no_hp' => '081355557777', 'satker_id' => $satkerB->id]);
        $tanpaNomor = Pnpp::create(['nama' => 'Joko Widodo', 'nip' => '85030303', 'no_hp' => null, 'satker_id' => $satkerB->id]);

        return compact('satkerA', 'satkerB', 'budi', 'siti', 'tanpaNomor');
    }

    #[Test]
    public function admin_mengirim_pesan_manual_teks_bebas_ke_kontak_pnpp(): void
    {
        extract($this->pasangan());

        $this->actingAs($this->superadmin())
            ->from(route('admin.respon.pesan-manual'))
            ->post(route('admin.respon.pesan-manual-kirim'), [
                'pnpp_ids' => [$budi->id, $siti->id],
                'isi' => 'Kami ingatkan jadwal kontrol Anda besok.',
            ])
            ->assertRedirect(route('admin.respon.pesan-manual'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('message_logs', [
            'jenis' => 'respon',
            'rule' => 'pesan_manual',
            'pnpp_id' => $budi->id,
            'penerima_no_hp' => '6281234567890',
            'konten' => 'Kami ingatkan jadwal kontrol Anda besok.',
            'status' => 'terkirim',
        ]);
        $this->assertDatabaseHas('message_logs', [
            'jenis' => 'respon',
            'rule' => 'pesan_manual',
            'pnpp_id' => $siti->id,
            'penerima_no_hp' => '6281355557777',
            'status' => 'terkirim',
        ]);

        // Teks bebas → tanpa meta_template_name dan tanpa parameter template.
        $log = MessageLog::where('pnpp_id', $budi->id)->where('rule', 'pesan_manual')->firstOrFail();
        $this->assertNull($log->meta_template_name);
        $this->assertSame([], $log->template_params);

        // Semua pesan dalam satu grup (kirim_group sama).
        $grup = MessageLog::where('rule', 'pesan_manual')->pluck('kirim_group')->unique();
        $this->assertCount(1, $grup);
    }

    #[Test]
    public function kontak_dengan_nomor_tidak_valid_dilewati(): void
    {
        extract($this->pasangan());

        $this->actingAs($this->superadmin())
            ->from(route('admin.respon.pesan-manual'))
            ->post(route('admin.respon.pesan-manual-kirim'), [
                'pnpp_ids' => [$budi->id, $tanpaNomor->id],
                'isi' => 'Halo, ini RS Bhayangkara.',
            ])
            ->assertRedirect(route('admin.respon.pesan-manual'))
            ->assertSessionHas('success', fn ($pesan) => str_contains($pesan, 'dilewati'));

        $this->assertDatabaseHas('message_logs', [
            'pnpp_id' => $tanpaNomor->id,
            'rule' => 'pesan_manual',
            'status' => 'gagal',
            'error' => 'Nomor WhatsApp tidak valid.',
        ]);
    }

    #[Test]
    public function form_pesan_manual_menampilkan_kontak_target_dan_abaikan_pencarian(): void
    {
        extract($this->pasangan());

        $this->actingAs($this->superadmin())
            ->get(route('admin.respon.pesan-manual'))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Pesan Manual');

        $this->actingAs($this->superadmin())
            ->get(route('admin.respon.pesan-manual', ['q' => 'Siti']))
            ->assertOk()
            ->assertSee('Siti Aminah')
            ->assertDontSee('Budi Santoso');

        // Filter satker.
        $this->actingAs($this->superadmin())
            ->get(route('admin.respon.pesan-manual', ['satker' => $satkerA->id]))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Siti Aminah');
    }

    #[Test]
    public function form_pesan_manual_tanpa_target_ditolak(): void
    {
        $this->actingAs($this->superadmin())
            ->post(route('admin.respon.pesan-manual-kirim'), [
                'pnpp_ids' => [],
                'isi' => 'Pesan tanpa target.',
            ])
            ->assertSessionHasErrors('pnpp_ids');

        $this->actingAs($this->superadmin())
            ->post(route('admin.respon.pesan-manual-kirim'), [
                'pnpp_ids' => [999999],
                'isi' => 'Target tidak ada.',
            ])
            ->assertSessionHasErrors('pnpp_ids.*');

        $this->assertDatabaseCount('message_logs', 0);
    }

    #[Test]
    public function user_biasa_ditolak_dari_modul_respon_pesan_manual(): void
    {
        $user = User::where('email', 'user@gmail.com')->firstOrFail();

        $this->actingAs($user)->get(route('admin.respon.pesan-manual'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.respon.pesan-manual-kirim'), [
            'pnpp_ids' => [1],
            'isi' => 'Halo',
        ])->assertForbidden();
    }
}
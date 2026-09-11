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

class OutreachManualTest extends TestCase
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

    protected function userBiasa(): User
    {
        return User::where('email', 'user@gmail.com')->firstOrFail();
    }

    protected function buatPnpp(array $attrs = []): Pnpp
    {
        $satker = Satker::create(['kode' => 'TM-'.str()->random(6), 'nama' => 'Satker Manual']);

        return Pnpp::create(array_merge([
            'nama' => 'Budi Santoso',
            'nip' => '123-'.str()->random(6),
            'satker_id' => $satker->id,
            'no_hp' => '081234567890',
        ], $attrs));
    }

    protected function templateMetaDefault(): array
    {
        return [
            'nama' => (string) config('whatsapp.meta.fallback_template'),
            'bahasa' => (string) config('whatsapp.meta.fallback_language', 'en_US'),
        ];
    }

    #[Test]
    public function halaman_form_manual_bisa_diakses(): void
    {
        $this->actingAs($this->superadmin())
            ->get(route('admin.outreach.create'))
            ->assertOk();
    }

    #[Test]
    public function user_biasa_tidak_bisa_akses_form_manual(): void
    {
        $this->actingAs($this->userBiasa())
            ->get(route('admin.outreach.create'))
            ->assertForbidden();
    }

    #[Test]
    public function form_penerima_menyediakan_centang_semua(): void
    {
        $valid = $this->buatPnpp();
        $invalid = $this->buatPnpp(['nama' => 'Tanpa Nomor', 'nip' => '789', 'no_hp' => null]);

        $response = $this->actingAs($this->superadmin())
            ->get(route('admin.outreach.create'))
            ->assertOk()
            ->assertSee('Centang semua');

        // Pasien dengan nomor valid bisa dipilih lewat "Centang semua";
        // pasien tanpa nomor disorot sebagai tidak valid, bukan calon kirim.
        $html = $response->getContent();
        $checkbox = fn ($id) => '/name="pnpp_ids\[\]" value="'.preg_quote((string) $id, '/').'"/';

        $this->assertMatchesRegularExpression($checkbox($valid->id), $html);
        $this->assertDoesNotMatchRegularExpression($checkbox($invalid->id), $html);
        $this->assertStringContainsString('Centang semua', $html);
        $this->assertStringContainsString('nomor tidak valid', $html);
    }

    #[Test]
    public function kirim_manual_membuat_pesan_terkirim_dengan_template_default(): void
    {
        $pnpp = $this->buatPnpp();

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$pnpp->id],
                'jenis' => 'outreach',
            ])
            ->assertRedirect(route('admin.outreach.index'));

        $log = MessageLog::where('pnpp_id', $pnpp->id)->first();

        $this->assertNotNull($log);
        $this->assertSame('manual', $log->rule);
        $this->assertSame('terkirim', $log->status);
        $this->assertSame('Budi Santoso', $log->penerima_nama);
        $this->assertSame('6281234567890', $log->penerima_no_hp);
        $this->assertSame($this->templateMetaDefault()['nama'], $log->meta_template_name);
        $this->assertSame($this->templateMetaDefault()['bahasa'], $log->meta_language);
        $this->assertSame([], (array) $log->template_params);
        $this->assertNull($log->reminder_id);
    }

    #[Test]
    public function kirim_sekarang_abaikan_kirim_pada_kosong(): void
    {
        $pnpp = $this->buatPnpp();

        // Form mengirim kirim_pada='' (input always ada di form) saat mode
        // "sekarang" — harus tetap memproses, bukan error validasi.
        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$pnpp->id],
                'jenis' => 'outreach',
                'mode' => 'sekarang',
                'kirim_pada' => '',
            ])
            ->assertRedirect(route('admin.outreach.index'))
            ->assertSessionHas('success');

        $log = MessageLog::where('pnpp_id', $pnpp->id)->first();
        $this->assertNotNull($log);
        $this->assertSame('terkirim', $log->status);
        $this->assertNull($log->kirim_pada);
    }

    #[Test]
    public function kirim_manual_ke_banyak_pasien_sekaligus(): void
    {
        $budi = $this->buatPnpp();
        $siti = $this->buatPnpp(['nama' => 'Siti Aminah', 'nip' => '456', 'no_hp' => '085678912345']);

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$budi->id, $siti->id],
                'jenis' => 'outreach',
            ])
            ->assertRedirect(route('admin.outreach.index'));

        $this->assertSame(2, MessageLog::count());
        $this->assertSame('terkirim', MessageLog::where('pnpp_id', $budi->id)->first()->status);
        $this->assertSame('terkirim', MessageLog::where('pnpp_id', $siti->id)->first()->status);
        $this->assertSame('6285678912345', MessageLog::where('pnpp_id', $siti->id)->first()->penerima_no_hp);
    }

    #[Test]
    public function kirim_manual_pnpp_tanpa_hp_menghasilkan_gagal(): void
    {
        $pnpp = $this->buatPnpp(['no_hp' => null]);

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$pnpp->id],
                'jenis' => 'outreach',
            ])
            ->assertRedirect(route('admin.outreach.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('message_logs', [
            'pnpp_id' => $pnpp->id,
            'status' => 'gagal',
            'error' => 'Nomor WhatsApp pasien tidak valid.',
        ]);
    }

    #[Test]
    public function kirim_manual_follow_up_ditolak_tanpa_izini(): void
    {
        $pnpp = $this->buatPnpp();

        // Buat user khusus yang punya 'manage outreach' tapi TIDAK 'manage follow-up'
        $user = User::create([
            'name' => 'Tanpa Follow Up',
            'email' => 'tanpa-followup@test.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('user');
        $user->givePermissionTo('manage outreach');

        $this->actingAs($user)
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$pnpp->id],
                'jenis' => 'follow_up',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function kirim_manual_membutuhkan_pasien(): void
    {
        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [],
                'jenis' => 'outreach',
            ])
            ->assertSessionHasErrors('pnpp_ids');

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [99999],
                'jenis' => 'outreach',
            ])
            ->assertSessionHasErrors('pnpp_ids.0');
    }

    #[Test]
    public function jadwalkan_pesan_membuat_menunggu_dengan_waktu_kirim(): void
    {
        $pnpp = $this->buatPnpp();
        $kirimPada = now()->addHour()->format('Y-m-d\TH:i');

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$pnpp->id],
                'jenis' => 'outreach',
                'mode' => 'jadwalkan',
                'kirim_pada' => $kirimPada,
            ])
            ->assertRedirect(route('admin.outreach.index'))
            ->assertSessionHas('success');

        $log = MessageLog::where('pnpp_id', $pnpp->id)->first();

        $this->assertNotNull($log);
        $this->assertSame('manual', $log->rule);
        $this->assertSame('menunggu', $log->status);
        $this->assertNotNull($log->kirim_pada);
        $this->assertNull($log->sent_at);
    }

    #[Test]
    public function jadwalkan_wajib_isi_waktu_di_masa_depan(): void
    {
        $pnpp = $this->buatPnpp();

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$pnpp->id],
                'jenis' => 'outreach',
                'mode' => 'jadwalkan',
            ])
            ->assertSessionHasErrors('kirim_pada');

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$pnpp->id],
                'jenis' => 'outreach',
                'mode' => 'jadwalkan',
                'kirim_pada' => now()->subDay()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors('kirim_pada');

        $this->assertSame(0, MessageLog::count());
    }

    #[Test]
    public function pesan_manual_bisa_dilihat_di_detail(): void
    {
        $pnpp = $this->buatPnpp();

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$pnpp->id],
                'jenis' => 'outreach',
            ]);

        $log = MessageLog::where('pnpp_id', $pnpp->id)->first();

        $this->actingAs($this->superadmin())
            ->get(route('admin.broadcast.show', $log))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Manual');
    }
}
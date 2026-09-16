<?php

namespace Tests\Feature;

use App\Models\Dokter;
use App\Models\MessageLog;
use App\Models\MessageReply;
use App\Models\MessageTemplate;
use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\Reminder;
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

    protected function buatReminder(Pnpp $pnpp): Reminder
    {
        $poli = Poli::create(['kode' => 'POL-'.str()->random(4), 'nama' => 'Poli Umum']);
        $dokter = Dokter::create(['poli_id' => $poli->id, 'nama' => 'dr. Umum']);

        return Reminder::create([
            'pnpp_id' => $pnpp->id,
            'poli_id' => $poli->id,
            'dokter_id' => $dokter->id,
            'tanggal' => now()->format('Y-m-d'),
            'jam' => now()->format('H:i'),
            'status' => 'terjadwal',
        ]);
    }

    protected function buatTemplate(): MessageTemplate
    {
        return MessageTemplate::create([
            'judul' => 'Jadwal Poli',
            'kode' => 'TMP-'.str()->random(6),
            'channel' => 'WhatsApp',
            'konten' => 'Halo {nama}, jadwal Anda di {poli} pada {tanggal} pukul {jam}.',
            'is_active' => true,
            'meta_param_tokens' => ['nama', 'tanggal'],
            'meta_template_name' => 'jadwal_poli',
            'meta_language' => 'id',
        ]);
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
        $this->buatReminder($valid);

        $invalid = $this->buatPnpp(['nama' => 'Tanpa Nomor', 'nip' => '789', 'no_hp' => null]);
        $this->buatReminder($invalid);

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
    public function follow_up_hanya_menampilkan_pnpp_yang_belum_membalas(): void
    {
        $belumBalas = $this->buatPnpp();
        $this->buatReminder($belumBalas);

        $sudahBalas = $this->buatPnpp(['nama' => 'Sudah Balas', 'nip' => '555', 'no_hp' => '081299988877']);
        $this->buatReminder($sudahBalas);
        MessageReply::create([
            'pnpp_id' => $sudahBalas->id,
            'no_hp' => '6281299988877',
            'nama' => 'Sudah Balas',
            'isi_pesan' => 'Baik, saya hadir.',
            'waktu_masuk' => now(),
            'driver' => 'waha',
        ]);

        $response = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.create'))
            ->assertOk();

        $html = $response->getContent();
        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringNotContainsString('Sudah Balas', $html);
    }

    #[Test]
    public function outreach_masih_menampilkan_pnpp_yang_sudah_membalas(): void
    {
        $sudahBalas = $this->buatPnpp(['nama' => 'Sudah Balas', 'nip' => '555']);
        $this->buatReminder($sudahBalas);
        MessageReply::create([
            'pnpp_id' => $sudahBalas->id,
            'no_hp' => '6281234567890',
            'nama' => 'Sudah Balas',
            'isi_pesan' => 'Baik, saya hadir.',
            'waktu_masuk' => now(),
            'driver' => 'waha',
        ]);

        // Filter "belum membalas" khusus milik modul Follow Up — outreach
        // tetap menampilkan pasien yang sudah membalas.
        $url = route('admin.outreach.create').'?q='.urlencode('Sudah Balas');

        $this->actingAs($this->superadmin())
            ->get($url)
            ->assertOk()
            ->assertSee('Sudah Balas');
    }

    #[Test]
    public function filter_tanggal_menyaring_berdasarkan_jadwal(): void
    {
        $besok = $this->buatReminder($this->buatPnpp());
        $lusa = $this->buatReminder($this->buatPnpp(['nama' => 'Pasien Lusa', 'nip' => '777']));

        // Jadwal lusa diubah ke tanggal lusa agar berbeda dari besok.
        $besok->update(['tanggal' => now()->addDay()->format('Y-m-d')]);
        $lusa->update(['tanggal' => now()->addDays(2)->format('Y-m-d')]);

        $this->actingAs($this->superadmin())
            ->get(route('admin.outreach.create', [
                'tanggal' => now()->addDay()->format('Y-m-d'),
            ]))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Pasien Lusa');
    }

    #[Test]
    public function pnpp_tanpa_jadwal_tidak_tampil_di_form(): void
    {
        $this->buatPnpp(['nama' => 'Tanpa Jadwal', 'nip' => '888']);

        $this->actingAs($this->superadmin())
            ->get(route('admin.outreach.create'))
            ->assertOk()
            ->assertDontSee('Tanpa Jadwal');
    }

    #[Test]
    public function filter_tampilkan_semua_memunculkan_pnpp_tanpa_jadwal(): void
    {
        $berjadwal = $this->buatPnpp();
        $this->buatReminder($berjadwal);
        $tanpaJadwal = $this->buatPnpp(['nama' => 'Tanpa Jadwal', 'nip' => '888']);

        $this->actingAs($this->superadmin())
            ->get(route('admin.outreach.create', ['tampilkan' => 'semua']))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Tanpa Jadwal');
    }

    #[Test]
    public function filter_tampilkan_berjadwal_mengabaikan_filter_tanggal(): void
    {
        // Mode "semua" tidak ikut dibatasi by filter tanggal jadwal:
        // pasien tanpa jadwal tetap muncul walau tanggal disaring.
        $tanpaJadwal = $this->buatPnpp(['nama' => 'Tanpa Jadwal', 'nip' => '888']);

        $this->actingAs($this->superadmin())
            ->get(route('admin.outreach.create', [
                'tampilkan' => 'semua',
                'tanggal' => now()->addDay()->format('Y-m-d'),
            ]))
            ->assertOk()
            ->assertSee('Tanpa Jadwal');
    }

    #[Test]
    public function kirim_manual_membuat_pesan_terkirim_dengan_template(): void
    {
        $pnpp = $this->buatPnpp();
        $template = $this->buatTemplate();

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$pnpp->id],
                'jenis' => 'outreach',
                'message_template_id' => $template->id,
            ])
            ->assertRedirect(route('admin.outreach.index'));

        $log = MessageLog::where('pnpp_id', $pnpp->id)->first();

        $this->assertNotNull($log);
        $this->assertSame('manual', $log->rule);
        $this->assertSame('terkirim', $log->status);
        $this->assertSame('Budi Santoso', $log->penerima_nama);
        $this->assertSame('6281234567890', $log->penerima_no_hp);
        $this->assertSame($template->id, $log->message_template_id);
        $this->assertSame('jadwal_poli', $log->meta_template_name);
        $this->assertSame('id', $log->meta_language);
        $this->assertSame(['Budi Santoso', '{tanggal}'], (array) $log->template_params);
        $this->assertNull($log->reminder_id);
    }

    #[Test]
    public function kirim_manual_tanpa_template_ditolak(): void
    {
        $pnpp = $this->buatPnpp();

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$pnpp->id],
                'jenis' => 'outreach',
            ])
            ->assertSessionHasErrors('message_template_id');

        $this->assertSame(0, MessageLog::count());
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
                'message_template_id' => $this->buatTemplate()->id,
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
                'message_template_id' => $this->buatTemplate()->id,
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
                'message_template_id' => $this->buatTemplate()->id,
            ])
            ->assertRedirect(route('admin.outreach.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('message_logs', [
            'pnpp_id' => $pnpp->id,
            'status' => 'gagal',
            'error' => 'Pasien tidak memiliki nomor WhatsApp yang valid.',
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
                'message_template_id' => $this->buatTemplate()->id,
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
                'message_template_id' => $this->buatTemplate()->id,
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
                'message_template_id' => $this->buatTemplate()->id,
            ]);

        $log = MessageLog::where('pnpp_id', $pnpp->id)->first();

        $this->actingAs($this->superadmin())
            ->get(route('admin.broadcast.show', $log))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Manual');
    }
}

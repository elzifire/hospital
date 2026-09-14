<?php

namespace Tests\Feature;

use App\Models\Dokter;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\Reminder;
use App\Models\Satker;
use App\Models\TemplateCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OutreachTemplateTest extends TestCase
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

    protected function buatTemplate(?array $params = null): MessageTemplate
    {
        return MessageTemplate::create([
            'judul' => 'Jadwal Poli',
            'kode' => 'TMP-'.str()->random(6),
            'channel' => 'WhatsApp',
            'konten' => 'Halo {nama}, jadwal Anda di {poli} pada {tanggal} pukul {jam}.',
            'is_active' => true,
            'template_category_id' => TemplateCategory::where('slug', 'outreach')->firstOrFail()->id,
            'meta_param_tokens' => $params ?? ['nama', 'tanggal'],
            'meta_template_name' => 'jadwal_poli',
            'meta_language' => 'id',
        ]);
    }

    protected function jadwalkan(array $data): array
    {
        return array_merge([
            'pnpp_ids' => [],
            'jenis' => 'outreach',
            'mode' => 'jadwalkan',
            'kirim_pada' => now()->addHour()->format('Y-m-d\TH:i'),
        ], $data);
    }

    protected function buatTemplatePengingat(): MessageTemplate
    {
        return MessageTemplate::create([
            'judul' => 'Pengingat Kunjungan',
            'kode' => 'TMP-'.str()->random(6),
            'channel' => 'WhatsApp',
            'konten' => 'Yth. Bapak/Ibu {{nama}}, kunjungan Anda pada {{hari_tanggal}} pukul {{waktu_kunjungan}} di {{poli_layanan}}.',
            'is_active' => true,
            'meta_param_tokens' => ['nama', 'hari_tanggal', 'waktu_kunjungan', 'poli_layanan'],
            'meta_template_name' => 'pengingat_kunjungan_rsbhayangkara_v1',
            'meta_language' => 'id',
        ]);
    }

    protected function buatReminder(Pnpp $pnpp): Reminder
    {
        $poli = Poli::create(['kode' => 'POL-'.str()->random(4), 'nama' => 'Poli Mata']);
        $dokter = Dokter::create(['poli_id' => $poli->id, 'nama' => 'dr. Sari']);

        return Reminder::create([
            'pnpp_id' => $pnpp->id,
            'poli_id' => $poli->id,
            'dokter_id' => $dokter->id,
            'tanggal' => '2026-09-20',
            'jam' => '09:30',
            'home_visit' => false,
            'status' => 'terjadwal',
        ]);
    }

    #[Test]
    public function halaman_form_menampilkan_pilihan_template_dan_variabel(): void
    {
        $template = $this->buatTemplate();

        $this->actingAs($this->superadmin())
            ->get(route('admin.outreach.create'))
            ->assertOk()
            ->assertDontSee('Pesan standar (tanpa template)')
            ->assertSee('Pilih template')
            ->assertSee($template->judul)
            ->assertSee('Variabel pesan')
            ->assertSee('Centang semua');
    }

    #[Test]
    public function kirim_dengan_template_dan_variabel_manual(): void
    {
        $pnpp = $this->buatPnpp();
        $template = $this->buatTemplate();

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$pnpp->id],
                'jenis' => 'outreach',
                'message_template_id' => $template->id,
                'vars' => ['tanggal' => '2026-01-01'],
            ])
            ->assertRedirect(route('admin.outreach.index'));

        $log = MessageLog::where('pnpp_id', $pnpp->id)->first();

        $this->assertNotNull($log);
        $this->assertSame($template->id, $log->message_template_id);
        $this->assertStringContainsString('Halo Budi Santoso', $log->konten);
        $this->assertStringContainsString('2026-01-01', $log->konten);
        $this->assertSame('jadwal_poli', $log->meta_template_name);
        $this->assertSame('id', $log->meta_language);
        $this->assertSame(['Budi Santoso', '2026-01-01'], (array) $log->template_params);
        $this->assertSame('terkirim', $log->status);
        $this->assertNotNull($log->kirim_group);
    }

    #[Test]
    public function kirim_dengan_template_tanpa_variabel_pakai_data_pasien(): void
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

        $this->assertStringContainsString('Halo Budi Santoso', $log->konten);
        $this->assertSame(['Budi Santoso', '{tanggal}'], (array) $log->template_params);
    }

    #[Test]
    public function variabel_manual_dengan_emoji_dibersihkan_sebelum_disimpan(): void
    {
        $pnpp = $this->buatPnpp();
        $template = $this->buatTemplate();

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$pnpp->id],
                'jenis' => 'outreach',
                'message_template_id' => $template->id,
                'vars' => ['nama' => 'Budi ✅ Santoso'],
            ])
            ->assertRedirect(route('admin.outreach.index'));

        $log = MessageLog::where('pnpp_id', $pnpp->id)->first();

        $this->assertStringContainsString('Budi [OK] Santoso', $log->konten);
        $this->assertSame(['Budi [OK] Santoso', '{tanggal}'], (array) $log->template_params);
    }

    #[Test]
    public function kirim_manual_satu_sesi_memakai_kirim_group_yang_sama(): void
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

        $this->assertSame(1, MessageLog::whereNotNull('kirim_group')->distinct()->count('kirim_group'));
    }

    #[Test]
    public function kirim_tanpa_template_ditolak(): void
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
    public function halaman_form_menampilkan_dropdown_jadwal_untuk_template_pengingat(): void
    {
        $pnpp = $this->buatPnpp();
        $reminder = $this->buatReminder($pnpp);
        $this->buatTemplatePengingat();

        $this->actingAs($this->superadmin())
            ->get(route('admin.outreach.create'))
            ->assertOk()
            ->assertSee('Pratinjau per Penerima')
            ->assertSee('Referensi jadwal Digital Reminder')
            ->assertSee('09:30')
            ->assertSee('Poli Mata');
    }

    #[Test]
    public function kirim_template_pengingat_mengisi_variabel_dari_jadwal_digital_reminder(): void
    {
        $pnpp = $this->buatPnpp();
        $template = $this->buatTemplatePengingat();
        $reminder = $this->buatReminder($pnpp);

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$pnpp->id],
                'jenis' => 'outreach',
                'message_template_id' => $template->id,
                'reminder_ids' => [$pnpp->id => $reminder->id],
            ])
            ->assertRedirect(route('admin.outreach.index'));

        $log = MessageLog::where('pnpp_id', $pnpp->id)->first();

        $this->assertNotNull($log);
        $this->assertSame((int) $reminder->id, (int) $log->reminder_id);

        $this->assertSame([
            'Budi Santoso',
            $reminder->tanggal?->locale('id')->translatedFormat('l, d F Y'),
            $reminder->jam?->format('H:i'),
            'Poli Mata',
        ], (array) $log->template_params);

        // Kurung ganda {{token}} terender rapi tanpa suntingan kawat.
        $this->assertStringContainsString('Yth. Bapak/Ibu Budi Santoso', $log->konten);
        $this->assertStringContainsString((string) $reminder->tanggal?->locale('id')->translatedFormat('l, d F Y'), $log->konten);
        $this->assertStringNotContainsString('{Budi', $log->konten);
    }

    #[Test]
    public function referensi_jadwal_milik_pasien_lain_diabaikan(): void
    {
        $budi = $this->buatPnpp();
        $siti = $this->buatPnpp(['nama' => 'Siti Aminah', 'nip' => '456', 'no_hp' => '085678912345']);
        $template = $this->buatTemplatePengingat();
        $reminderSiti = $this->buatReminder($siti);

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$budi->id],
                'jenis' => 'outreach',
                'message_template_id' => $template->id,
                'reminder_ids' => [$budi->id => $reminderSiti->id],
            ])
            ->assertRedirect(route('admin.outreach.index'));

        $log = MessageLog::where('pnpp_id', $budi->id)->first();

        $this->assertNull($log->reminder_id);
        $this->assertSame(['Budi Santoso', '{hari_tanggal}', '{waktu_kunjungan}', '{poli_layanan}'], (array) $log->template_params);
    }

    #[Test]
    public function halaman_ubah_grup_menunggu_bisa_diakses(): void
    {
        $budi = $this->buatPnpp();
        $siti = $this->buatPnpp(['nama' => 'Siti Aminah', 'nip' => '456']);

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), $this->jadwalkan([
                'pnpp_ids' => [$budi->id, $siti->id],
                'message_template_id' => $this->buatTemplate()->id,
            ]));

        $grup = MessageLog::where('pnpp_id', $budi->id)->first()->kirim_group;

        $response = $this->actingAs($this->superadmin())
            ->get(route('admin.outreach.edit', $grup))
            ->assertOk()
            ->assertSee('Ubah Pesan Outreach')
            ->assertSee('Budi Santoso')
            ->assertSee('Siti Aminah');

        // Target lama tetap ikut tampil walau tanpa filter.
        $this->assertStringContainsString('name="pnpp_ids[]" value="'.$budi->id.'"', $response->getContent());
        $this->assertStringContainsString('name="pnpp_ids[]" value="'.$siti->id.'"', $response->getContent());
    }

    #[Test]
    public function ubah_target_template_dan_variabel(): void
    {
        $budi = $this->buatPnpp();
        $siti = $this->buatPnpp(['nama' => 'Siti Aminah', 'nip' => '456']);
        $amin = $this->buatPnpp(['nama' => 'Amin Rais', 'nip' => '789']);

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), $this->jadwalkan([
                'pnpp_ids' => [$budi->id, $siti->id],
                'message_template_id' => $this->buatTemplate()->id,
            ]));

        $budiLog = MessageLog::where('pnpp_id', $budi->id)->first();
        $grup = $budiLog->kirim_group;

        $this->actingAs($this->superadmin())
            ->put(route('admin.outreach.update', $grup), [
                'pnpp_ids' => [$budi->id, $amin->id],
                'message_template_id' => $budiLog->message_template_id,
                'vars' => ['tanggal' => '2026-02-02'],
            ])
            ->assertRedirect(route('admin.outreach.index'));

        // Target dipertahankan → konten & variabel diperbarui.
        $budiLog->refresh();
        $this->assertStringContainsString('2026-02-02', $budiLog->konten);
        $this->assertSame(['Budi Santoso', '2026-02-02'], (array) $budiLog->template_params);
        $this->assertSame('menunggu', $budiLog->status);

        // Target dilepas → dibatalkan.
        $sitiLog = MessageLog::where('pnpp_id', $siti->id)->first();
        $this->assertSame('dibatalkan', $sitiLog->status);

        // Target baru → satu pesan baru di grup yang sama.
        $aminLog = MessageLog::where('pnpp_id', $amin->id)->first();
        $this->assertNotNull($aminLog);
        $this->assertSame($grup, $aminLog->kirim_group);
        $this->assertSame('menunggu', $aminLog->status);
        $this->assertStringContainsString('2026-02-02', $aminLog->konten);
        $this->assertSame($budiLog->kirim_pada?->toDateTimeString(), $aminLog->kirim_pada?->toDateTimeString());
    }

    #[Test]
    public function grup_yang_sudah_terkirim_tidak_bisa_diubah(): void
    {
        $pnpp = $this->buatPnpp();

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$pnpp->id],
                'jenis' => 'outreach',
                'message_template_id' => $this->buatTemplate()->id,
            ]);

        $grup = MessageLog::where('pnpp_id', $pnpp->id)->first()->kirim_group;

        $this->actingAs($this->superadmin())
            ->get(route('admin.outreach.edit', $grup))
            ->assertForbidden();

        $this->actingAs($this->superadmin())
            ->put(route('admin.outreach.update', $grup), [
                'pnpp_ids' => [$pnpp->id],
                'message_template_id' => $this->buatTemplate()->id,
            ])
            ->assertForbidden();
    }

    #[Test]
    public function user_biasa_tidak_bisa_mengakses_halaman_ubah(): void
    {
        $pnpp = $this->buatPnpp();

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), $this->jadwalkan([
                'pnpp_ids' => [$pnpp->id],
                'message_template_id' => $this->buatTemplate()->id,
            ]));

        $grup = MessageLog::where('pnpp_id', $pnpp->id)->first()->kirim_group;

        $user = User::create([
            'name' => 'Biasa',
            'email' => 'biasa-outreach@test.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('user');

        $this->actingAs($user)
            ->get(route('admin.outreach.edit', $grup))
            ->assertForbidden();
    }
}

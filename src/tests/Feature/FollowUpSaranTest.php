<?php

namespace Tests\Feature;

use App\Models\Dokter;
use App\Models\Kunjungan;
use App\Models\MessageLog;
use App\Models\MessageReply;
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

class FollowUpSaranTest extends TestCase
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

    protected function buatReminder(Pnpp $pnpp, ?string $tanggal = null, string $status = 'terjadwal', ?int $templateId = null, array $ekstra = []): Reminder
    {
        $poli = Poli::create(['kode' => 'POL-'.str()->random(4), 'nama' => 'Poli Umum']);
        $dokter = Dokter::create(['poli_id' => $poli->id, 'nama' => 'dr. Umum']);

        return Reminder::create(array_merge([
            'pnpp_id' => $pnpp->id,
            'poli_id' => $poli->id,
            'dokter_id' => $dokter->id,
            'tanggal' => $tanggal ?? now()->format('Y-m-d'),
            'jam' => now()->format('H:i'),
            'status' => $status,
            'message_template_id' => $templateId,
        ], $ekstra));
    }

    protected function buatTemplate(?int $categoryId = null): MessageTemplate
    {
        return MessageTemplate::create([
            'judul' => 'Jadwal Poli',
            'kode' => 'TMP-'.str()->random(6),
            'channel' => 'WhatsApp',
            'konten' => 'Halo {nama}, jadwal Anda di {poli} pada {tanggal} pukul {jam}.',
            'is_active' => true,
            'template_category_id' => $categoryId,
            'meta_param_tokens' => ['nama', 'tanggal'],
            'meta_template_name' => 'jadwal_poli',
            'meta_language' => 'id',
        ]);
    }

    protected function kirimOutreachHariIni(Pnpp $pnpp, ?MessageTemplate $template = null): void
    {
        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$pnpp->id],
                'jenis' => 'outreach',
                'message_template_id' => ($template ?? $this->buatTemplate())->id,
            ])
            ->assertRedirect(route('admin.outreach.index'));
    }

    /**
     * Ambil dan decode data Alpine yang dirender sebagai window.alpineOutreachData.
     *
     * @return array<string, mixed>
     */
    protected function ambilDataAlpine(string $html): array
    {
        if (preg_match('/window\.alpineOutreachData\s*=\s*(\{.*?\});\s*<\/script>/s', $html, $m) !== 1) {
            return [];
        }

        return json_decode($m[1], true) ?? [];
    }

    #[Test]
    public function follow_up_menyarankan_pasien_dioutreach_hari_ini_belum_balas(): void
    {
        $belumBalas = $this->buatPnpp();
        $this->buatReminder($belumBalas);
        $sudahBalas = $this->buatPnpp(['nama' => 'Sudah Balas', 'nip' => '777', 'no_hp' => '081299988877']);
        $this->buatReminder($sudahBalas);

        $this->kirimOutreachHariIni($belumBalas);
        $this->kirimOutreachHariIni($sudahBalas);

        MessageReply::create([
            'pnpp_id' => $sudahBalas->id,
            'no_hp' => '6281299988877',
            'nama' => 'Sudah Balas',
            'isi_pesan' => 'Baik, saya hadir.',
            'waktu_masuk' => now(),
            'driver' => 'waha',
        ]);

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringContainsString('Saran Follow Up', $html);
        $this->assertStringContainsString('Outreach Belum Dibalas', $html);

        $data = $this->ambilDataAlpine($html);
        $this->assertCount(1, $data['saran']);
        // Reminder terjadwal hari ini tanpa kunjungan → juga "belum berkunjung".
        $this->assertSame(['belum_berkunjung', 'outreach_belum_balas'], $data['saran'][0]['kategori']);
        $this->assertStringContainsString('belum berkunjung.', $data['saran'][0]['alasan'][0]);
        $this->assertStringContainsString('belum dibalas.', $data['saran'][0]['alasan'][1]);
        // Yang sudah membalas tidak masuk saran dan sudah dibuang dari
        // daftar penerima oleh saringan "belum membalas".
        $this->assertStringNotContainsString('Sudah Balas', $html);
    }

    #[Test]
    public function pasien_jadwal_lewat_tanpa_kunjungan_disaran_untuk_follow_up(): void
    {
        $pnpp = $this->buatPnpp();
        $this->buatReminder($pnpp, now()->subDays(2)->format('Y-m-d'), 'tidak_datang');

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Saran Follow Up', $html);
        $this->assertStringContainsString('Belum Hadir', $html);
        $this->assertStringContainsString('lewat tanpa kunjungan.', $html);
        $this->assertStringContainsString('belum_hadir', $html);
        $this->assertStringContainsString('Budi Santoso', $html);
    }

    #[Test]
    public function pasien_dengan_dua_kriteria_tampil_sekali_dengan_dua_alasan(): void
    {
        $pnpp = $this->buatPnpp();
        $this->buatReminder($pnpp, now()->subDays(2)->format('Y-m-d'), 'tidak_datang');
        $this->kirimOutreachHariIni($pnpp);

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.create'))
            ->assertOk()
            ->getContent();

        // Satu kartu: kedua kategori & alasan tergabung dalam satu entri.
        $data = $this->ambilDataAlpine($html);
        $this->assertCount(1, $data['saran']);
        $this->assertSame(['belum_hadir', 'outreach_belum_balas'], $data['saran'][0]['kategori']);
        $this->assertCount(2, $data['saran'][0]['alasan']);
        $this->assertStringContainsString('lewat tanpa kunjungan.', $data['saran'][0]['alasan'][0]);
        $this->assertStringContainsString('belum dibalas.', $data['saran'][0]['alasan'][1]);
        $this->assertStringContainsString('Belum Hadir', $html);
    }

    #[Test]
    public function halaman_ubah_follow_up_juga_menampilkan_saran_follow_up(): void
    {
        $pnpp = $this->buatPnpp();
        $this->buatReminder($pnpp, now()->subDays(2)->format('Y-m-d'), 'tidak_datang');

        // Buat grup follow-up dengan mode jadwalkan sehingga tetap "menunggu".
        $this->actingAs($this->superadmin())
            ->post(route('admin.follow-up.store'), [
                'pnpp_ids' => [$pnpp->id],
                'jenis' => 'follow_up',
                'mode' => 'jadwalkan',
                'kirim_pada' => now()->addHour()->format('Y-m-d\TH:i'),
                'message_template_id' => $this->buatTemplate()->id,
            ])
            ->assertRedirect(route('admin.follow-up.index'));

        $group = MessageLog::where('pnpp_id', $pnpp->id)->firstOrFail()->kirim_group;

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.edit', $group))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Saran Follow Up', $html);
        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringContainsString('lewat tanpa kunjungan.', $html);
    }

    #[Test]
    public function tanpa_outreach_hari_ini_tidak_ada_saran(): void
    {
        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Saran Follow Up', $html);
    }

    #[Test]
    public function pasien_sudah_difollow_up_hari_ini_tidak_disaran_ulang(): void
    {
        $pnpp = $this->buatPnpp();
        $this->buatReminder($pnpp);
        $this->kirimOutreachHariIni($pnpp);

        $this->actingAs($this->superadmin())
            ->post(route('admin.follow-up.store'), [
                'pnpp_ids' => [$pnpp->id],
                'jenis' => 'follow_up',
                'message_template_id' => $this->buatTemplate()->id,
            ])
            ->assertRedirect(route('admin.follow-up.index'));

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.create'))
            ->assertOk()
            ->getContent();

        // Pasien tetap tampil di daftar penerima (belum membalas) namun
        // tidak lagi disarankan karena sudah di-follow up hari ini.
        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringNotContainsString('Saran Follow Up', $html);
        $this->assertSame([], $this->ambilDataAlpine($html)['saran']);
    }

    #[Test]
    public function hanya_outreach_terkirim_yang_memicu_saran(): void
    {
        $menunggu = $this->buatPnpp(['nama' => 'Masih Menunggu', 'nip' => '101']);
        // Jadwal sudah lewat & selesai → bukan belum_hadir, bukan belum_berkunjung.
        $this->buatReminder($menunggu, now()->subDays(2)->format('Y-m-d'), 'selesai');

        // Pesan belum terkirim (menunggu) — belum sampai ke pasien.
        MessageLog::create([
            'jenis' => 'outreach', 'rule' => 'manual', 'pnpp_id' => $menunggu->id,
            'penerima_nama' => 'Masih Menunggu', 'penerima_no_hp' => '6281234567890',
            'konten' => 'Pesan belum terkirim', 'status' => 'menunggu', 'sent_at' => null,
            'kirim_group' => (string) str()->uuid(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Saran Follow Up', $html);
    }

    #[Test]
    public function form_outreach_dan_follow_up_menampilkan_semua_template_aktif(): void
    {
        $outreach = $this->buatTemplate(TemplateCategory::where('slug', 'outreach')->firstOrFail()->id);
        $outreach->update(['judul' => 'Template Khusus Outreach']);
        $followUp = $this->buatTemplate(TemplateCategory::where('slug', 'follow-up')->firstOrFail()->id);
        $followUp->update(['judul' => 'Template Khusus Follow Up']);

        $outreachHtml = $this->actingAs($this->superadmin())
            ->get(route('admin.outreach.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Template Khusus Outreach', $outreachHtml);
        $this->assertStringContainsString('Template Khusus Follow Up', $outreachHtml);

        $followUpHtml = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Template Khusus Outreach', $followUpHtml);
        $this->assertStringContainsString('Template Khusus Follow Up', $followUpHtml);
    }

    #[Test]
    public function index_follow_up_menampilkan_saran_dengan_dua_tab(): void
    {
        $belumHadir = $this->buatPnpp();
        $this->buatReminder($belumHadir, now()->subDays(2)->format('Y-m-d'), 'tidak_datang');

        $belumBerkunjung = $this->buatPnpp(['nama' => 'Citra Dewi', 'no_hp' => '081255566677']);
        $this->buatReminder($belumBerkunjung, now()->addDays(1)->format('Y-m-d'));

        $outreach = $this->buatPnpp(['nama' => 'Rina Antika', 'no_hp' => '081277788899']);
        $this->kirimOutreachHariIni($outreach);

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Saran Follow Up', $html);
        $this->assertStringContainsString('Belum Hadir (1)', $html);
        $this->assertStringContainsString('Belum Berkunjung (1)', $html);
        $this->assertStringContainsString('Outreach Belum Dibalas (1)', $html);
        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringContainsString('Citra Dewi', $html);
        $this->assertStringContainsString('Rina Antika', $html);
        $this->assertStringContainsString('lewat tanpa kunjungan.', $html);
        $this->assertStringContainsString('belum berkunjung.', $html);
        $this->assertStringContainsString('belum dibalas.', $html);
        $this->assertStringContainsString('sasar=belum_hadir', $html);
        $this->assertStringContainsString('sasar=belum_berkunjung', $html);
        $this->assertStringContainsString('sasar=outreach_belum_balas', $html);
    }

    #[Test]
    public function create_follow_up_menangkap_hook_sasar_dan_preselect_penerima(): void
    {
        $belumHadir = $this->buatPnpp();
        $this->buatReminder($belumHadir, now()->subDays(2)->format('Y-m-d'), 'tidak_datang');

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.create', ['sasar' => 'belum_hadir']))
            ->assertOk()
            ->getContent();

        $data = $this->ambilDataAlpine($html);
        $this->assertSame([(int) $belumHadir->id], $data['selected']);
        $this->assertContains((int) $belumHadir->id, $data['allIds']);

        $outreach = $this->buatPnpp(['nama' => 'Rina Antika', 'no_hp' => '081277788899']);
        $this->kirimOutreachHariIni($outreach);

        $htmlOutreach = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.create', ['sasar' => 'outreach_belum_balas']))
            ->assertOk()
            ->getContent();

        $dataOutreach = $this->ambilDataAlpine($htmlOutreach);
        $this->assertSame([(int) $outreach->id], $dataOutreach['selected']);
        $this->assertContains((int) $outreach->id, $dataOutreach['allIds']);

        $belumBerkunjung = $this->buatPnpp(['nama' => 'Citra Dewi', 'no_hp' => '081255566677']);
        $this->buatReminder($belumBerkunjung, now()->addDays(1)->format('Y-m-d'));

        $htmlBerkunjung = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.create', ['sasar' => 'belum_berkunjung']))
            ->assertOk()
            ->getContent();

        $dataBerkunjung = $this->ambilDataAlpine($htmlBerkunjung);
        $this->assertSame([(int) $belumBerkunjung->id], $dataBerkunjung['selected']);
        $this->assertContains((int) $belumBerkunjung->id, $dataBerkunjung['allIds']);

        $polos = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.create'))
            ->assertOk()
            ->getContent();

        $this->assertSame([], $this->ambilDataAlpine($polos)['selected']);
    }

    #[Test]
    public function pasien_jadwal_hari_ini_belum_kunjungan_disaran_berkunjung(): void
    {
        $pnpp = $this->buatPnpp(['nama' => 'Citra Dewi', 'no_hp' => '081255566677']);
        $this->buatReminder($pnpp, now()->format('Y-m-d'));

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Saran Follow Up', $html);
        $this->assertStringContainsString('Belum Berkunjung', $html);
        $this->assertStringContainsString('belum berkunjung.', $html);
        $this->assertStringContainsString('Citra Dewi', $html);

        $data = $this->ambilDataAlpine($html);
        $this->assertCount(1, $data['saran']);
        $this->assertSame(['belum_berkunjung'], $data['saran'][0]['kategori']);
    }

    #[Test]
    public function pasien_yang_sudah_mencatat_kunjungan_tidak_disaran_belum_berkunjung(): void
    {
        $pnpp = $this->buatPnpp(['nama' => 'Citra Dewi', 'no_hp' => '081255566677']);
        $reminder = $this->buatReminder($pnpp, now()->format('Y-m-d'));

        Kunjungan::create([
            'pnpp_id' => $pnpp->id,
            'reminder_id' => $reminder->id,
            'tanggal_kunjungan' => now()->format('Y-m-d'),
        ]);

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Saran Follow Up', $html);
        $this->assertSame([], $this->ambilDataAlpine($html)['saran']);
    }

    #[Test]
    public function saran_menyertakan_label_template_sumber(): void
    {
        $tplJadwal = $this->buatTemplate();
        $tplJadwal->update(['judul' => 'Template Jadwal A']);
        $pasienJadwal = $this->buatPnpp(['nama' => 'Jadwal A', 'no_hp' => '081200000001']);
        $this->buatReminder($pasienJadwal, now()->format('Y-m-d'), 'terjadwal', $tplJadwal->id);

        $pasienOutreach = $this->buatPnpp(['nama' => 'Outreach X', 'no_hp' => '081200000002']);
        $tplOutreach = $this->buatTemplate();
        $tplOutreach->update(['judul' => 'Template Outreach X']);
        $this->kirimOutreachHariIni($pasienOutreach, $tplOutreach);

        $pasienTanpa = $this->buatPnpp(['nama' => 'Tanpa Tpl', 'no_hp' => '081200000003']);
        $this->buatReminder($pasienTanpa, now()->format('Y-m-d'));

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.create'))
            ->assertOk()
            ->getContent();

        $perNama = collect($this->ambilDataAlpine($html)['saran'])->keyBy('nama');
        $this->assertSame(['Template Jadwal A'], $perNama['Jadwal A']['template']);
        $this->assertSame(['Template Outreach X'], $perNama['Outreach X']['template']);
        $this->assertSame(['Tanpa template'], $perNama['Tanpa Tpl']['template']);
    }

    #[Test]
    public function index_menyaring_saran_berdasarkan_template(): void
    {
        $tplA = $this->buatTemplate();
        $tplA->update(['judul' => 'Template A']);
        $pasienA = $this->buatPnpp(['nama' => 'Pasien A', 'no_hp' => '081211111111']);
        $this->buatReminder($pasienA, now()->format('Y-m-d'), 'terjadwal', $tplA->id);

        $tplB = $this->buatTemplate();
        $tplB->update(['judul' => 'Template B']);
        $pasienB = $this->buatPnpp(['nama' => 'Pasien B', 'no_hp' => '081222222222']);
        $this->buatReminder($pasienB, now()->format('Y-m-d'), 'terjadwal', $tplB->id);

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.index', ['template' => 'Template A']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Pasien A', $html);
        $this->assertStringNotContainsString('Pasien B', $html);
        // Daftar pilihan template tetap menampilkan keduanya.
        $this->assertStringContainsString('Template A (1)', $html);
        $this->assertStringContainsString('Template B (1)', $html);
    }

    #[Test]
    public function create_hook_sasar_dengan_template_memilih_subset_saja(): void
    {
        $tplA = $this->buatTemplate();
        $tplA->update(['judul' => 'Template A']);
        $pasienA = $this->buatPnpp(['nama' => 'Pasien A', 'no_hp' => '081211111111']);
        $this->buatReminder($pasienA, now()->format('Y-m-d'), 'terjadwal', $tplA->id);

        $tplB = $this->buatTemplate();
        $tplB->update(['judul' => 'Template B']);
        $pasienB = $this->buatPnpp(['nama' => 'Pasien B', 'no_hp' => '081222222222']);
        $this->buatReminder($pasienB, now()->format('Y-m-d'), 'terjadwal', $tplB->id);

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.create', ['sasar' => 'belum_berkunjung', 'template' => 'Template A']))
            ->assertOk()
            ->getContent();

        $data = $this->ambilDataAlpine($html);
        $this->assertSame([(int) $pasienA->id], $data['selected']);
        $this->assertNotContains((int) $pasienB->id, $data['selected']);
    }

    #[Test]
    public function home_visit_tidak_masuk_saran_follow_up(): void
    {
        $rs = $this->buatPnpp();
        $this->buatReminder($rs, now()->subDays(2)->format('Y-m-d'), 'tidak_datang');

        $rsBerkunjung = $this->buatPnpp(['nama' => 'Gun Gun RS', 'no_hp' => '081244433322']);
        $this->buatReminder($rsBerkunjung, now()->format('Y-m-d'));

        $homeHadir = $this->buatPnpp(['nama' => 'Euis Home', 'no_hp' => '081266655544']);
        $this->buatReminder($homeHadir, now()->subDays(2)->format('Y-m-d'), 'tidak_datang', null, [
            'home_visit' => true,
            'poli_id' => null,
        ]);

        $homeBerkunjung = $this->buatPnpp(['nama' => 'Fahmi Home', 'no_hp' => '081277788866']);
        $this->buatReminder($homeBerkunjung, now()->format('Y-m-d'), 'terjadwal', null, [
            'home_visit' => true,
            'poli_id' => null,
        ]);

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.create'))
            ->assertOk()
            ->getContent();

        // Hanya jadwal kunjungan RS yang disaran; home visit dikecualikan.
        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringContainsString('Gun Gun RS', $html);
        $this->assertStringNotContainsString('Euis Home', $html);
        $this->assertStringNotContainsString('Fahmi Home', $html);

        $data = $this->ambilDataAlpine($html);
        $this->assertCount(2, $data['saran']);
        $this->assertNotContains((int) $homeHadir->id, $data['saran']);
        $this->assertNotContains((int) $homeBerkunjung->id, $data['saran']);

        // Daftar penerima manual juga tidak memuat jadwal home visit.
        $this->assertContains((int) $rsBerkunjung->id, $data['allIds']);
        $this->assertNotContains((int) $homeBerkunjung->id, $data['allIds']);
    }

    #[Test]
    public function saran_index_mengecualikan_home_visit_dari_tab_belum_hadir(): void
    {
        $this->buatReminder($this->buatPnpp(), now()->subDays(2)->format('Y-m-d'), 'tidak_datang');
        $this->buatReminder(
            $this->buatPnpp(['nama' => 'Hendra Home', 'no_hp' => '081299900011']),
            now()->subDays(2)->format('Y-m-d'),
            'tidak_datang',
            null,
            ['home_visit' => true, 'poli_id' => null],
        );

        $html = $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Hendra Home', $html);
        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringContainsString('Belum Hadir (1)', $html);
    }
}

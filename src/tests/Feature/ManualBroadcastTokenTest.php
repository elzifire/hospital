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

class ManualBroadcastTokenTest extends TestCase
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

    /**
     * Template dengan variable Meta custom berpenulisan rapi ({{ token }})
     * DAN tanpa meta_param_tokens — token diekstrak dari konten supaya
     * penormalan kurung di uji sekaligus.
     */
    protected function buatTemplateAlias(): MessageTemplate
    {
        return MessageTemplate::create([
            'judul' => 'Info Pasien',
            'kode' => 'TMP-'.str()->random(6),
            'channel' => 'WhatsApp',
            'konten' => 'Halo {{ nama_pasien }}, NIP Anda {nip_pasien}, nomor {nomor_hp}.',
            'is_active' => true,
            'template_category_id' => TemplateCategory::where('slug', 'outreach')->firstOrFail()->id,
        ]);
    }

    protected function buatTemplateGabungan(): MessageTemplate
    {
        return MessageTemplate::create([
            'judul' => 'Jadwal Gabungan',
            'kode' => 'TMP-'.str()->random(6),
            'channel' => 'WhatsApp',
            'konten' => "Hari/Tanggal: {hari_tanggal}\nWaktu: {waktu_kunjungan}\nPoli/Layanan: {poli_layanan}",
            'is_active' => true,
            'template_category_id' => TemplateCategory::where('slug', 'outreach')->firstOrFail()->id,
            'meta_param_tokens' => ['hari_tanggal', 'waktu_kunjungan', 'poli_layanan'],
            'meta_template_name' => 'jadwal_gabungan',
            'meta_language' => 'id',
        ]);
    }

    protected function buatReminder(Pnpp $pnpp, string $poliNama, string $tanggal = '2026-09-17', string $jam = '09:30'): Reminder
    {
        $poli = Poli::create(['kode' => 'POL-'.str()->random(4), 'nama' => $poliNama]);
        $dokter = Dokter::create(['poli_id' => $poli->id, 'nama' => 'dr. Sari']);

        return Reminder::create([
            'pnpp_id' => $pnpp->id,
            'poli_id' => $poli->id,
            'dokter_id' => $dokter->id,
            'tanggal' => $tanggal,
            'jam' => $jam,
            'home_visit' => false,
            'status' => 'terjadwal',
        ]);
    }

    #[Test]
    public function variabel_meta_custom_alias_terisi_dari_data_pasien(): void
    {
        $pnpp = $this->buatPnpp();
        $template = $this->buatTemplateAlias();

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$pnpp->id],
                'jenis' => 'outreach',
                'message_template_id' => $template->id,
            ])
            ->assertRedirect(route('admin.outreach.index'));

        $log = MessageLog::where('pnpp_id', $pnpp->id)->first();

        $this->assertNotNull($log);
        $this->assertSame(
            ['Budi Santoso', (string) $pnpp->nip, '081234567890'],
            (array) $log->template_params
        );
        $this->assertStringContainsString('Halo Budi Santoso', $log->konten);
        $this->assertStringContainsString((string) $pnpp->nip, $log->konten);
        $this->assertStringContainsString('081234567890', $log->konten);
        $this->assertStringNotContainsString('{nama_pasien}', $log->konten);
        $this->assertStringNotContainsString('{{', $log->konten);
    }

    #[Test]
    public function halaman_form_menyertakan_alias_token_per_pasien_untuk_pratinjau(): void
    {
        $pnpp = $this->buatPnpp();
        $this->buatReminder($pnpp, 'Poli Gigi', '2026-09-17', '09:14');
        $this->buatTemplateAlias();
        $this->buatTemplateGabungan();

        $response = $this->actingAs($this->superadmin())
            ->get(route('admin.outreach.create'))
            ->assertOk();

        // Data pasien di frontend memuat alias {nama_pasien} & {nomor_hp}.
        $this->assertStringContainsString('"nama_pasien":"Budi Santoso"', $response->getContent());
        $this->assertStringContainsString('"nomor_hp":"081234567890"', $response->getContent());

        // Dropdown template menampilkan token rapi {{ token }} yang dikenali.
        $this->assertStringContainsString('nama_pasien', $response->getContent());
    }

    #[Test]
    public function jadwal_bersamaan_di_hari_sama_digabung_jadi_satu_pesan(): void
    {
        $pnpp = $this->buatPnpp();
        $gigi = $this->buatReminder($pnpp, 'Poli Gigi', '2026-09-17', '09:14');
        $this->buatReminder($pnpp, 'Farmasi', '2026-09-17', '13:00');
        $template = $this->buatTemplateGabungan();

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$pnpp->id],
                'jenis' => 'outreach',
                'message_template_id' => $template->id,
                'reminder_ids' => [$pnpp->id => $gigi->id],
            ])
            ->assertRedirect(route('admin.outreach.index'));

        $log = MessageLog::where('pnpp_id', $pnpp->id)->first();

        $this->assertNotNull($log);
        $this->assertSame((int) $gigi->id, (int) $log->reminder_id);
        $this->assertStringContainsString('Hari/Tanggal: Kamis, 17 September 2026', $log->konten);
        $this->assertStringContainsString('Waktu: 09:14 & 13:00', $log->konten);
        $this->assertStringContainsString('Poli/Layanan: Poli Gigi & Farmasi', $log->konten);
        $this->assertSame(
            ['Kamis, 17 September 2026', '09:14 & 13:00', 'Poli Gigi & Farmasi'],
            (array) $log->template_params
        );

        // Satu pasien tetap hanya mendapat SATU pesan meski ada 2 jadwal.
        $this->assertSame(1, MessageLog::where('pnpp_id', $pnpp->id)->count());
    }

    #[Test]
    public function jadwal_di_hari_berbeda_tidak_digabung(): void
    {
        $pnpp = $this->buatPnpp();
        $satu = $this->buatReminder($pnpp, 'Poli Gigi', '2026-09-17', '09:14');
        $this->buatReminder($pnpp, 'Farmasi', '2026-09-18', '13:00');
        $template = $this->buatTemplateGabungan();

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$pnpp->id],
                'jenis' => 'outreach',
                'message_template_id' => $template->id,
                'reminder_ids' => [$pnpp->id => $satu->id],
            ])
            ->assertRedirect(route('admin.outreach.index'));

        $log = MessageLog::where('pnpp_id', $pnpp->id)->first();

        $this->assertStringContainsString('Waktu: 09:14', $log->konten);
        $this->assertStringNotContainsString('13:00', $log->konten);
        $this->assertStringNotContainsString('Farmasi', $log->konten);
        $this->assertSame(
            ['Kamis, 17 September 2026', '09:14', 'Poli Gigi'],
            (array) $log->template_params
        );
    }
}

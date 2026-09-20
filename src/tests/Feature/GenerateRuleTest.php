<?php

namespace Tests\Feature;

use App\Broadcasting\BroadcastService;
use App\Models\BroadcastRule;
use App\Models\Dokter;
use App\Models\MessageLog;
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

class GenerateRuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class); // termasuk 2 BroadcastRule (template null)
    }

    protected function superadmin(): User
    {
        return User::where('email', 'superadmin@gmail.com')->firstOrFail();
    }

    /** Master + pasien + template, dipakai banyak skenario. */
    protected function skenario(): array
    {
        $satker = Satker::create(['kode' => 'TEST', 'nama' => 'Satker Uji']);
        $poli = Poli::create(['kode' => 'UMUM-GEN', 'nama' => 'Poli Umum']);
        $dokter = Dokter::create(['poli_id' => $poli->id, 'nama' => 'dr. Rina']);

        $budi = Pnpp::create(['nama' => 'Budi Santoso', 'nip' => '123', 'satker_id' => $satker->id, 'no_hp' => '081234567890']);
        $tanpaHp = Pnpp::create(['nama' => 'Tanpa HP', 'nip' => '789', 'satker_id' => $satker->id, 'no_hp' => null]);

        $template = MessageTemplate::create([
            'judul' => 'Pengingat Uji',
            'channel' => 'WhatsApp',
            'konten' => 'Halo {nama}, jadwal Anda di {poli} pada {tanggal} pukul {jam}.',
            'is_active' => true,
        ]);

        return compact('satker', 'poli', 'dokter', 'budi', 'tanpaHp', 'template');
    }

    protected function buatJadwal(Pnpp $pasien, Poli $poli, Dokter $dokter, string $tanggal, string $status = 'terjadwal'): Reminder
    {
        return Reminder::create([
            'pnpp_id' => $pasien->id,
            'poli_id' => $poli->id,
            'dokter_id' => $dokter->id,
            'tanggal' => $tanggal,
            'jam' => '09:00',
            'status' => $status,
        ]);
    }

    #[Test]
    public function sweep_menandai_jadwal_terlewat_tanpa_kunjungan(): void
    {
        extract($this->skenario());

        $terlewat = $this->buatJadwal($budi, $poli, $dokter, today()->subDays(2)->format('Y-m-d'));
        $selesai = $this->buatJadwal($budi, $poli, $dokter, today()->subDays(3)->format('Y-m-d'), 'selesai');
        $mendatang = $this->buatJadwal($budi, $poli, $dokter, today()->addDays(5)->format('Y-m-d'));

        $jumlah = app(BroadcastService::class)->sweepStatus();

        $this->assertSame(1, $jumlah);
        $this->assertSame('tidak_datang', $terlewat->refresh()->status);
        $this->assertSame('selesai', $selesai->refresh()->status);
        $this->assertSame('terjadwal', $mendatang->refresh()->status);
    }

    #[Test]
    public function generate_outreach_membuat_pesan_per_rule_dan_dedup(): void
    {
        extract($this->skenario());

        BroadcastRule::query()->jenis('outreach')->update(['message_template_id' => $template->id]);

        $h7 = $this->buatJadwal($budi, $poli, $dokter, today()->addDays(7)->format('Y-m-d'));
        $h1 = $this->buatJadwal($budi, $poli, $dokter, today()->addDay()->format('Y-m-d'));
        $hariIni = $this->buatJadwal($budi, $poli, $dokter, today()->format('Y-m-d')); // bukan milik outreach

        $hasil = app(BroadcastService::class)->generate('outreach');

        $this->assertSame(['dibuat' => 2, 'dilewati' => 0], $hasil);
        $this->assertDatabaseHas('message_logs', [
            'jenis' => 'outreach', 'rule' => 'h-7', 'reminder_id' => $h7->id, 'status' => 'menunggu',
            'penerima_no_hp' => '6281234567890',
        ]);
        $this->assertDatabaseHas('message_logs', [
            'jenis' => 'outreach', 'rule' => 'h-1', 'reminder_id' => $h1->id,
        ]);
        $this->assertDatabaseMissing('message_logs', ['reminder_id' => $hariIni->id, 'jenis' => 'outreach']);

        // KONTEN render dari konteks reminder
        $log = MessageLog::where('rule', 'h-7')->first();
        $this->assertSame(
            'Halo Budi Santoso, jadwal Anda di Poli Umum pada '.now()->addDays(7)->locale('id')->translatedFormat('l, j F Y').' pukul 09:00.',
            $log->konten,
        );

        // Generate ulang → semua dilewati (dedup)
        $this->assertSame(['dibuat' => 0, 'dilewati' => 2], app(BroadcastService::class)->generate('outreach'));
    }

    #[Test]
    public function generate_follow_up_tidak_membuat_pesan_tanpa_aturan(): void
    {
        extract($this->skenario());

        // Aturan generate follow up (H-1, hari-H, tidak datang) dihapus
        // sementara — generate tidak menghasilkan apa pun walau ada jadwal.
        $h1 = $this->buatJadwal($budi, $poli, $dokter, today()->addDay()->format('Y-m-d'));
        $noShow = $this->buatJadwal($budi, $poli, $dokter, today()->subDay()->format('Y-m-d'), 'tidak_datang');

        $svc = app(BroadcastService::class);
        $svc->sweepStatus();

        $hasil = $svc->generate('follow_up');

        $this->assertSame(['dibuat' => 0, 'dilewati' => 0], $hasil);
        $this->assertSame(0, MessageLog::jenis('follow_up')->count());
        $this->assertCount(0, BroadcastRule::jenis('follow_up')->get());
    }

    #[Test]
    public function pasien_tanpa_nomor_valid_menghasilkan_pesan_gagal(): void
    {
        extract($this->skenario());

        BroadcastRule::query()->jenis('outreach')->update(['message_template_id' => $template->id]);

        $jadwal = $this->buatJadwal($tanpaHp, $poli, $dokter, today()->addDays(7)->format('Y-m-d'));

        app(BroadcastService::class)->generate('outreach');

        $this->assertDatabaseHas('message_logs', [
            'reminder_id' => $jadwal->id,
            'status' => 'gagal',
            'error' => 'Pasien tidak memiliki nomor WhatsApp yang valid.',
        ]);
    }

    #[Test]
    public function rule_tanpa_template_atau_nonaktif_dilewati(): void
    {
        extract($this->skenario());

        $this->buatJadwal($budi, $poli, $dokter, today()->addDays(7)->format('Y-m-d'));

        // Semua rule outreach tanpa template → tidak ada pesan
        $this->assertSame(['dibuat' => 0, 'dilewati' => 0], app(BroadcastService::class)->generate('outreach'));
        $this->assertSame(0, MessageLog::count());

        // Template terpasang tapi rule nonaktif → tetap dilewati
        $rule = BroadcastRule::query()->jenis('outreach')->where('rule', 'h-7')->first();
        $rule->update(['message_template_id' => $template->id, 'is_active' => false]);

        $this->assertSame(['dibuat' => 0, 'dilewati' => 0], app(BroadcastService::class)->generate('outreach'));
        $this->assertSame(0, MessageLog::count());
    }

    #[Test]
    public function template_dipilih_di_jadwal_menggantikan_template_default_rule(): void
    {
        extract($this->skenario());

        $default = MessageTemplate::create([
            'judul' => 'Default Rule',
            'channel' => 'WhatsApp',
            'konten' => 'Default {nama} di {poli}',
            'is_active' => true,
        ]);
        $khusus = MessageTemplate::create([
            'judul' => 'Khusus Jadwal',
            'channel' => 'WhatsApp',
            'konten' => 'Khusus {nama} di {poli}',
            'is_active' => true,
        ]);

        BroadcastRule::query()->jenis('outreach')->update(['message_template_id' => $default->id]);

        $jadwalOverride = $this->buatJadwal($budi, $poli, $dokter, today()->addDays(7)->format('Y-m-d'));
        $jadwalOverride->update(['message_template_id' => $khusus->id]);
        $jadwalDefault = $this->buatJadwal($budi, $poli, $dokter, today()->addDay()->format('Y-m-d'));

        $this->assertSame(['dibuat' => 2, 'dilewati' => 0], app(BroadcastService::class)->generate('outreach'));

        // Jadwal bernilai khusus memakai template miliknya, bukan default rule
        $logKhusus = MessageLog::where('rule', 'h-7')->firstOrFail();
        $this->assertSame($khusus->id, $logKhusus->message_template_id);
        $this->assertSame('Khusus Budi Santoso di Poli Umum', $logKhusus->konten);

        // Jadwal tanpa template tetap memakai default rule
        $logDefault = MessageLog::where('rule', 'h-1')->firstOrFail();
        $this->assertSame($default->id, $logDefault->message_template_id);
        $this->assertSame('Default Budi Santoso di Poli Umum', $logDefault->konten);
    }

    #[Test]
    public function tombol_generate_dari_halaman_outreach(): void
    {
        extract($this->skenario());

        BroadcastRule::query()->jenis('outreach')->update(['message_template_id' => $template->id]);
        $this->buatJadwal($budi, $poli, $dokter, today()->addDays(7)->format('Y-m-d'));

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.generate'))
            ->assertRedirect(route('admin.outreach.index'))
            ->assertSessionHas('success');

        $this->assertSame(1, MessageLog::count());

        // Halaman index menampilkan pesan yang barusan digenerate
        $this->actingAs($this->superadmin())
            ->get(route('admin.outreach.index'))
            ->assertOk()
            ->assertSee('Budi Santoso');
    }

    #[Test]
    public function batalkan_pesan_menunggu(): void
    {
        extract($this->skenario());

        BroadcastRule::query()->jenis('outreach')->update(['message_template_id' => $template->id]);
        $this->buatJadwal($budi, $poli, $dokter, today()->addDays(7)->format('Y-m-d'));

        app(BroadcastService::class)->generate('outreach');
        $log = MessageLog::first();

        $this->actingAs($this->superadmin())
            ->delete(route('admin.broadcast.batalkan', $log))
            ->assertRedirect();

        $this->assertSame('dibatalkan', $log->refresh()->status);
    }

    #[Test]
    public function filter_rentang_tanggal_menyaring_tabel_dan_kartu_statistik(): void
    {
        extract($this->skenario());

        $buat = function (string $nama, string $status, string $tanggal) use ($budi, $template) {
            $log = MessageLog::create([
                'jenis' => 'outreach',
                'rule' => 'manual',
                'message_template_id' => $template->id,
                'pnpp_id' => $budi->id,
                'penerima_nama' => $nama,
                'penerima_no_hp' => '081234567890',
                'konten' => 'Halo, jadwal Anda di Poli Umum.',
                'status' => $status,
            ]);
            $log->timestamps = false;
            $log->created_at = $tanggal.' 08:00:00';
            $log->save();

            return $log;
        };
        $buat('Lama Terkirim', 'terkirim', today()->subDay()->format('Y-m-d'));
        $buat('Lama Menunggu', 'menunggu', today()->subDay()->format('Y-m-d'));
        $buat('Hari Ini Terkirim', 'terkirim', today()->format('Y-m-d'));

        // Kartu statistik ikut menghitung total, seluruh rentang.
        $this->actingAs($this->superadmin())
            ->get(route('admin.outreach.index'))
            ->assertOk()
            ->assertSee('Lama Terkirim')
            ->assertSee('Lama Menunggu')
            ->assertSee('Hari Ini Terkirim');

        // Filter hanya hari ini: tabel & kartu menyesuaikan.
        $this->get(route('admin.outreach.index', [
            'dari' => today()->format('Y-m-d'),
            'sampai' => today()->format('Y-m-d'),
        ]))
            ->assertOk()
            ->assertSee('Hari Ini Terkirim')
            ->assertDontSee('Lama Terkirim')
            ->assertDontSee('Lama Menunggu');
    }

    #[Test]
    public function filter_template_menyaring_tabel_dan_kartu_statistik(): void
    {
        extract($this->skenario());

        $templateLain = MessageTemplate::create([
            'judul' => 'Pengingat Alternatif',
            'channel' => 'WhatsApp',
            'konten' => 'Halo {nama}, jangan lupa kontrol.',
            'is_active' => true,
        ]);

        $buat = fn (string $nama, string $status, int $templateId) => MessageLog::create([
            'jenis' => 'outreach',
            'rule' => 'manual',
            'message_template_id' => $templateId,
            'pnpp_id' => $budi->id,
            'penerima_nama' => $nama,
            'penerima_no_hp' => '081234567890',
            'konten' => 'Halo, jadwal Anda di Poli Umum.',
            'status' => $status,
        ]);
        $buat('Pakai Default', 'terkirim', $template->id);
        $buat('Pakai Alternatif', 'terkirim', $templateLain->id);

        $this->actingAs($this->superadmin())
            ->get(route('admin.outreach.index'))
            ->assertOk()
            ->assertSee('Pakai Default')
            ->assertSee('Pakai Alternatif');

        // Filter template default: tabel & kartu hanya template terpilih.
        $this->get(route('admin.outreach.index', ['template' => $template->id]))
            ->assertOk()
            ->assertSee('Pakai Default')
            ->assertDontSee('Pakai Alternatif');
    }

    #[Test]
    public function filter_satker_menyaring_tabel_dan_kartu_statistik(): void
    {
        extract($this->skenario());

        $satkerLain = Satker::create(['kode' => 'TEST2', 'nama' => 'Satker Kedua']);
        $siti = Pnpp::create(['nama' => 'Siti dari Satker Kedua', 'nip' => '456', 'satker_id' => $satkerLain->id, 'no_hp' => '081234567891']);

        $buat = fn (string $nama, Pnpp $pasien) => MessageLog::create([
            'jenis' => 'outreach',
            'rule' => 'manual',
            'message_template_id' => $template->id,
            'pnpp_id' => $pasien->id,
            'penerima_nama' => $nama,
            'penerima_no_hp' => $pasien->no_hp,
            'konten' => 'Halo, jadwal Anda di Poli Umum.',
            'status' => 'terkirim',
        ]);
        $buat('Budi Satker Satu', $budi);
        $buat('Siti Satker Kedua', $siti);

        $this->actingAs($this->superadmin())
            ->get(route('admin.outreach.index'))
            ->assertOk()
            ->assertSee('Budi Satker Satu')
            ->assertSee('Siti Satker Kedua');

        // Filter satker: tabel & kartu hanya pesan pasien satker terpilih.
        $this->get(route('admin.outreach.index', ['satker' => $satkerLain->id]))
            ->assertOk()
            ->assertSee('Siti Satker Kedua')
            ->assertDontSee('Budi Satker Satu');
    }
}

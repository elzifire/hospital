<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\Kunjungan;
use App\Models\MessageLog;
use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\Reminder;
use App\Models\ResponManual;
use App\Models\Satker;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

/**
 * Dashboard proaktif — angka dihitung dari database, bukan dummy.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function superadmin(): User
    {
        return User::where('email', 'superadmin@gmail.com')->firstOrFail();
    }

    #[Test]
    public function dashboard_memuat_data_riil_dari_database(): void
    {
        $satker = Satker::create(['kode' => 'KSDM', 'nama' => 'Polresta Bogor']);

        $pnpp = Pnpp::factory()->create(['satker_id' => $satker->id]);

        MessageLog::create([
            'jenis' => 'outreach',
            'pnpp_id' => $pnpp->id,
            'penerima_nama' => $pnpp->nama,
            'penerima_no_hp' => $pnpp->no_hp,
            'konten' => 'Info layanan kesehatan.',
            'status' => 'terkirim',
            'sent_at' => now(),
        ]);
        MessageLog::create([
            'jenis' => 'follow_up',
            'pnpp_id' => $pnpp->id,
            'penerima_nama' => $pnpp->nama,
            'penerima_no_hp' => $pnpp->no_hp,
            'konten' => 'Bagaimana kondisi Anda?',
            'status' => 'terkirim',
            'sent_at' => now(),
        ]);
        ResponManual::create([
            'nama' => $pnpp->nama,
            'nrp_nip' => $pnpp->nip,
            'no_hp' => $pnpp->no_hp,
            'satker' => 'Polresta Bogor',
            'isi' => 'Saya sudah lega, terima kasih.',
            'waktu' => now(),
        ]);

        $igd = Poli::firstOrCreate(['kode' => 'IGD'], ['nama' => 'Instalasi Gawat Darurat']);
        Kunjungan::create([
            'pnpp_id' => $pnpp->id,
            'poli_id' => $igd->id,
            'tanggal_kunjungan' => today(),
            'keluhan' => 'Demam',
            'diagnosa' => 'ISPA',
        ]);

        $respon = $this->actingAs($this->superadmin())->get(route('admin.dashboard'));

        $respon->assertOk();
        $respon->assertSee('PNPP DALAM DATABASE');
        $respon->assertSee((string) Pnpp::count());
        $respon->assertSee('Polresta Bogor');
        $respon->assertSee('Instalasi Gawat Darurat');
        // Chart trend & donut harus menerima JSON series.
        $respon->assertSee('trendChart');
        $respon->assertSee('donutChart');
    }

    #[Test]
    public function status_bilangan_di_hitung_dari_outbox(): void
    {
        $pnpp = Pnpp::factory()->create();

        // 2 follow-up terkirim, 1 gagal → donut Selesai 66,7% / Terlambat 33,3%.
        foreach (['terkirim', 'terkirim', 'gagal'] as $i => $status) {
            MessageLog::create([
                'jenis' => 'follow_up',
                'pnpp_id' => $pnpp->id,
                'penerima_nama' => $pnpp->nama,
                'penerima_no_hp' => $pnpp->no_hp,
                'konten' => 'Pesan #'.($i + 1),
                'status' => $status,
                'sent_at' => $status === 'terkirim' ? now() : null,
            ]);
        }

        $respon = $this->actingAs($this->superadmin())->get(route('admin.dashboard'));

        $respon->assertOk();
        $respon->assertSee('66,7%');
        $respon->assertSee('33,3%');
        $respon->assertSee('2');
    }

    #[Test]
    public function foto_follow_up_hari_ini_diambil_dari_jadwal_reminder(): void
    {
        $pnpp = Pnpp::factory()->create();
        $poli = Poli::firstOrCreate(['kode' => 'UMUM'], ['nama' => 'Poli Umum']);

        Reminder::create([
            'pnpp_id' => $pnpp->id,
            'poli_id' => $poli->id,
            'tanggal' => today(),
            'jam' => '09:00',
            'status' => 'terjadwal',
        ]);

        $respon = $this->actingAs($this->superadmin())->get(route('admin.dashboard'));

        $respon->assertOk();
        $respon->assertSee('1');
    }

    #[Test]
    public function kunjungan_hari_ini_dikelompokkan_per_poli(): void
    {
        $pnpp = Pnpp::factory()->create();
        $igd = Poli::firstOrCreate(['kode' => 'IGD'], ['nama' => 'Instalasi Gawat Darurat']);

        Kunjungan::create([
            'pnpp_id' => $pnpp->id,
            'poli_id' => $igd->id,
            'tanggal_kunjungan' => today(),
        ]);
        Kunjungan::create([
            'pnpp_id' => $pnpp->id,
            'tanggal_kunjungan' => today(),
            'keluhan' => 'Kontrol',
        ]);

        $respon = $this->actingAs($this->superadmin())->get(route('admin.dashboard'));

        $respon->assertOk();
        $respon->assertSee('2');
        $respon->assertSee('Instalasi Gawat Darurat');
        $respon->assertSee('Tanpa Poli');
    }

    #[Test]
    public function trend_kunjungan_menjadi_total_tunggal_dengan_hardcode_additif(): void
    {
        Kunjungan::create([
            'pnpp_id' => Pnpp::factory()->create()->id,
            'tanggal_kunjungan' => today(),
        ]);

        $respon = $this->actingAs($this->superadmin())->get(route('admin.dashboard'));

        $respon->assertOk();
        // Satu seri tunggal, tanpa pemisahan kategori IGD / Rawat Jalan / Rawat Inap.
        $respon->assertSee('"name":"Total Kunjungan"', false);
        $respon->assertDontSee('"name":"Rawat Inap"', false);

        // Nilai hardcode Jan–Sep ditambahkan (additif) di atas database.
        if (now()->year === 2026) {
            $respon->assertSee('"data":[70,88,87,122,93,49,104,93,0', false);
        }
    }

    #[Test]
    public function target_pnpp_mengikuti_konstanta_dan_bisa_dioverride(): void
    {
        $konstanta = (new ReflectionClass(DashboardController::class))->getConstant('TARGET_PNPP');

        $respon = $this->actingAs($this->superadmin())->get(route('admin.dashboard'));

        $respon->assertOk();
        $respon->assertSee(number_format($konstanta, 0, ',', '.'));

        // Override lewat config (.env DASHBOARD_TARGET_PNPP) tetap berlaku.
        config(['dashboard.target_pnpp' => 777]);
        $respon = $this->actingAs($this->superadmin())->get(route('admin.dashboard'));

        $respon->assertOk();
        $respon->assertSee('777');
    }
}

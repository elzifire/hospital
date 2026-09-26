<?php

namespace Tests\Feature;

use App\Models\HariLibur;
use App\Models\Poli;
use App\Models\RegisterPnpp;
use App\Models\Satker;
use App\Models\TujuanKunjungan;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Hari buka poli (checklist per hari) + tabel hari libur pada pendaftaran
 * PNPP publik:
 *  - form menampilkan jadwal utuh tiap poli (hari · jam), poli dulu baru tanggal.
 *  - tanggal kunjungan wajib jatuh pada hari yang diceklis tiap poli tujuan.
 *  - tanpa hari diceklis = poli buka setiap hari.
 *  - tanggal kunjungan tidak boleh jatuh pada hari libur (umum / khusus poli).
 *  - tiap hari libur punya flag sumber (manual / google_calendar).
 */
class RegisterPnppHariLayananTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function payload(array $overrides = []): array
    {
        $satker = Satker::firstOrCreate(['kode' => 'UJI-HR'], ['nama' => 'Satker Uji Hari']);

        return array_merge([
            'nama' => 'BUDI SANTOSO',
            'nik' => '1101010101010001',
            'nip' => '9901',
            'jabatan' => 'Bintara',
            'satker_id' => $satker->id,
            'unit' => 'Bagian Operasional',
            'ttl' => 'Bogor, 01-01-1990',
            'alamat' => 'Jl. Uji No. 1',
            'no_hp' => '081234567890',
            'tujuan_kunjungan' => [TujuanKunjungan::first()->id],
            'rencana_tanggal_kunjungan' => $this->tanggalUji()->format('Y-m-d'),
            'rencana_jam_kunjungan' => '09:30',
        ], $overrides);
    }

    protected function tanggalUji(): \Illuminate\Support\Carbon
    {
        return today()->addDays(2);
    }

    /**
     * Nama hari Indonesia dari tanggal (1 = Senin ... 7 = Minggu).
     */
    protected function namaHari(\Illuminate\Support\Carbon $tanggal): string
    {
        $daftar = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        return $daftar[$tanggal->isoWeekday() - 1];
    }

    /**
     * Nama hari lain (3 hari setelah hari yang diberikan) — bukan hari sama.
     */
    protected function hariLain(string $hari): string
    {
        $daftar = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        return $daftar[(array_search($hari, $daftar, true) + 3) % 7];
    }

    #[Test]
    public function form_menampilkan_jadwal_utuh_tiap_poli(): void
    {
        // Contoh kasus: poli gigi buka Senin–Jumat jam 09:00–17:00.
        Poli::create([
            'kode' => 'GIGI-UJI',
            'nama' => 'Poli Gigi',
            'jam_buka' => '09:00',
            'jam_tutup' => '17:00',
            'hari_senin' => true,
            'hari_selasa' => true,
            'hari_rabu' => true,
            'hari_kamis' => true,
            'hari_jumat' => true,
        ]);

        $this->get(route('register-pnpp.create'))
            ->assertOk()
            ->assertSee('Poli Tujuan')
            ->assertSee('Poli Gigi')
            ->assertSee('Senin, Selasa, Rabu, Kamis, Jumat')
            ->assertSee('09:00–17:00')
            ->assertSee('Tanggal Kunjungan');
    }

    #[Test]
    public function poli_igd_24_jam_setiap_hari_menerima_tanggal_dan_jam_apa_saja(): void
    {
        // Contoh kasus: IGD buka 24 jam, Senin–Minggu (tanpa hari diceklis).
        $poli = Poli::create(['kode' => 'IGD-UJI', 'nama' => 'Instalasi Gawat Darurat']);

        $this->post(route('register-pnpp.store'), $this->payload([
            'poli_dituju' => [$poli->id],
            'rencana_tanggal_kunjungan' => today()->addDays(11)->format('Y-m-d'),
            'rencana_jam_kunjungan' => '23:45',
        ]))
            ->assertRedirect(route('register-pnpp.success'))
            ->assertSessionHas('success_register_id');

        $this->assertSame(1, RegisterPnpp::count());
    }

    #[Test]
    public function tanggal_di_hari_yang_diceklis_diterima(): void
    {
        $hari = $this->namaHari($this->tanggalUji());
        $poli = Poli::create(['kode' => 'HARI', 'nama' => 'Poli Hari Uji', 'hari_'.strtolower($hari) => true]);

        $this->post(route('register-pnpp.store'), $this->payload(['poli_dituju' => [$poli->id]]))
            ->assertRedirect(route('register-pnpp.success'))
            ->assertSessionHas('success_register_id');

        $this->assertSame(1, RegisterPnpp::count());
    }

    #[Test]
    public function tanggal_di_hari_yang_tidak_diceklis_ditolak(): void
    {
        $hariTerbuka = $this->hariLain($this->namaHari($this->tanggalUji()));
        $poli = Poli::create(['kode' => 'HARI', 'nama' => 'Poli Hari Uji', 'hari_'.strtolower($hariTerbuka) => true]);

        $this->post(route('register-pnpp.store'), $this->payload(['poli_dituju' => [$poli->id]]))
            ->assertSessionHasErrors('rencana_tanggal_kunjungan');

        $this->assertSame(0, RegisterPnpp::count());
    }

    #[Test]
    public function hari_buka_boleh_tidak_berurutan(): void
    {
        $tanggal1 = $this->tanggalUji();
        $w1 = $this->namaHari($tanggal1);
        $w2 = $this->hariLain($w1); // 3 hari setelah w1, tidak bersebelahan
        $poli = Poli::create([
            'kode' => 'SUYAN',
            'nama' => 'Poli Souvenir Jumat',
            'hari_'.strtolower($w1) => true,
            'hari_'.strtolower($w2) => true,
        ]);

        // Senin & Jumat sama-sama diceklis: tanggal di kedua hari itu diterima.
        $this->post(route('register-pnpp.store'), $this->payload([
            'poli_dituju' => [$poli->id],
            'rencana_tanggal_kunjungan' => $tanggal1->format('Y-m-d'),
        ]))->assertRedirect(route('register-pnpp.success'));

        $this->post(route('register-pnpp.store'), $this->payload([
            'poli_dituju' => [$poli->id],
            'rencana_tanggal_kunjungan' => $tanggal1->copy()->addDays(3)->format('Y-m-d'),
        ]))->assertRedirect(route('register-pnpp.success'));

        $this->assertSame(2, RegisterPnpp::count());
    }

    #[Test]
    public function poli_tanpa_checklist_menerima_tanggal_apa_saja(): void
    {
        $poli = Poli::create(['kode' => 'BEBAS', 'nama' => 'Poli Hari Bebas']);

        $this->post(route('register-pnpp.store'), $this->payload([
            'poli_dituju' => [$poli->id],
            'rencana_tanggal_kunjungan' => today()->addDays(10)->format('Y-m-d'),
        ]))
            ->assertRedirect(route('register-pnpp.success'))
            ->assertSessionHas('success_register_id');

        $this->assertSame(1, RegisterPnpp::count());
    }

    #[Test]
    public function tanggal_hari_libur_umum_ditolak(): void
    {
        $poli = Poli::create(['kode' => 'LIBUR', 'nama' => 'Poli Libur Uji']);
        HariLibur::create(['tanggal' => $this->tanggalUji()->format('Y-m-d'), 'nama' => 'Libur Nasional Uji']);

        $this->post(route('register-pnpp.store'), $this->payload(['poli_dituju' => [$poli->id]]))
            ->assertSessionHasErrors('rencana_tanggal_kunjungan');

        $this->assertSame(0, RegisterPnpp::count());
    }

    #[Test]
    public function tanggal_hari_libur_khusus_poli_ditolak_hanya_untuk_poli_tersebut(): void
    {
        $poliA = Poli::create(['kode' => 'LIB-A', 'nama' => 'Poli A']);
        $poliB = Poli::create(['kode' => 'LIB-B', 'nama' => 'Poli B']);
        $tanggal = $this->tanggalUji()->format('Y-m-d');
        HariLibur::create(['tanggal' => $tanggal, 'nama' => 'Khusus Poli A', 'poli_id' => $poliA->id]);

        // Ke poli yang libur → ditolak.
        $this->post(route('register-pnpp.store'), $this->payload([
            'poli_dituju' => [$poliA->id],
            'rencana_tanggal_kunjungan' => $tanggal,
        ]))
            ->assertSessionHasErrors('rencana_tanggal_kunjungan');

        // Ke poli lain pada tanggal yang sama → diterima.
        $this->post(route('register-pnpp.store'), $this->payload([
            'poli_dituju' => [$poliB->id],
            'rencana_tanggal_kunjungan' => $tanggal,
        ]))
            ->assertRedirect(route('register-pnpp.success'))
            ->assertSessionHas('success_register_id');

        $this->assertSame(1, RegisterPnpp::count());
    }

    #[Test]
    public function sumber_hari_libur_tercatat_dan_google_calendar_ikut_diblokir(): void
    {
        $poli = Poli::create(['kode' => 'GOOGLE-UJI', 'nama' => 'Poli Google']);

        // Default: manual.
        $manual = HariLibur::create(['tanggal' => $this->tanggalUji()->format('Y-m-d'), 'nama' => 'Manual Uji']);
        $this->assertSame(HariLibur::SUMBER_MANUAL, $manual->refresh()->sumber);

        // Dari Google Calendar: flag sumber + event_id tercatat, tetap memblokir.
        $tanggal = $this->tanggalUji()->copy()->addDays(3)->format('Y-m-d');
        $google = HariLibur::create([
            'tanggal' => $tanggal,
            'nama' => 'Kalender Uji',
            'sumber' => HariLibur::SUMBER_GOOGLE_CALENDAR,
            'event_id' => 'gc-event-123',
        ]);
        $this->assertSame(HariLibur::SUMBER_GOOGLE_CALENDAR, $google->sumber);
        $this->assertSame('gc-event-123', $google->event_id);

        $this->post(route('register-pnpp.store'), $this->payload([
            'poli_dituju' => [$poli->id],
            'rencana_tanggal_kunjungan' => $tanggal,
        ]))
            ->assertSessionHasErrors('rencana_tanggal_kunjungan');

        $this->assertSame(0, RegisterPnpp::count());
    }
}
<?php

namespace Tests\Feature;

use App\Models\Poli;
use App\Models\PoliJadwalHari;
use App\Models\RegisterPnpp;
use App\Models\Satker;
use App\Models\TujuanKunjungan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Jadwal layanan per hari pada poli:
 *  - tabel poli_jadwal_hari menimpa pola lama (jam_buka/jam_tutup + hari_*).
 *  - tanpa baris jadwal per hari, perilaku lama tetap dipakai (fallback).
 *  - registrasi PNPP (publik & admin) menilai jam/hari sesuai tanggal yang
 *    dipilih, bukan jam statis poli.
 */
class PoliJadwalPerHariTest extends TestCase
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

    protected function tanggalUji(): Carbon
    {
        return today()->addDays(2);
    }

    protected function namaHari(Carbon $tanggal): string
    {
        return Poli::namaHari($tanggal);
    }

    /** Senin pekan depan — selalu di masa depan untuk melewati after_or_equal:today. */
    protected function seninMendatang(): Carbon
    {
        return Carbon::today()->addWeek()->startOfWeek();
    }

    protected function payload(array $overrides = []): array
    {
        $satker = Satker::firstOrCreate(['kode' => 'UJI-JPD'], ['nama' => 'Satker Uji Jadwal Per Hari']);

        return array_merge([
            'nama' => 'SITI AMINAH',
            'nik' => '1101010101010001',
            'nip' => '8801',
            'jabatan' => 'PNS',
            'satker_id' => $satker->id,
            'unit' => 'Bagian Umum',
            'ttl' => 'Bogor, 01-01-1988',
            'alamat' => 'Jl. Uji No. 2',
            'no_hp' => '081298765432',
            'tujuan_kunjungan' => [TujuanKunjungan::first()->id],
            'rencana_tanggal_kunjungan' => $this->tanggalUji()->format('Y-m-d'),
            'rencana_jam_kunjungan' => '09:30',
        ], $overrides);
    }

    protected function beriJadwalPerHari(Poli $poli, array $hariJam): void
    {
        $poli->jadwalHari()->delete();

        foreach (Poli::DAFTAR_HARI as $hari) {
            $entri = $hariJam[$hari] ?? ['buka' => false];

            $poli->jadwalHari()->create([
                'hari' => $hari,
                'buka' => $entri['buka'] ?? false,
                'jam_buka' => $entri['buka'] ? ($entri['jam_buka'] ?? null) : null,
                'jam_tutup' => $entri['buka'] ? ($entri['jam_tutup'] ?? null) : null,
            ]);
        }

        $poli->refresh();
    }

    #[Test]
    public function jadwal_per_hari_menimpa_pola_lama_dan_tanpa_baris_tetap_fallback(): void
    {
        $legacy = Poli::create(['kode' => 'LEGACY', 'nama' => 'Poli Lama', 'jam_buka' => '08:00', 'jam_tutup' => '12:00']);

        // Tanpa baris jadwal per hari: pakai jam/hari pola lama, buka setiap hari.
        $this->assertFalse($legacy->hasJadwalPerHari());
        $this->assertTrue($legacy->hariBuka($this->tanggalUji()));
        $this->assertSame('08:00', $legacy->jadwalPada($this->tanggalUji())['jam_buka']);
        $this->assertSame('08:00–12:00', $legacy->jamLayanan());

        // Dengan jadwal per hari: sumber kebenaran berpindah ke baris tsb.
        $poli = Poli::create(['kode' => 'PERHARI', 'nama' => 'Poli Per Hari', 'jam_buka' => '08:00', 'jam_tutup' => '12:00']);
        $this->beriJadwalPerHari($poli, [
            'Senin' => ['buka' => true, 'jam_buka' => '08:00', 'jam_tutup' => '10:00'],
            'Selasa' => ['buka' => true, 'jam_buka' => '13:00', 'jam_tutup' => '16:00'],
            'Rabu' => ['buka' => false],
        ]);

        $this->assertTrue($poli->hasJadwalPerHari());
        $this->assertSame(7, $poli->jadwalHari()->count());
        $this->assertSame('08:00', $poli->jadwalPada($this->tanggalUji()->startOfWeek())['jam_buka']);
        $this->assertFalse($poli->jadwalPada($this->tanggalUji()->startOfWeek()->addDays(2))['buka']);
        $this->assertTrue($poli->hariBuka($this->tanggalUji()->startOfWeek())); // Senin
        $this->assertFalse($poli->hariBuka($this->tanggalUji()->startOfWeek()->addDays(2))); // Rabu
    }

    #[Test]
    public function jadwal_ringkas_per_hari_menampilkan_jam_berbeda_tiap_hari(): void
    {
        $poli = Poli::create(['kode' => 'RNGKAS', 'nama' => 'Poli Ringkas']);
        $this->beriJadwalPerHari($poli, [
            'Senin' => ['buka' => true, 'jam_buka' => '08:00', 'jam_tutup' => '12:00'],
            'Selasa' => ['buka' => true, 'jam_buka' => '08:00', 'jam_tutup' => '12:00'],
            'Rabu' => ['buka' => true, 'jam_buka' => '08:00', 'jam_tutup' => '12:00'],
            'Kamis' => ['buka' => true, 'jam_buka' => '08:00', 'jam_tutup' => '12:00'],
            'Jumat' => ['buka' => true, 'jam_buka' => '08:00', 'jam_tutup' => '12:00'],
            'Sabtu' => ['buka' => true, 'jam_buka' => '09:00', 'jam_tutup' => '13:00'],
            'Minggu' => ['buka' => false],
        ]);

        $ringkas = $poli->jadwalRingkas();

        $this->assertStringContainsString('Sen–Jum 08:00–12:00', $ringkas);
        $this->assertStringContainsString('Sab 09:00–13:00', $ringkas);
        $this->assertStringContainsString('Min Tutup', $ringkas);
    }

    #[Test]
    public function hari_buka_per_hari_dipakai_validasi_registrasi_publik(): void
    {
        $poli = Poli::create(['kode' => 'JPD-1', 'nama' => 'Poli Jadwal Per Hari']);
        $monday = $this->seninMendatang(); // Senin pekan depan
        $this->beriJadwalPerHari($poli, [
            'Senin' => ['buka' => true, 'jam_buka' => '08:00', 'jam_tutup' => '10:00'],
            'Selasa' => ['buka' => false],
        ]);

        // Senin jam 09:00 (di dalam 08:00–10:00) → diterima.
        $this->post(route('register-pnpp.store'), $this->payload([
            'poli_dituju' => [$poli->id],
            'rencana_tanggal_kunjungan' => $monday->format('Y-m-d'),
            'rencana_jam_kunjungan' => '09:00',
        ]))->assertRedirect(route('register-pnpp.success'));

        // Senin jam 11:00 (di luar 08:00–10:00) → ditolak, walaupun poli lama
        // dibuka lebih panjang — jam dinilai sesuai hari yang dipilih.
        $this->post(route('register-pnpp.store'), $this->payload([
            'poli_dituju' => [$poli->id],
            'rencana_tanggal_kunjungan' => $monday->format('Y-m-d'),
            'rencana_jam_kunjungan' => '11:00',
        ]))->assertSessionHasErrors('rencana_jam_kunjungan');

        // Selasa (hari tutup) → ditolak sebagai hari layanan.
        $this->post(route('register-pnpp.store'), $this->payload([
            'poli_dituju' => [$poli->id],
            'rencana_tanggal_kunjungan' => $monday->copy()->addDay()->format('Y-m-d'),
            'rencana_jam_kunjungan' => '09:00',
        ]))->assertSessionHasErrors('rencana_tanggal_kunjungan');

        $this->assertSame(1, RegisterPnpp::count());
    }

    #[Test]
    public function jadwal_per_hari_ikut_divalidasi_pada_edit_admin(): void
    {
        // Registrasi ni lewat jalur publik dulu, lalu diedit sebagai admin.
        $poli = Poli::create(['kode' => 'JPD-2', 'nama' => 'Poli Jadwal Per Hari']);
        $monday = $this->seninMendatang();
        $this->beriJadwalPerHari($poli, [
            'Senin' => ['buka' => true, 'jam_buka' => '09:00', 'jam_tutup' => '11:00'],
        ]);

        $this->post(route('register-pnpp.store'), $this->payload([
            'poli_dituju' => [$poli->id],
            'rencana_tanggal_kunjungan' => $monday->format('Y-m-d'),
            'rencana_jam_kunjungan' => '10:00',
        ]))->assertRedirect(route('register-pnpp.success'));

        $register = RegisterPnpp::firstOrFail();

        // Edit jam ke 13:00 — di luar 09:00–11:00 → ditolak.
        $this->actingAs($this->superadmin())
            ->patch(route('admin.register-pnpp.update', $register), [
                'poli_dituju' => [$poli->id],
                'rencana_tanggal_kunjungan' => $monday->format('Y-m-d'),
                'rencana_jam_kunjungan' => '13:00',
            ])
            ->assertSessionHasErrors('rencana_jam_kunjungan');

        // Edit tanggal ke hari yang tutup → ditolak.
        $this->actingAs($this->superadmin())
            ->patch(route('admin.register-pnpp.update', $register), [
                'poli_dituju' => [$poli->id],
                'rencana_tanggal_kunjungan' => $monday->copy()->addDay()->format('Y-m-d'),
                'rencana_jam_kunjungan' => '10:00',
            ])
            ->assertSessionHasErrors('rencana_tanggal_kunjungan');
    }

    #[Test]
    public function admin_bisa_menyimpan_jadwal_per_hari_dari_form_poli(): void
    {
        $senin = ['buka' => 1, 'jam_buka' => '08:00', 'jam_tutup' => '12:00'];
        $selasa = ['buka' => 1, 'jam_buka' => '13:00', 'jam_tutup' => '16:00'];
        $jadwal = [];
        foreach (Poli::DAFTAR_HARI as $hari) {
            $jadwal[$hari] = ['buka' => 0];
        }
        $jadwal['Senin'] = $senin;
        $jadwal['Selasa'] = $selasa;

        $this->actingAs($this->superadmin())
            ->post(route('admin.poli.store'), [
                'kode' => 'JPD-NEW',
                'nama' => 'Poli New Per Hari',
                'jadwal_hari' => $jadwal,
            ])
            ->assertRedirect(route('admin.poli.index'));

        $poli = Poli::where('kode', 'JPD-NEW')->firstOrFail();

        $this->assertTrue($poli->hasJadwalPerHari());
        $this->assertSame(7, PoliJadwalHari::where('poli_id', $poli->id)->count());
        $this->assertTrue($poli->jadwalPada(today()->startOfWeek())['buka']);
        $this->assertFalse($poli->jadwalPada(today()->startOfWeek()->addDays(2))['buka']);
        $this->assertSame('08:00', $poli->jadwalPada(today()->startOfWeek())['jam_buka']);
    }

    #[Test]
    public function form_poli_wajib_minimal_satu_hari_buka(): void
    {
        $jadwal = [];
        foreach (Poli::DAFTAR_HARI as $hari) {
            $jadwal[$hari] = ['buka' => 0];
        }

        $this->actingAs($this->superadmin())
            ->from(route('admin.poli.create'))
            ->post(route('admin.poli.store'), [
                'kode' => 'JPD-TUTUP',
                'nama' => 'Poli Tutup Seminggu',
                'jadwal_hari' => $jadwal,
            ])
            ->assertSessionHasErrors('jadwal_hari')
            ->assertRedirect(route('admin.poli.create'));

        $this->assertSame(0, Poli::where('kode', 'JPD-TUTUP')->count());
    }

    #[Test]
    public function form_poli_menolak_jam_tutup_sebelum_jam_buka(): void
    {
        $jadwal = [];
        foreach (Poli::DAFTAR_HARI as $hari) {
            $jadwal[$hari] = ['buka' => 0];
        }
        $jadwal['Senin'] = ['buka' => 1, 'jam_buka' => '16:00', 'jam_tutup' => '08:00'];

        $this->actingAs($this->superadmin())
            ->from(route('admin.poli.create'))
            ->post(route('admin.poli.store'), [
                'kode' => 'JPD-JAM',
                'nama' => 'Poli Jam Salah',
                'jadwal_hari' => $jadwal,
            ])
            ->assertSessionHasErrors('jadwal_hari.Senin.jam_tutup');
    }

    #[Test]
    public function edit_poli_menyimpan_perubahan_jadwal_per_hari(): void
    {
        $poli = Poli::create(['kode' => 'JPD-EDIT', 'nama' => 'Poli Edit Per Hari', 'jam_buka' => '08:00', 'jam_tutup' => '12:00']);

        $jadwal = [];
        foreach (Poli::DAFTAR_HARI as $hari) {
            $jadwal[$hari] = ['buka' => 0];
        }
        $jadwal['Senin'] = ['buka' => 1, 'jam_buka' => '07:00', 'jam_tutup' => '09:00'];

        $this->actingAs($this->superadmin())
            ->post(route('admin.poli.update', $poli), [
                '_method' => 'PUT',
                'kode' => 'JPD-EDIT',
                'nama' => 'Poli Edit Per Hari',
                'jadwal_hari' => $jadwal,
            ])
            ->assertRedirect(route('admin.poli.index'));

        $poli->refresh();

        $this->assertTrue($poli->hasJadwalPerHari());
        $this->assertTrue($poli->jadwalPada(today()->startOfWeek())['buka']);
        $this->assertSame('07:00', $poli->jadwalPada(today()->startOfWeek())['jam_buka']);
        $this->assertFalse($poli->jadwalPada(today()->startOfWeek()->addDays(2))['buka']);
        $this->assertStringContainsString('Sen 07:00–09:00', $poli->jadwalRingkas());
    }
}

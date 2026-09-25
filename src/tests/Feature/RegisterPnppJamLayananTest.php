<?php

namespace Tests\Feature;

use App\Models\Poli;
use App\Models\RegisterPnpp;
use App\Models\Satker;
use App\Models\TujuanKunjungan;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Jam layanan poli (jam_buka/jam_tutup) pada pendaftaran PNPP publik:
 *  - form menampilkan jam layanan di tiap poli (urutan: poli dulu, baru tanggal).
 *  - jam kunjungan wajib berada dalam jam layanan tiap poli tujuan.
 *  - poli tanpa jam (null) dianggap buka 24 jam.
 */
class RegisterPnppJamLayananTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function payload(array $overrides = []): array
    {
        $satker = Satker::create(['kode' => 'UJI-SD', 'nama' => 'Satker Uji']);

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
            'rencana_tanggal_kunjungan' => today()->addDays(2)->format('Y-m-d'),
            'rencana_jam_kunjungan' => '09:30',
        ], $overrides);
    }

    #[Test]
    public function form_menampilkan_jam_layanan_di_urutan_poli_pertama(): void
    {
        $poli = Poli::create(['kode' => 'LAYANAN', 'nama' => 'Poli Layanan Uji', 'jam_buka' => '08:00', 'jam_tutup' => '12:00']);

        $this->get(route('register-pnpp.create'))
            ->assertOk()
            ->assertSee('Poli Tujuan')
            ->assertSee('Poli Layanan Uji')
            ->assertSee('08:00–12:00')
            ->assertSee('Tanggal Kunjungan');
    }

    #[Test]
    public function jam_di_dalam_jam_layanan_diterima(): void
    {
        $poli = Poli::create(['kode' => 'LAYANAN', 'nama' => 'Poli Layanan Uji', 'jam_buka' => '08:00', 'jam_tutup' => '12:00']);

        $this->post(route('register-pnpp.store'), $this->payload(['poli_dituju' => [$poli->id]]))
            ->assertRedirect(route('register-pnpp.success'))
            ->assertSessionHas('success_register_id');

        $this->assertSame(1, RegisterPnpp::count());
    }

    #[Test]
    public function jam_di_luar_jam_layanan_ditolak(): void
    {
        $poli = Poli::create(['kode' => 'LAYANAN', 'nama' => 'Poli Layanan Uji', 'jam_buka' => '08:00', 'jam_tutup' => '12:00']);

        $this->post(route('register-pnpp.store'), $this->payload([
            'poli_dituju' => [$poli->id],
            'rencana_jam_kunjungan' => '14:00',
        ]))
            ->assertSessionHasErrors('rencana_jam_kunjungan');

        $this->assertSame(0, RegisterPnpp::count());
    }

    #[Test]
    public function poli_buka_24_jam_menerima_jam_apa_saja(): void
    {
        $poli = Poli::create(['kode' => '24JAM', 'nama' => 'Poli 24 Jam']);

        $this->post(route('register-pnpp.store'), $this->payload([
            'poli_dituju' => [$poli->id],
            'rencana_jam_kunjungan' => '23:00',
        ]))
            ->assertRedirect(route('register-pnpp.success'))
            ->assertSessionHas('success_register_id');

        $this->assertSame(1, RegisterPnpp::count());
    }

    #[Test]
    public function jam_harus_dalam_semua_poli_tujuan_yang_dipilih(): void
    {
        $poliA = Poli::create(['kode' => 'A-LAYANAN', 'nama' => 'Poli A Layanan', 'jam_buka' => '08:00', 'jam_tutup' => '12:00']);
        $poliB = Poli::create(['kode' => 'B-LAYANAN', 'nama' => 'Poli B Layanan', 'jam_buka' => '13:00', 'jam_tutup' => '16:00']);

        // 13:00 masuk jam poliB tetapi keluar jam poliA → ditolak.
        $this->post(route('register-pnpp.store'), $this->payload([
            'poli_dituju' => [$poliA->id, $poliB->id],
            'rencana_jam_kunjungan' => '13:00',
        ]))
            ->assertSessionHasErrors('rencana_jam_kunjungan');

        $this->assertSame(0, RegisterPnpp::count());
    }
}
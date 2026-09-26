<?php

namespace Tests\Feature;

use App\Models\Poli;
use App\Models\RegisterPnpp;
use App\Models\Satker;
use App\Models\TujuanKunjungan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Asal satker pada pendaftaran PNPP publik: satker yang diketik manual
 * TIDAK membuat baris baru di tabel master `satkers`. Cocok dengan data
 * master → dipakai satker master-nya; di luar master → dipetakan ke satker
 * cadangan "Satker Lainnya" dengan nama asli ketikan tetap disimpan.
 */
class RegisterPnppSatkerTest extends TestCase
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

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'BUDI SANTOSO',
            'nik' => '1101010101010001',
            'nip' => '9901',
            'jabatan' => 'Bintara',
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
    public function satker_manual_yang_cocok_dengan_master_dipakai_langsung(): void
    {
        $satker = Satker::create(['kode' => 'SATKER-A', 'nama' => 'Dinas Kesehatan Kota Bogor']);
        $poli = Poli::create(['kode' => 'UJI-POLI', 'nama' => 'Poli Uji Satker']);

        // Ketikan memakai huruf kecil → tetap cocok dengan master (case-insensitive).
        $this->post(route('register-pnpp.store'), $this->payload([
            'satker_id' => null,
            'satker_baru' => '  dinas kesehatan kota bogor  ',
            'poli_dituju' => [$poli->id],
        ]))->assertRedirect(route('register-pnpp.success'));

        $reg = RegisterPnpp::latest('id')->first();

        $this->assertSame($satker->id, $reg->satker_id);
        $this->assertNull($reg->satker_lainnya);
        $this->assertFalse($reg->isSatkerLainnya());
        $this->assertSame('Dinas Kesehatan Kota Bogor', $reg->satkerNamaTampil());
    }

    #[Test]
    public function satker_manual_di_luar_master_dipetakan_ke_satker_lainnya(): void
    {
        $poli = Poli::create(['kode' => 'UJI-POLI', 'nama' => 'Poli Uji Satker']);
        $jumlahMaster = Satker::count();

        $this->post(route('register-pnpp.store'), $this->payload([
            'satker_id' => null,
            'satker_baru' => 'Polsek Bogor Tengah',
            'poli_dituju' => [$poli->id],
        ]))->assertRedirect(route('register-pnpp.success'));

        // Master tidak bertambah untuk satker ketikan — hanya satker cadangan
        // "Satker Lainnya" yang bisa muncul (dibuat on-demand, tidak pernah
        // membuat baris baru dari nama yang diketik pendaftar).
        $this->assertFalse(Satker::where('nama', 'Polsek Bogor Tengah')->exists());
        $this->assertTrue(Satker::where('kode', Satker::KODE_LAINNYA)->exists());
        $this->assertSame($jumlahMaster + 1, Satker::count());

        $reg = RegisterPnpp::latest('id')->first();

        $this->assertTrue($reg->isSatkerLainnya());
        $this->assertSame('Polsek Bogor Tengah', $reg->satker_lainnya);
        $this->assertSame('Polsek Bogor Tengah', $reg->satkerNamaAsli());
        $this->assertSame('Satker Lainnya · Polsek Bogor Tengah', $reg->satkerNamaTampil());
    }

    #[Test]
    public function admin_melihat_satker_lainnya_beserta_nama_asli(): void
    {
        $poli = Poli::create(['kode' => 'UJI-POLI', 'nama' => 'Poli Uji Satker']);

        $this->post(route('register-pnpp.store'), $this->payload([
            'satker_id' => null,
            'satker_baru' => 'Polsek Bogor Tengah',
            'poli_dituju' => [$poli->id],
        ]))->assertRedirect(route('register-pnpp.success'));

        $reg = RegisterPnpp::latest('id')->first();

        $this->assertTrue($reg->isSatkerLainnya());
        $this->assertSame('Polsek Bogor Tengah', $reg->satker_lainnya);

        $this->actingAs($this->superadmin());

        $this->get(route('admin.register-pnpp.index'))
            ->assertOk()
            ->assertSee('Satker Lainnya')
            ->assertSee('Polsek Bogor Tengah');

        $this->get(route('admin.register-pnpp.show', $reg))
            ->assertOk()
            ->assertSee('Satker Lainnya')
            ->assertSee('Polsek Bogor Tengah');
    }
}

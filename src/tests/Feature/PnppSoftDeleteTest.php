<?php

namespace Tests\Feature;

use App\Models\Dokter;
use App\Models\Kunjungan;
use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\Reminder;
use App\Models\Satker;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PnppSoftDeleteTest extends TestCase
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

    protected function admin(): User
    {
        return User::where('email', 'admin@gmail.com')->firstOrFail();
    }

    protected function pasangan(array $overrides = []): array
    {
        $satker = Satker::firstOrCreate(['kode' => 'TEST'], ['nama' => 'Satker Uji']);
        $poli = Poli::firstOrCreate(['kode' => 'UMUM-SD'], ['nama' => 'Poli Umum']);
        $poliLain = Poli::firstOrCreate(['kode' => 'GIGI-SD'], ['nama' => 'Poli Gigi']);
        $dokter = Dokter::firstOrCreate(['poli_id' => $poli->id, 'nama' => 'dr. Rina']);

        $pasien = Pnpp::create(array_merge([
            'nama' => 'Budi Santoso',
            'nip' => '9001',
            'no_bpjs' => '111333555777',
            'satker_id' => $satker->id,
            'no_hp' => '081234567890',
        ], $overrides));

        return compact('satker', 'poli', 'poliLain', 'dokter', 'pasien');
    }

    protected function buatReminder(Pnpp $pasien, Poli $poli, Dokter $dokter): Reminder
    {
        return Reminder::create([
            'pnpp_id' => $pasien->id,
            'poli_id' => $poli->id,
            'dokter_id' => $dokter->id,
            'tanggal' => today()->addDay()->format('Y-m-d'),
            'jam' => '09:30',
            'home_visit' => false,
            'status' => 'terjadwal',
        ]);
    }

    protected function buatKunjungan(Pnpp $pasien, Poli $poli): Kunjungan
    {
        return Kunjungan::create([
            'pnpp_id' => $pasien->id,
            'poli_id' => $poli->id,
            'tanggal_kunjungan' => today()->format('Y-m-d'),
            'home_visit' => false,
        ]);
    }

    #[Test]
    public function hapus_default_menjadi_soft_delete_dan_tidak_muncul_di_index(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        $this->delete(route('admin.pnpp.destroy', $pasien))
            ->assertRedirect(route('admin.pnpp.index'));

        $terhapus = Pnpp::withTrashed()->find($pasien->id);
        $this->assertNotNull($terhapus);
        $this->assertNotNull($terhapus->deleted_at);

        // Tidak ikut daftar index — NIP hanya muncul di baris tabel.
        $this->get(route('admin.pnpp.index'))
            ->assertOk()
            ->assertDontSee('NIP 9001');
    }

    #[Test]
    public function admin_bisa_soft_delete_tetapi_ditolak_hapus_permanen(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->admin());

        $this->delete(route('admin.pnpp.destroy', $pasien))
            ->assertRedirect(route('admin.pnpp.index'));

        $this->assertNotNull(Pnpp::withTrashed()->find($pasien->id)->deleted_at);

        $pasienKedua = $this->pasangan(['nip' => '9002'])['pasien'];

        $this->delete(route('admin.pnpp.force-destroy', $pasienKedua))
            ->assertForbidden();

        // Data tetap aman — tidak terhapus permanen.
        $this->assertNotNull(Pnpp::withTrashed()->find($pasienKedua->id));
        $this->assertNull(Pnpp::withTrashed()->find($pasienKedua->id)->deleted_at);
    }

    #[Test]
    public function hanya_superadmin_yang_bisa_hapus_permanen(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        $kunjungan = $this->buatKunjungan($pasien, $poli);
        $reminder = $this->buatReminder($pasien, $poli, $dokter);

        $this->delete(route('admin.pnpp.force-destroy', $pasien))
            ->assertRedirect(route('admin.pnpp.index'));

        // Baris benar-benar hilang (termasuk dengan withTrashed)…
        $this->assertNull(Pnpp::withTrashed()->find($pasien->id));
        $this->assertDatabaseMissing('pnpps', ['id' => $pasien->id]);

        // …beserta kunjungan dan penjadwalan yang menunjuknya (cascade).
        $this->assertNull(Kunjungan::find($kunjungan->id));
        $this->assertNull(Reminder::find($reminder->id));
    }

    #[Test]
    public function superadmin_bisa_hapus_permanen_data_yang_sudah_soft_delete(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        $this->delete(route('admin.pnpp.destroy', $pasien));
        $this->assertNotNull(Pnpp::withTrashed()->find($pasien->id)->deleted_at);

        // Ikatan route withTrashed() → data soft-deleted tetap bisa dibuka.
        $this->delete(route('admin.pnpp.force-destroy', $pasien))
            ->assertRedirect(route('admin.pnpp.index'));

        $this->assertNull(Pnpp::withTrashed()->find($pasien->id));
    }

    #[Test]
    public function nip_dan_no_bpjs_bisa_dipakai_ulang_setelah_soft_delete(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        // NIP masih dipakai pasien aktif → tolak duplikat (termasuk No. BPJS).
        $this->post(route('admin.pnpp.store'), [
            'nama' => 'Duplikat Aktif',
            'nip' => '9001',
            'no_bpjs' => '111333555777',
        ])->assertSessionHasErrors(['nip', 'no_bpjs']);

        $this->delete(route('admin.pnpp.destroy', $pasien));

        // Setelah soft delete, NIP/BPJS bisa dipakai lagi…
        $this->post(route('admin.pnpp.store'), [
            'nama' => 'Penerus NIP',
            'nip' => '9001',
            'no_bpjs' => '111333555777',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
        ])->assertRedirect(route('admin.pnpp.index'));

        // Hanya satu PNPP aktif yang memakai NIP/BPJS lama (soft-deleted tak dihitung).
        $this->assertSame(1, Pnpp::where('nip', '9001')->count());
    }

    #[Test]
    public function nama_pasien_soft_deleted_tetap_tampil_di_riwayat(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        // Pasien punya penjadwalan + kunjungan manual.
        $this->buatKunjungan($pasien, $poli);
        $this->buatReminder($pasien, $poli, $dokter);

        $this->delete(route('admin.pnpp.destroy', $pasien));
        $this->assertNotNull(Pnpp::withTrashed()->find($pasien->id)->deleted_at);

        // Riwayat tetap muncul dengan nama & NIP pasien (withTrashed eager load).
        $this->get(route('admin.digital-reminder.index'))
            ->assertOk()
            ->assertSee('NIP 9001');

        $this->get(route('admin.kunjungan.index'))
            ->assertOk()
            ->assertSee('NIP 9001');
    }

    #[Test]
    public function tombol_hapus_permanen_hanya_tampil_untuk_superadmin(): void
    {
        extract($this->pasangan());

        $admin = $this->admin();
        $this->actingAs($admin);
        $forceUrl = route('admin.pnpp.force-destroy', $pasien);

        $this->get(route('admin.pnpp.index'))
            ->assertOk()
            ->assertSee(route('admin.pnpp.destroy', $pasien))
            ->assertDontSee($forceUrl);

        $this->actingAs($this->superadmin())
            ->get(route('admin.pnpp.index'))
            ->assertOk()
            ->assertSee($forceUrl);
    }
}

<?php

namespace Tests\Feature;

use App\Models\HariLibur;
use App\Models\Poli;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * CRUD hari libur di admin + integrasi tombol sinkron Google Calendar:
 *  - superadmin/admin (pemegang "manage hari-libur") bisa kelola manual.
 *  - entri hasil sinkron (sumber google_calendar) tetap aman saat update.
 *  - pengguna tanpa permission ditolak (403) di semua aksi.
 *  - sinkron tanpa konfigurasi API key/ID menampilkan pesan error.
 */
class AdminHariLiburCrudTest extends TestCase
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

    /** Akun biasa (role "user") — tanpa permission manage hari-libur. */
    protected function tanpaPermission(): User
    {
        return User::where('email', 'user@gmail.com')->firstOrFail();
    }

    protected function tanggalUji(): string
    {
        return today()->addDays(7)->format('Y-m-d');
    }

    #[Test]
    public function superadmin_bisa_melihat_halaman_hari_libur(): void
    {
        $libur = HariLibur::create([
            'tanggal' => $this->tanggalUji(),
            'nama' => 'Cuti Bersama',
        ]);

        $this->actingAs($this->superadmin())
            ->get(route('admin.hari-libur.index'))
            ->assertOk()
            ->assertSee('Cuti Bersama');

        $this->actingAs($this->superadmin())
            ->get(route('admin.hari-libur.create'))
            ->assertOk()
            ->assertSee('Tambah Hari Libur');

        $this->actingAs($this->superadmin())
            ->get(route('admin.hari-libur.edit', $libur))
            ->assertOk()
            ->assertSee('Cuti Bersama');
    }

    #[Test]
    public function superadmin_menambah_hari_libur_manual_untuk_semua_poli(): void
    {
        $this->actingAs($this->superadmin())
            ->post(route('admin.hari-libur.store'), [
                'tanggal' => $this->tanggalUji(),
                'nama' => 'Tahun Baru',
                'poli_id' => '',
            ])
            ->assertRedirect(route('admin.hari-libur.index'));

        $this->assertDatabaseHas('hari_liburs', [
            'tanggal' => $this->tanggalUji(),
            'nama' => 'Tahun Baru',
            'poli_id' => null,
            'sumber' => HariLibur::SUMBER_MANUAL,
            'event_id' => null,
        ]);
    }

    #[Test]
    public function superadmin_menambah_hari_libur_khusus_poli(): void
    {
        $poli = Poli::firstOrCreate(['kode' => 'GIGI-UL'], ['nama' => 'Poli Gigi UL']);

        $this->actingAs($this->superadmin())
            ->post(route('admin.hari-libur.store'), [
                'tanggal' => $this->tanggalUji(),
                'nama' => 'Dinas Poli Gigi',
                'poli_id' => $poli->id,
            ])
            ->assertRedirect(route('admin.hari-libur.index'));

        $this->assertDatabaseHas('hari_liburs', [
            'tanggal' => $this->tanggalUji(),
            'nama' => 'Dinas Poli Gigi',
            'poli_id' => $poli->id,
        ]);
    }

    #[Test]
    public function tanggal_nama_dan_poli_rangkap_ditolak(): void
    {
        $this->actingAs($this->superadmin())->post(route('admin.hari-libur.store'), [
            'tanggal' => $this->tanggalUji(),
            'nama' => 'Cuti Bersama',
            'poli_id' => '',
        ])->assertRedirect(route('admin.hari-libur.index'));

        $this->actingAs($this->superadmin())
            ->post(route('admin.hari-libur.store'), [
                'tanggal' => $this->tanggalUji(),
                'nama' => 'Cuti Bersama',
                'poli_id' => '',
            ])
            ->assertSessionHasErrors('tanggal');

        $this->assertDatabaseCount('hari_liburs', 1);
    }

    #[Test]
    public function tanggal_lain_dengan_nama_sama_tetap_boleh(): void
    {
        $this->actingAs($this->superadmin())->post(route('admin.hari-libur.store'), [
            'tanggal' => $this->tanggalUji(),
            'nama' => 'Cuti Bersama',
            'poli_id' => '',
        ])->assertRedirect(route('admin.hari-libur.index'));

        $this->actingAs($this->superadmin())
            ->post(route('admin.hari-libur.store'), [
                'tanggal' => today()->addDays(9)->format('Y-m-d'),
                'nama' => 'Cuti Bersama',
                'poli_id' => '',
            ])
            ->assertRedirect(route('admin.hari-libur.index'));

        $this->assertDatabaseCount('hari_liburs', 2);
    }

    #[Test]
    public function validasi_mewajibkan_tanggal_dan_nama(): void
    {
        $this->actingAs($this->superadmin())
            ->post(route('admin.hari-libur.store'), [])
            ->assertSessionHasErrors(['tanggal', 'nama']);
    }

    #[Test]
    public function superadmin_mengubah_hari_libur(): void
    {
        $libur = HariLibur::create(['tanggal' => $this->tanggalUji(), 'nama' => 'Cuti Lama']);

        $this->actingAs($this->superadmin())
            ->put(route('admin.hari-libur.update', $libur), [
                'tanggal' => $this->tanggalUji(),
                'nama' => 'Cuti Baru',
                'poli_id' => '',
            ])
            ->assertRedirect(route('admin.hari-libur.index'));

        $this->assertDatabaseHas('hari_liburs', ['id' => $libur->id, 'nama' => 'Cuti Baru']);
    }

    #[Test]
    public function update_tidak_mengubah_sumber_dan_event_id_hasil_google(): void
    {
        $libur = HariLibur::create([
            'tanggal' => $this->tanggalUji(),
            'nama' => 'Hari Nasional',
            'sumber' => HariLibur::SUMBER_GOOGLE_CALENDAR,
            'event_id' => 'gc-evt-1',
        ]);

        $this->actingAs($this->superadmin())
            ->put(route('admin.hari-libur.update', $libur), [
                'tanggal' => $this->tanggalUji(),
                'nama' => 'Hari Nasional (rev)',
                'poli_id' => '',
            ])
            ->assertRedirect(route('admin.hari-libur.index'));

        $this->assertDatabaseHas('hari_liburs', [
            'id' => $libur->id,
            'nama' => 'Hari Nasional (rev)',
            'sumber' => HariLibur::SUMBER_GOOGLE_CALENDAR,
            'event_id' => 'gc-evt-1',
        ]);
    }

    #[Test]
    public function superadmin_menghapus_hari_libur(): void
    {
        $libur = HariLibur::create(['tanggal' => $this->tanggalUji(), 'nama' => 'Cuti Dihapus']);

        $this->actingAs($this->superadmin())
            ->delete(route('admin.hari-libur.destroy', $libur))
            ->assertRedirect(route('admin.hari-libur.index'));

        $this->assertDatabaseMissing('hari_liburs', ['id' => $libur->id]);
    }

    #[Test]
    public function pengguna_tanpa_permission_ditolak(): void
    {
        $user = $this->tanpaPermission();

        $this->actingAs($user)->get(route('admin.hari-libur.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.hari-libur.create'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.hari-libur.store'), [])->assertForbidden();
        $this->actingAs($user)->post(route('admin.hari-libur.sync'))->assertForbidden();
    }

    #[Test]
    public function sinkron_tanpa_konfigurasi_menampilkan_error(): void
    {
        config([
            'google-calendar.api_key' => null,
            'google-calendar.calendar_id' => null,
        ]);

        $this->actingAs($this->superadmin())
            ->from(route('admin.hari-libur.index'))
            ->post(route('admin.hari-libur.sync'))
            ->assertRedirect(route('admin.hari-libur.index'))
            ->assertSessionHas('error');
    }
}
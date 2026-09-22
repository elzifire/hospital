<?php

namespace Tests\Feature;

use App\Models\Dokter;
use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\Reminder;
use App\Models\Satker;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DigitalReminderSoftDeleteTest extends TestCase
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

    protected function pasangan(): array
    {
        $satker = Satker::firstOrCreate(['kode' => 'TEST'], ['nama' => 'Satker Uji']);
        $poli = Poli::firstOrCreate(['kode' => 'UMUM-SD'], ['nama' => 'Poli Umum']);
        $poliLain = Poli::firstOrCreate(['kode' => 'GIGI-SD'], ['nama' => 'Poli Gigi']);
        $dokter = Dokter::firstOrCreate(['poli_id' => $poli->id, 'nama' => 'dr. Rina']);

        $pasien = Pnpp::firstOrCreate(
            ['nip' => '123'],
            ['nama' => 'Budi Santoso', 'satker_id' => $satker->id, 'no_hp' => '081234567890'],
        );
        $pasien->update(['satker_id' => $satker->id, 'no_hp' => '081234567890']);

        return compact('satker', 'poli', 'poliLain', 'dokter', 'pasien');
    }

    protected function buatReminder(array $overrides = []): Reminder
    {
        extract($this->pasangan());

        return Reminder::create(array_merge([
            'pnpp_id' => $pasien->id,
            'poli_id' => $poli->id,
            'dokter_id' => $dokter->id,
            'tanggal' => today()->addDay()->format('Y-m-d'),
            'jam' => '09:30',
            'home_visit' => false,
            'status' => 'terjadwal',
        ], $overrides));
    }

    #[Test]
    public function hapus_default_menjadi_soft_delete_dan_tidak_muncul_di_index(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        $reminder = $this->buatReminder();

        $this->delete(route('admin.digital-reminder.destroy', $reminder))
            ->assertRedirect(route('admin.digital-reminder.index'));

        // Baris masih ada di database, namun ditandai soft delete.
        $terhapus = Reminder::withTrashed()->find($reminder->id);
        $this->assertNotNull($terhapus);
        $this->assertNotNull($terhapus->deleted_at);

        // Tidak ikut daftar index (karena soft delete) — NIP hanya muncul di baris tabel.
        $this->get(route('admin.digital-reminder.index'))
            ->assertOk()
            ->assertDontSee('NIP 123');
    }

    #[Test]
    public function admin_bisa_soft_delete_tetapi_ditolak_hapus_permanen(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->admin());

        $reminder = $this->buatReminder();

        $this->delete(route('admin.digital-reminder.destroy', $reminder))
            ->assertRedirect(route('admin.digital-reminder.index'));

        $this->assertNotNull(Reminder::withTrashed()->find($reminder->id)->deleted_at);

        $reminderKedua = $this->buatReminder();

        $this->delete(route('admin.digital-reminder.force-destroy', $reminderKedua))
            ->assertForbidden();

        // Data tetap aman — tidak terhapus permanen.
        $this->assertNotNull(Reminder::withTrashed()->find($reminderKedua->id));
        $this->assertNull(Reminder::withTrashed()->find($reminderKedua->id)->deleted_at);
    }

    #[Test]
    public function hanya_superadmin_yang_bisa_hapus_permanen(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        $reminder = $this->buatReminder();

        $this->delete(route('admin.digital-reminder.force-destroy', $reminder))
            ->assertRedirect(route('admin.digital-reminder.index'));

        // Baris benar-benar hilang dari database (termasuk withTrashed).
        $this->assertNull(Reminder::withTrashed()->find($reminder->id));
        $this->assertDatabaseMissing('reminders', ['id' => $reminder->id]);
    }

    #[Test]
    public function superadmin_bisa_hapus_permanen_jadwal_yang_sudah_soft_delete(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        $reminder = $this->buatReminder();

        $this->delete(route('admin.digital-reminder.destroy', $reminder));
        $this->assertNotNull(Reminder::withTrashed()->find($reminder->id)->deleted_at);

        // Ikatan route withTrashed() → jadwal soft-deleted tetap bisa dibuka.
        $this->delete(route('admin.digital-reminder.force-destroy', $reminder))
            ->assertRedirect(route('admin.digital-reminder.index'));

        $this->assertNull(Reminder::withTrashed()->find($reminder->id));
    }

    #[Test]
    public function akun_poli_bisa_soft_delete_polinya_sendiri_tapi_tidak_milik_poli_lain(): void
    {
        extract($this->pasangan());

        $user = User::create([
            'name' => 'Petugas Umum',
            'email' => 'petugas-umum@test.dev',
            'password' => 'rahasia',
        ]);
        $user->assignRole('poli');
        $user->userDetail()->updateOrCreate([], ['poli_id' => $poli->id]);
        $this->actingAs($user);

        $sendiri = $this->buatReminder();
        $this->delete(route('admin.digital-reminder.destroy', $sendiri))
            ->assertRedirect(route('admin.digital-reminder.index'));
        $this->assertNotNull(Reminder::withTrashed()->find($sendiri->id)->deleted_at);

        $poliLain = $this->buatReminder(['poli_id' => $poliLain->id]);
        $this->delete(route('admin.digital-reminder.destroy', $poliLain))
            ->assertForbidden();
        $this->assertNull(Reminder::withTrashed()->find($poliLain->id)->deleted_at);
    }

    #[Test]
    public function tombol_hapus_permanen_hanya_tampil_untuk_superadmin(): void
    {
        extract($this->pasangan());

        $admin = $this->admin();
        $this->actingAs($admin);

        $reminder = $this->buatReminder();

        $this->get(route('admin.digital-reminder.index'))
            ->assertOk()
            ->assertSee('Hapus')
            ->assertDontSee('Hapus Permanen');

        $this->actingAs($this->superadmin())
            ->get(route('admin.digital-reminder.index'))
            ->assertOk()
            ->assertSee('Hapus Permanen');
    }
}

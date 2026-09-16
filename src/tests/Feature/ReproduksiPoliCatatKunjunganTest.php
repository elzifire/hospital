<?php

namespace Tests\Feature;

use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\Reminder;
use App\Models\Satker;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReproduksiPoliCatatKunjunganTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function poliUser(Poli $poli): User
    {
        $user = User::create([
            'name' => 'Petugas Poli',
            'email' => 'petugas-poli@test.dev',
            'password' => 'rahasia',
        ]);
        $user->assignRole('poli');
        $user->userDetail()->updateOrCreate([], ['poli_id' => $poli->id]);
        return $user;
    }

    #[Test]
    public function buka_form_catat_kunjungan_sebagai_poli(): void
    {
        $satker = Satker::create(['kode' => 'KDT', 'nama' => 'Satker Uji']);
        $poliA = Poli::create(['kode' => 'KDT-A', 'nama' => 'Poli A']);
        $budi = Pnpp::create(['nama' => 'Budi Santoso', 'nip' => '9001', 'satker_id' => $satker->id]);
        $reminder = Reminder::create([
            'pnpp_id' => $budi->id,
            'poli_id' => $poliA->id,
            'tanggal' => today()->format('Y-m-d'),
            'jam' => '09:00',
            'status' => 'terjadwal',
        ]);

        $user = $this->poliUser($poliA);
        $this->assertSame($poliA->id, $user->refresh()->poliId());

        $this->actingAs($user);

        $this->get(route('admin.kunjungan.create'))
            ->assertOk()
            ->assertSee('Tambah Kunjungan');

        $this->get(route('admin.digital-reminder.edit', $reminder))
            ->assertOk()
            ->assertSee('Kunjungan Nyata');

        $this->post(route('admin.digital-reminder.kunjungan', $reminder), [
            'tanggal_kunjungan' => today()->format('Y-m-d'),
        ])->assertRedirect(route('admin.pnpp.kunjungan', $budi));

        $this->assertDatabaseHas('kunjungans', ['reminder_id' => $reminder->id]);
        $this->assertSame('selesai', $reminder->refresh()->status);
    }
}
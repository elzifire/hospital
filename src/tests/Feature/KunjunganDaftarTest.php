<?php

namespace Tests\Feature;

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

/**
 * Pemisahan Digital Reminder & Kunjungan: menu Digital Reminder menampilkan
 * index gabungan (jadwal + kunjungan manual), sedangkan menu Kunjungan
 * menampilkan halaman kunjungan tersendiri.
 */
class KunjunganDaftarTest extends TestCase
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

    protected function pasangan(): array
    {
        $satker = Satker::create(['kode' => 'KDT', 'nama' => 'Satker Uji']);
        $poliA = Poli::create(['kode' => 'KDT-A', 'nama' => 'Poli A']);
        $poliB = Poli::create(['kode' => 'KDT-B', 'nama' => 'Poli B']);

        $budi = Pnpp::create(['nama' => 'Budi Santoso', 'nip' => '9001', 'satker_id' => $satker->id]);

        return compact('satker', 'poliA', 'poliB', 'budi');
    }

    #[Test]
    public function index_digital_reminder_tetap_gabungan_dan_kunjungan_punya_halaman_sendiri(): void
    {
        extract($this->pasangan());

        // Kunjungan manual: dua poli hari yang sama untuk Budi.
        $budi->kunjungans()->createMany([
            ['poli_id' => $poliA->id, 'tanggal_kunjungan' => today()->format('Y-m-d'), 'diagnosa' => 'Hipertensi'],
            ['poli_id' => $poliB->id, 'tanggal_kunjungan' => today()->format('Y-m-d')],
        ]);

        // Penjadwalan yang belum dicatat (2 hari lagi).
        Reminder::create([
            'pnpp_id' => $budi->id,
            'poli_id' => $poliB->id,
            'tanggal' => today()->addDays(2)->format('Y-m-d'),
            'jam' => '10:00',
            'status' => 'terjadwal',
        ]);

        $this->actingAs($this->superadmin());

        // Digital Reminder: index gabungan (jadwal + kunjungan manual).
        $this->get(route('admin.digital-reminder.index'))
            ->assertOk()
            ->assertSee('Daftar Sesi')
            ->assertSee('Budi Santoso')
            ->assertSee('Poli A')
            ->assertSee('Poli B')
            ->assertSee('Realisasi Reminder')
            ->assertSee('Manual');

        // Kunjungan: halaman tersendiri berisi kunjungan tercatat.
        $this->get(route('admin.kunjungan.index'))
            ->assertOk()
            ->assertSee('Daftar Kunjungan')
            ->assertSee('Budi Santoso')
            ->assertSee('Poli A')
            ->assertSee('Poli B')
            ->assertSee('Manual');
    }

    #[Test]
    public function catat_dari_reminder_via_form_kunjungan(): void
    {
        extract($this->pasangan());

        $reminder = Reminder::create([
            'pnpp_id' => $budi->id,
            'poli_id' => $poliA->id,
            'tanggal' => today()->format('Y-m-d'),
            'jam' => '09:00',
            'status' => 'terjadwal',
        ]);

        $this->actingAs($this->superadmin())
            ->post(route('admin.kunjungan.catat'), [
                'reminder_id' => $reminder->id,
                'tanggal_kunjungan' => today()->format('Y-m-d'),
                'poli_pilih' => [$poliB->id],
                'polis' => [
                    $poliA->id => ['keluhan' => 'Pusing', 'diagnosa' => 'Hipertensi'],
                    $poliB->id => ['keluhan' => 'Gigi sakit'],
                ],
            ])->assertRedirect(route('admin.kunjungan.index'));

        // Poli terjadwal terhubung reminder; poli tambahan menjadi baris mandiri.
        $this->assertDatabaseHas('kunjungans', [
            'reminder_id' => $reminder->id,
            'pnpp_id' => $budi->id,
            'poli_id' => $poliA->id,
            'keluhan' => 'Pusing',
            'diagnosa' => 'Hipertensi',
        ]);
        $this->assertDatabaseHas('kunjungans', [
            'reminder_id' => null,
            'poli_id' => $poliB->id,
            'keluhan' => 'Gigi sakit',
        ]);
        $this->assertSame('selesai', $reminder->refresh()->status);

        // Penjadwalan yang sudah dicatat tidak bisa dicatat dua kali.
        $this->post(route('admin.kunjungan.catat'), [
            'reminder_id' => $reminder->id,
            'tanggal_kunjungan' => today()->format('Y-m-d'),
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame(2, Kunjungan::count());
    }

    #[Test]
    public function akun_poli_hanya_bisa_mencatat_dari_jadwal_polinya_sendiri(): void
    {
        extract($this->pasangan());

        $reminderLain = Reminder::create([
            'pnpp_id' => $budi->id,
            'poli_id' => $poliA->id,
            'tanggal' => today()->format('Y-m-d'),
            'jam' => '09:00',
            'status' => 'terjadwal',
        ]);

        $user = User::create([
            'name' => 'Petugas Poli B',
            'email' => 'petugas-b@test.dev',
            'password' => 'rahasia',
        ]);
        $user->assignRole('poli');
        $user->userDetail()->updateOrCreate([], ['poli_id' => $poliB->id]);

        // Jadwal poli lain → ditolak.
        $this->actingAs($user)->post(route('admin.kunjungan.catat'), [
            'reminder_id' => $reminderLain->id,
            'tanggal_kunjungan' => today()->format('Y-m-d'),
        ])->assertForbidden();

        // Jadwal polinya sendiri → boleh.
        $reminderSendiri = Reminder::create([
            'pnpp_id' => $budi->id,
            'poli_id' => $poliB->id,
            'tanggal' => today()->format('Y-m-d'),
            'jam' => '10:00',
            'status' => 'terjadwal',
        ]);

        $this->actingAs($user)->post(route('admin.kunjungan.catat'), [
            'reminder_id' => $reminderSendiri->id,
            'tanggal_kunjungan' => today()->format('Y-m-d'),
        ])->assertRedirect(route('admin.kunjungan.index'));

        $this->assertDatabaseHas('kunjungans', ['reminder_id' => $reminderSendiri->id, 'poli_id' => $poliB->id]);
        $this->assertSame('selesai', $reminderSendiri->refresh()->status);
    }
}

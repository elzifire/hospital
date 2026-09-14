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

class ReminderCrudTest extends TestCase
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

    /** Master + 2 pasien untuk skenario CRUD. */
    protected function pasangan(): array
    {
        $satker = Satker::create(['kode' => 'TEST', 'nama' => 'Satker Uji']);
        $poliUmum = Poli::create(['kode' => 'UMUM-RMD', 'nama' => 'Poli Umum']);
        $poliGigi = Poli::create(['kode' => 'GIGI-RMD', 'nama' => 'Poli Gigi']);
        $dokterUmum = Dokter::create(['poli_id' => $poliUmum->id, 'nama' => 'dr. Rina']);
        $dokterGigi = Dokter::create(['poli_id' => $poliGigi->id, 'nama' => 'drg. Anton']);

        $budi = Pnpp::create(['nama' => 'Budi Santoso', 'nip' => '123', 'satker_id' => $satker->id, 'no_hp' => '081234567890']);
        $siti = Pnpp::create(['nama' => 'Siti Aminah', 'nip' => '456', 'satker_id' => $satker->id]);

        return compact('satker', 'poliUmum', 'poliGigi', 'dokterUmum', 'dokterGigi', 'budi', 'siti');
    }

    #[Test]
    public function superadmin_membuat_jadwal_untuk_beberapa_pasien_dan_poli_sekaligus(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        $this->post(route('admin.digital-reminder.store'), [
            'pnpp_ids' => [$budi->id, $siti->id],
            'poli_ids' => [$poliUmum->id, $poliGigi->id],
            'tanggal' => today()->addDays(3)->format('Y-m-d'),
            'jam' => '09:30',
            'home_visit' => '1',
            'catatan' => 'Kontrol rutin',
        ])->assertRedirect(route('admin.digital-reminder.index'));

        // 2 pasien × 2 poli = 4 penjadwalan; dokter dibiarkan kosong
        // saat create (diisi per poli lewat edit).
        $this->assertSame(4, Reminder::count());

        $this->assertDatabaseHas('reminders', [
            'pnpp_id' => $budi->id,
            'poli_id' => $poliUmum->id,
            'dokter_id' => null,
            'tanggal' => today()->addDays(3)->format('Y-m-d'),
            'status' => 'terjadwal',
            'home_visit' => true,
            'catatan' => 'Kontrol rutin',
        ]);
        $this->assertDatabaseHas('reminders', ['pnpp_id' => $siti->id, 'poli_id' => $poliGigi->id, 'status' => 'terjadwal']);
    }

    #[Test]
    public function validasi_form_jadwal(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        $dasar = [
            'pnpp_ids' => [$budi->id],
            'poli_ids' => [$poliUmum->id],
            'tanggal' => today()->addDay()->format('Y-m-d'),
            'jam' => '09:00',
        ];

        // Tanpa pasien → ditolak
        $this->post(route('admin.digital-reminder.store'), collect($dasar)->except('pnpp_ids')->all())
            ->assertSessionHasErrors('pnpp_ids');

        // Tanpa poli → ditolak
        $this->post(route('admin.digital-reminder.store'), collect($dasar)->except('poli_ids')->all())
            ->assertSessionHasErrors('poli_ids');

        // Tanggal masa lalu → ditolak saat membuat (koreksi jadwal lama hanya lewat edit)
        $this->post(route('admin.digital-reminder.store'), array_merge($dasar, ['tanggal' => '2020-01-01']))
            ->assertSessionHasErrors('tanggal');
    }

    #[Test]
    public function edit_bisa_mengoreksi_status_secara_manual(): void
    {
        extract($this->pasangan());

        $reminder = Reminder::create([
            'pnpp_id' => $budi->id,
            'poli_id' => $poliUmum->id,
            'tanggal' => today()->subDays(2)->format('Y-m-d'),
            'jam' => '08:00',
            'status' => 'tidak_datang',
        ]);

        $this->actingAs($this->superadmin())
            ->get(route('admin.digital-reminder.edit', $reminder))
            ->assertOk()
            ->assertSee('Budi Santoso');

        $this->put(route('admin.digital-reminder.update', $reminder), [
            'poli_id' => $poliUmum->id,
            'dokter_id' => $dokterUmum->id,
            'tanggal' => $reminder->tanggal->format('Y-m-d'),
            'jam' => '08:00',
            'home_visit' => '0',
            'status' => 'selesai',
            'catatan' => 'Ternyata datang, lupa dicatat',
        ])->assertRedirect(route('admin.digital-reminder.index'));

        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'status' => 'selesai', 'catatan' => 'Ternyata datang, lupa dicatat']);
    }

    #[Test]
    public function catat_kunjungan_mencatat_poli_terjadwal_dan_poli_lain(): void
    {
        extract($this->pasangan());

        $reminder = Reminder::create([
            'pnpp_id' => $budi->id,
            'poli_id' => $poliUmum->id,
            'dokter_id' => $dokterUmum->id,
            'tanggal' => today()->format('Y-m-d'),
            'jam' => '09:00',
            'status' => 'terjadwal',
        ]);

        $this->actingAs($this->superadmin())
            ->post(route('admin.digital-reminder.kunjungan', $reminder), [
                'tanggal_kunjungan' => today()->format('Y-m-d'),
                'poli_pilih' => [$poliGigi->id],
                'polis' => [
                    $poliUmum->id => ['keluhan' => 'Pusing', 'diagnosa' => 'Hipertensi derajat 1'],
                    $poliGigi->id => ['keluhan' => 'Gigi berlubang', 'diagnosa' => 'Karies'],
                ],
            ])->assertRedirect(route('admin.pnpp.kunjungan', $budi));

        // Baris poli terjadwal terhubung ke reminder; poli lain lepas.
        $this->assertSame(2, Kunjungan::count());
        $this->assertDatabaseHas('kunjungans', [
            'reminder_id' => $reminder->id,
            'pnpp_id' => $budi->id,
            'poli_id' => $poliUmum->id,
            'keluhan' => 'Pusing',
            'diagnosa' => 'Hipertensi derajat 1',
        ]);
        $this->assertDatabaseHas('kunjungans', [
            'reminder_id' => null,
            'poli_id' => $poliGigi->id,
            'keluhan' => 'Gigi berlubang',
            'diagnosa' => 'Karies',
        ]);
        $this->assertSame('selesai', $reminder->refresh()->status);

        // Penjadwalan yang sudah punya kunjungan tidak bisa dicatat dua kali
        $this->post(route('admin.digital-reminder.kunjungan', $reminder), [
            'tanggal_kunjungan' => today()->format('Y-m-d'),
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame(1, $reminder->kunjungan()->count());
        $this->assertSame(2, Kunjungan::count());
    }

    #[Test]
    public function hapus_penjadwalan(): void
    {
        extract($this->pasangan());

        $reminder = Reminder::create([
            'pnpp_id' => $budi->id,
            'poli_id' => $poliUmum->id,
            'tanggal' => today()->addDay()->format('Y-m-d'),
            'jam' => '09:00',
            'status' => 'terjadwal',
        ]);

        $this->actingAs($this->superadmin())
            ->delete(route('admin.digital-reminder.destroy', $reminder))
            ->assertRedirect(route('admin.digital-reminder.index'));

        $this->assertModelMissing($reminder);
    }

    #[Test]
    public function user_biasa_ditolak_dari_modul_digital_reminder(): void
    {
        $user = User::where('email', 'user@gmail.com')->firstOrFail();

        $this->actingAs($user)->get(route('admin.digital-reminder.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.digital-reminder.create'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.digital-reminder.store'), [])->assertForbidden();
    }
}

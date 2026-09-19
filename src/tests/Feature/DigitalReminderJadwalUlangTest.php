<?php

namespace Tests\Feature;

use App\Broadcasting\BroadcastService;
use App\Models\Dokter;
use App\Models\Kunjungan;
use App\Models\MessageLog;
use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\Reminder;
use App\Models\Satker;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DigitalReminderJadwalUlangTest extends TestCase
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
        $satker = Satker::firstOrCreate(['kode' => 'TEST'], ['nama' => 'Satker Uji']);
        $poli = Poli::firstOrCreate(['kode' => 'UMUM-RMD'], ['nama' => 'Poli Umum']);
        $dokter = Dokter::firstOrCreate(['poli_id' => $poli->id, 'nama' => 'dr. Rina']);

        $pasien = Pnpp::firstOrCreate(
            ['nip' => '123'],
            ['nama' => 'Budi Santoso', 'satker_id' => $satker->id, 'no_hp' => '081234567890'],
        );
        $pasien->update(['satker_id' => $satker->id, 'no_hp' => '081234567890']);

        return compact('satker', 'poli', 'dokter', 'pasien');
    }

    protected function buatReminder(array $overrides = []): Reminder
    {
        extract($this->pasangan());

        return Reminder::create(array_merge([
            'pnpp_id' => $pasien->id,
            'poli_id' => $poli->id,
            'dokter_id' => $dokter->id,
            'message_template_id' => null,
            'tanggal' => today()->addDay()->format('Y-m-d'),
            'jam' => '09:30',
            'home_visit' => false,
            'status' => 'terjadwal',
            'catatan' => null,
        ], $overrides));
    }

    protected function buatLog(Reminder $reminder, string $status, string $rule = 'h-1'): MessageLog
    {
        return MessageLog::create([
            'jenis' => 'follow_up',
            'rule' => $rule,
            'reminder_id' => $reminder->id,
            'pnpp_id' => $reminder->pnpp_id,
            'penerima_nama' => $reminder->pnpp?->nama,
            'penerima_no_hp' => '6281234567890',
            'konten' => 'Pesan undangan jadwal.',
            'status' => $status,
        ]);
    }

    #[Test]
    public function jadwal_ulang_sukses_membuat_baris_baru_dan_membatalkan_pesan_lama(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        $reminder = $this->buatReminder(['catatan' => 'Kontrol rutin']);
        $menunggu = $this->buatLog($reminder, 'menunggu');
        $terkirim = $this->buatLog($reminder, 'terkirim', 'h-7');

        $this->post(route('admin.digital-reminder.jadwal-ulang.store', $reminder), [
            'tanggal' => today()->addDays(7)->format('Y-m-d'),
            'jam' => '14:00',
            'catatan' => 'Pasien minta reschedule',
        ])->assertRedirect(route('admin.digital-reminder.index'));

        // Jadwal lama ditandai jadwal_ulang + catatan riwayat.
        $lama = $reminder->refresh();
        $this->assertSame('jadwal_ulang', $lama->status);
        $this->assertStringContainsString('dijadwal ulang ke', $lama->catatan);

        // Baris baru: terjadwal, tanggal/jam baru, field lain tersalin.
        $baru = Reminder::where('id', '!=', $lama->id)->firstOrFail();
        $this->assertSame('terjadwal', $baru->status);
        $this->assertSame($lama->pnpp_id, $baru->pnpp_id);
        $this->assertSame($lama->poli_id, $baru->poli_id);
        $this->assertSame($lama->dokter_id, $baru->dokter_id);
        $this->assertSame(today()->addDays(7)->format('Y-m-d'), $baru->tanggal->format('Y-m-d'));
        $this->assertSame('14:00', $baru->jam->format('H:i'));
        $this->assertStringContainsString('Dijadwal ulang dari', $baru->catatan);

        // Pesan menunggu lama dibatalkan; pesan terkirim tidak disentuh.
        $this->assertSame('dibatalkan', $menunggu->refresh()->status);
        $this->assertSame('terkirim', $terkirim->refresh()->status);
    }

    #[Test]
    public function jadwal_yang_sudah_dijadwalkan_ulang_tidak_tampil_di_follow_up(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        // Pasien A hanya punya jadwal lama (status jadwal_ulang) → tidak tampil.
        $pasienA = $this->buatReminder(['status' => 'jadwal_ulang', 'tanggal' => today()->subDay()->format('Y-m-d')]);

        // Pasien B punya jadwal lama + jadwal hasil reschedule → tampil via yang baru.
        $pasienB = Pnpp::create(['nama' => 'Siti Aminah', 'nip' => '456', 'satker_id' => $satker->id, 'no_hp' => '081298765432']);
        Reminder::create(['pnpp_id' => $pasienB->id, 'poli_id' => $poli->id, 'dokter_id' => $dokter->id, 'status' => 'jadwal_ulang', 'tanggal' => today()->subDay()->format('Y-m-d'), 'jam' => '09:00']);
        $baruB = Reminder::create(['pnpp_id' => $pasienB->id, 'poli_id' => $poli->id, 'dokter_id' => $dokter->id, 'status' => 'terjadwal', 'tanggal' => today()->addDays(10)->format('Y-m-d'), 'jam' => '13:00']);

        // Jadwal lewat berstatus jadwal_ulang tidak ikut di-sweep jadi tidak datang.
        $this->assertSame(0, app(BroadcastService::class)->sweepStatus());

        $this->get(route('admin.follow-up.index'))
            ->assertOk()
            ->assertDontSee('Budi Santoso')
            ->assertSee('Siti Aminah');

        $html = $this->get(route('admin.follow-up.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Budi Santoso', $html);
        $this->assertStringContainsString('Siti Aminah', $html);

        // Status baris baru tetap terjadwal (belum berkunjung).
        $this->assertSame('terjadwal', $baruB->refresh()->status);
    }

    #[Test]
    public function jadwal_selesai_dengan_kunjungan_ditolak_jadwal_ulang(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        $reminder = $this->buatReminder(['status' => 'selesai', 'tanggal' => today()->subDay()->format('Y-m-d')]);
        Kunjungan::create([
            'pnpp_id' => $pasien->id,
            'reminder_id' => $reminder->id,
            'poli_id' => $poli->id,
            'tanggal_kunjungan' => today()->subDay()->format('Y-m-d'),
        ]);

        $this->get(route('admin.digital-reminder.jadwal-ulang', $reminder))->assertSessionHas('error');
        $this->post(route('admin.digital-reminder.jadwal-ulang.store', $reminder), [
            'tanggal' => today()->addDays(7)->format('Y-m-d'),
            'jam' => '11:00',
        ])->assertSessionHas('error');

        $this->assertSame(1, Reminder::count());
    }

    #[Test]
    public function jadwal_dibatalkan_ditolak_jadwal_ulang(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        $reminder = $this->buatReminder(['status' => 'dibatalkan']);

        $this->get(route('admin.digital-reminder.jadwal-ulang', $reminder))->assertSessionHas('error');
        $this->post(route('admin.digital-reminder.jadwal-ulang.store', $reminder), [
            'tanggal' => today()->addDays(7)->format('Y-m-d'),
            'jam' => '11:00',
        ])->assertSessionHas('error');

        $this->assertSame(1, Reminder::count());
    }

    #[Test]
    public function tanggal_kemarin_ditolak_dan_tanggal_jam_sama_ditolak(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        $reminder = $this->buatReminder();

        // Tanggal di masa lalu → error validasi.
        $this->post(route('admin.digital-reminder.jadwal-ulang.store', $reminder), [
            'tanggal' => today()->subDay()->format('Y-m-d'),
            'jam' => '09:30',
        ])->assertSessionHasErrors('tanggal');

        // Tanggal/jam sama persis dengan jadwal lama → ditolak.
        $this->post(route('admin.digital-reminder.jadwal-ulang.store', $reminder), [
            'tanggal' => $reminder->tanggal->format('Y-m-d'),
            'jam' => $reminder->jam->format('H:i'),
        ])->assertSessionHas('error');

        $this->assertSame(1, Reminder::count());
    }

    #[Test]
    public function akun_poli_tidak_bisa_jadwal_ulang_poli_lain(): void
    {
        extract($this->pasangan());
        $poliLain = Poli::create(['kode' => 'GIGI-RMD', 'nama' => 'Poli Gigi']);

        $user = User::create(['name' => 'Petugas Umum', 'email' => 'petugas-umum@test.dev', 'password' => 'rahasia']);
        $user->assignRole('poli');
        $user->userDetail()->updateOrCreate([], ['poli_id' => $poli->id]);

        $reminder = $this->buatReminder(['poli_id' => $poliLain->id]);

        $this->actingAs($user)
            ->get(route('admin.digital-reminder.jadwal-ulang', $reminder))
            ->assertForbidden();
        $this->actingAs($user)
            ->post(route('admin.digital-reminder.jadwal-ulang.store', $reminder), [
                'tanggal' => today()->addDays(7)->format('Y-m-d'),
                'jam' => '11:00',
            ])
            ->assertForbidden();
    }
}

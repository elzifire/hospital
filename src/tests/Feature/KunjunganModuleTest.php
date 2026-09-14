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
 * Modul Kunjungan — satu pasien bisa ke beberapa poli dalam satu
 * tanggal (1 baris per poli), dari halaman pasien maupun form mandiri.
 */
class KunjunganModuleTest extends TestCase
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

    /** Master + pasien + poli untuk skenario multi-poli. */
    protected function pasangan(): array
    {
        $satker = Satker::create(['kode' => 'TEST', 'nama' => 'Satker Uji']);
        $poliUmum = Poli::create(['kode' => 'UMUM-KUJ', 'nama' => 'Poli Umum']);
        $poliGigi = Poli::create(['kode' => 'GIGI-KUJ', 'nama' => 'Poli Gigi']);
        $poliJantung = Poli::create(['kode' => 'JANTUNG-KUJ', 'nama' => 'Poli Jantung']);

        $budi = Pnpp::create(['nama' => 'Budi Santoso', 'nip' => '123', 'satker_id' => $satker->id]);

        return compact('satker', 'poliUmum', 'poliGigi', 'poliJantung', 'budi');
    }

    #[Test]
    public function menambah_kunjungan_multi_poli_dari_halaman_pasien(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        $this->post(route('admin.pnpp.kunjungan.store', $budi), [
            'tanggal_kunjungan' => '2026-09-05',
            'polis' => [
                ['poli_id' => $poliUmum->id, 'keluhan' => 'Pusing', 'diagnosa' => 'Hipertensi'],
                ['poli_id' => $poliJantung->id, 'keluhan' => 'Nyeri dada', 'diagnosa' => 'Angina'],
            ],
        ])->assertRedirect(route('admin.pnpp.kunjungan', $budi));

        // Dua poli pada tanggal yang sama = dua baris kunjungan.
        $this->assertSame(2, Kunjungan::where('pnpp_id', $budi->id)->count());
        $this->assertDatabaseHas('kunjungans', ['pnpp_id' => $budi->id, 'poli_id' => $poliUmum->id, 'tanggal_kunjungan' => '2026-09-05', 'keluhan' => 'Pusing']);
        $this->assertDatabaseHas('kunjungans', ['pnpp_id' => $budi->id, 'poli_id' => $poliJantung->id, 'tanggal_kunjungan' => '2026-09-05', 'diagnosa' => 'Angina']);
    }

    #[Test]
    public function validasi_form_kunjungan(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        // Tanpa baris poli → ditolak
        $this->post(route('admin.pnpp.kunjungan.store', $budi), ['tanggal_kunjungan' => '2026-09-05'])
            ->assertSessionHasErrors('polis');

        // Poli tidak valid → ditolak
        $this->post(route('admin.pnpp.kunjungan.store', $budi), [
            'tanggal_kunjungan' => '2026-09-05',
            'polis' => [['poli_id' => 999999]],
        ])->assertSessionHasErrors('polis.0.poli_id');
    }

    #[Test]
    public function menambah_kunjungan_dari_form_mandiri_modul(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        $this->get(route('admin.kunjungan.create', ['q' => 'Budi']))->assertOk()->assertSee('Budi Santoso');

        $this->post(route('admin.kunjungan.store'), [
            'pnpp_id' => $budi->id,
            'tanggal_kunjungan' => '2026-09-05',
            'polis' => [
                ['poli_id' => $poliUmum->id, 'keluhan' => 'Demam'],
                ['poli_id' => $poliGigi->id, 'keluhan' => 'Gigi sakit'],
            ],
        ])->assertRedirect(route('admin.kunjungan.index'));

        $this->assertSame(2, Kunjungan::where('pnpp_id', $budi->id)->count());
    }

    #[Test]
    public function halaman_utama_mengelompokkan_poli_per_pasien_dan_tanggal(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        // Dua poli hari ini + satu poli kemarin untuk pasien yang sama.
        // Diagnosa unik sebagai penanda baris jantung di tabel.
        $budi->kunjungans()->createMany([
            ['poli_id' => $poliUmum->id, 'tanggal_kunjungan' => today()->format('Y-m-d'), 'diagnosa' => 'Hipertensi'],
            ['poli_id' => $poliGigi->id, 'tanggal_kunjungan' => today()->format('Y-m-d')],
            ['poli_id' => $poliJantung->id, 'tanggal_kunjungan' => today()->subDay()->format('Y-m-d'), 'diagnosa' => 'Angina Pektoris Unik'],
        ]);

        $response = $this->get(route('admin.kunjungan.index'))->assertOk();

        // Nama pasien & semua poli muncul dalam satu tabel berbasis grup.
        $response->assertSee('Budi Santoso');
        $response->assertSee('Poli Umum');
        $response->assertSee('Poli Gigi');
        $response->assertSee('Poli Jantung');
        $response->assertSee('Angina Pektoris Unik');

        // Statistik & kolom sumber (semua kunjungan ini manual, tanpa reminder).
        $response->assertSee('Realisasi Reminder');
        $response->assertSee('Manual');

        // Filter poli menyaring baris — baris jantung hilang dari tabel
        // & sidebar terbaru (grafik per-poli memang tetap membandingkan
        // semua poli).
        $this->get(route('admin.kunjungan.index', ['poli' => $poliGigi->id]))
            ->assertOk()
            ->assertSee('Poli Gigi')
            ->assertDontSee('Angina Pektoris Unik');

        // Filter periode "hari ini" menyembunyikan kunjungan kemarin.
        $this->get(route('admin.kunjungan.index', ['periode' => 'hari-ini']))
            ->assertOk()
            ->assertDontSee('Angina Pektoris Unik');

        // Halaman riwayat pasien menampilkan grup "2 poli".
        $this->get(route('admin.pnpp.kunjungan', $budi))
            ->assertOk()
            ->assertSee('2 poli');
    }

    #[Test]
    public function menghapus_satu_baris_poli(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        $budi->kunjungans()->createMany([
            ['poli_id' => $poliUmum->id, 'tanggal_kunjungan' => '2026-09-05'],
            ['poli_id' => $poliGigi->id, 'tanggal_kunjungan' => '2026-09-05'],
        ]);

        $target = Kunjungan::where('poli_id', $poliGigi->id)->firstOrFail();

        $this->delete(route('admin.pnpp.kunjungan.destroy', [$budi, $target]))
            ->assertRedirect(route('admin.pnpp.kunjungan', $budi));

        $this->assertModelMissing($target);
        $this->assertSame(1, Kunjungan::where('pnpp_id', $budi->id)->count());
    }

    #[Test]
    public function realisasi_reminder_mencatat_poli_terjadwal_tanpa_checklist_tambahan(): void
    {
        extract($this->pasangan());

        $reminder = Reminder::create([
            'pnpp_id' => $budi->id,
            'poli_id' => $poliUmum->id,
            'tanggal' => today()->format('Y-m-d'),
            'jam' => '09:00',
            'status' => 'terjadwal',
        ]);

        $this->actingAs($this->superadmin())
            ->post(route('admin.digital-reminder.kunjungan', $reminder), [
                'tanggal_kunjungan' => today()->format('Y-m-d'),
                'keluhan' => 'Pusing',
            ])->assertRedirect(route('admin.pnpp.kunjungan', $budi));

        // Tanpa poli lain dicentang → hanya poli terjadwal yang tercatat.
        $this->assertSame(1, Kunjungan::count());
        $this->assertDatabaseHas('kunjungans', [
            'reminder_id' => $reminder->id,
            'poli_id' => $poliUmum->id,
            'keluhan' => 'Pusing',
        ]);
        $this->assertSame('selesai', $reminder->refresh()->status);
    }

    #[Test]
    public function mengedit_baris_poli(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        $budi->kunjungans()->createMany([
            ['poli_id' => $poliUmum->id, 'tanggal_kunjungan' => '2026-09-05', 'keluhan' => 'Pusing'],
            ['poli_id' => $poliGigi->id, 'tanggal_kunjungan' => '2026-09-05'],
        ]);

        $target = Kunjungan::where('poli_id', $poliUmum->id)->firstOrFail();

        // Form edit terbuka dan terisi nilai lama.
        $this->get(route('admin.pnpp.kunjungan.edit', [$budi, $target]))
            ->assertOk()
            ->assertSee('Pusing');

        // Simpan koreksi — tanggal, poli, keluhan, diagnosa bebas diubah.
        $this->put(route('admin.pnpp.kunjungan.update', [$budi, $target]), [
            'tanggal_kunjungan' => '2026-09-06',
            'poli_id' => $poliJantung->id,
            'keluhan' => 'Nyeri dada',
            'diagnosa' => 'Angina',
        ])->assertRedirect(route('admin.pnpp.kunjungan', $budi));

        $this->assertDatabaseHas('kunjungans', [
            'id' => $target->id,
            'poli_id' => $poliJantung->id,
            'tanggal_kunjungan' => '2026-09-06',
            'keluhan' => 'Nyeri dada',
            'diagnosa' => 'Angina',
        ]);

        // Baris poli lain pada tanggal yang sama tidak tersentuh.
        $this->assertDatabaseHas('kunjungans', ['pnpp_id' => $budi->id, 'poli_id' => $poliGigi->id, 'tanggal_kunjungan' => '2026-09-05']);

        // Poli tidak valid → ditolak.
        $this->put(route('admin.pnpp.kunjungan.update', [$budi, $target]), [
            'tanggal_kunjungan' => '2026-09-06',
            'poli_id' => 999999,
        ])->assertSessionHasErrors('poli_id');
    }

    #[Test]
    public function baris_poli_pasien_lain_tidak_bisa_diedit(): void
    {
        extract($this->pasangan());
        $this->actingAs($this->superadmin());

        $budi->kunjungans()->create(['poli_id' => $poliUmum->id, 'tanggal_kunjungan' => '2026-09-05']);
        $siti = Pnpp::create(['nama' => 'Siti Aminah', 'nip' => '456']);
        $target = Kunjungan::firstOrFail();

        // Baris milik Budi dibuka lewat URL pasien Siti → 404.
        $this->get(route('admin.pnpp.kunjungan.edit', [$siti, $target]))->assertNotFound();
        $this->put(route('admin.pnpp.kunjungan.update', [$siti, $target]), [
            'tanggal_kunjungan' => '2026-09-06',
            'poli_id' => $poliUmum->id,
        ])->assertNotFound();

        $this->assertDatabaseHas('kunjungans', ['id' => $target->id, 'tanggal_kunjungan' => '2026-09-05']);
    }

    #[Test]
    public function user_biasa_ditolak_dari_modul_kunjungan(): void
    {
        extract($this->pasangan());
        $user = User::where('email', 'user@gmail.com')->firstOrFail();

        $this->actingAs($user)->get(route('admin.kunjungan.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.kunjungan.create'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.kunjungan.store'), [])->assertForbidden();
        $this->actingAs($user)->get(route('admin.pnpp.kunjungan', $budi))->assertForbidden();
        $this->actingAs($user)->post(route('admin.pnpp.kunjungan.store', $budi), [])->assertForbidden();
    }
}

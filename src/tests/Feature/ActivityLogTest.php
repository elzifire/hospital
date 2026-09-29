<?php

namespace Tests\Feature;

use App\Models\Poli;
use App\Models\RegisterPnpp;
use App\Models\Satker;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Jejak perubahan data (before & after) via spatie/laravel-activitylog:
 *  - model penting mencatat create/update/delete otomatis ke activity_log.
 *  - causer = user yang login, properties berisi nilai lama & baru.
 *  - halaman admin Log Aktivitas (index + detail) hanya untuk yang punya
 *    permission "manage activity-log".
 */
class ActivityLogTest extends TestCase
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

    protected function tanpaPermission(): User
    {
        return User::where('email', 'user@gmail.com')->firstOrFail();
    }

    private function buatSatker(): Satker
    {
        return Satker::firstOrCreate(['kode' => 'LOG-AKTIVITAS'], ['nama' => 'Satker Log']);
    }

    #[Test]
    public function pembuatan_record_mencatat_aktivitas_dengan_causer(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $poli = Poli::create(['kode' => 'LA-A', 'nama' => 'Poli Log A']);

        $aktivitas = Activity::where('subject_type', Poli::class)
            ->where('subject_id', $poli->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($aktivitas, 'Pembuatan Poli harus tercatat di activity_log.');
        $this->assertSame($admin->getMorphClass(), $aktivitas->causer_type);
        $this->assertSame($admin->id, $aktivitas->causer_id);
        $this->assertSame('Poli Log A', $aktivitas->changes()['attributes']['nama'] ?? null);
    }

    #[Test]
    public function update_mencatat_nilai_lama_dan_baru_per_kolom(): void
    {
        $this->actingAs($this->admin());

        $poli = Poli::create(['kode' => 'LA-B', 'nama' => 'Poli Lama']);
        $poli->update(['nama' => 'Poli Baru']);

        $aktivitas = Activity::where('subject_type', Poli::class)
            ->where('subject_id', $poli->id)
            ->where('event', 'updated')
            ->latest()
            ->first();

        $this->assertNotNull($aktivitas);
        $perubahan = $aktivitas->changes();
        $this->assertSame('Poli Lama', $perubahan['old']['nama'] ?? null);
        $this->assertSame('Poli Baru', $perubahan['attributes']['nama'] ?? null);
    }

    #[Test]
    public function penghapusan_mencatat_aktivitas_delete(): void
    {
        $this->actingAs($this->admin());

        $poli = Poli::create(['kode' => 'LA-C', 'nama' => 'Poli Hapus']);
        $poli->delete();

        $aktivitas = Activity::where('subject_type', Poli::class)
            ->where('subject_id', $poli->id)
            ->where('event', 'deleted')
            ->latest()
            ->first();

        $this->assertNotNull($aktivitas);
        $this->assertSame('Poli Hapus', $aktivitas->changes()['old']['nama'] ?? null);
    }

    #[Test]
    public function field_sensitif_password_tidak_dicatat(): void
    {
        $this->actingAs($this->superadmin());

        $user = User::create([
            'name' => 'Aktivitas Uji',
            'email' => 'aktivitas-uji@test.dev',
            'password' => 'rahasia-lama',
        ]);
        $user->update(['password' => 'rahasia-baru', 'email' => 'aktivitas-uji-2@test.dev']);

        $aktivitas = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'updated')
            ->latest()
            ->first();

        $this->assertNotNull($aktivitas);
        $perubahan = $aktivitas->changes()['attributes'] ?? [];
        $this->assertArrayHasKey('email', $perubahan);
        $this->assertArrayNotHasKey('password', $perubahan, 'Password tidak boleh masuk log.');
    }

    #[Test]
    public function nik_dan_no_hp_dimascing_saat_masuk_log(): void
    {
        $this->actingAs($this->admin());

        $satker = $this->buatSatker();
        $reg = RegisterPnpp::create([
            'nama' => 'MASK NIK',
            'nik' => '1101010101010001',
            'nip' => '9301010101',
            'jabatan' => 'Bintara',
            'satker_id' => $satker->id,
            'ttl' => 'Bogor, 01-01-1990',
            'alamat' => 'Jl. Rahasia No. 9',
            'no_hp' => '081234567890',
            'rencana_tanggal_kunjungan' => today()->addDays(3)->format('Y-m-d'),
            'rencana_jam_kunjungan' => '09:30',
            'status' => RegisterPnpp::STATUS_BELUM_DISETUJUI,
        ]);

        $aktivitas = Activity::where('subject_type', RegisterPnpp::class)
            ->where('subject_id', $reg->id)
            ->where('event', 'created')
            ->firstOrFail();

        $atribut = $aktivitas->changes()['attributes'];

        $this->assertSame('************0001', $atribut['nik'] ?? null);
        $this->assertSame('********7890', $atribut['no_hp'] ?? null);
        $this->assertStringContainsString('*', $atribut['ttl'] ?? '');
        $this->assertStringContainsString('*', $atribut['alamat'] ?? '');
        $this->assertStringNotContainsString('0890', $atribut['no_hp'] ?? '');
    }

    #[Test]
    public function nilai_lama_dan_baru_sama_sama_dimascing(): void
    {
        $this->actingAs($this->admin());

        $poli = Poli::create(['kode' => 'LA-E', 'nama' => 'Poli Mask']);
        $poli->update(['nama' => 'Poli Mask Baru']);

        // nama (non-sensitif) tetap apa adanya di before & after.
        $aktivitas = Activity::where('subject_type', Poli::class)
            ->where('subject_id', $poli->id)
            ->where('event', 'updated')
            ->latest()
            ->first();

        $this->assertNotNull($aktivitas);
        $this->assertSame('Poli Mask', $aktivitas->changes()['old']['nama'] ?? null);
        $this->assertSame('Poli Mask Baru', $aktivitas->changes()['attributes']['nama'] ?? null);
    }

    #[Test]
    public function kolom_sensitif_lain_yang_diubah_ikut_dimascing(): void
    {
        $this->actingAs($this->superadmin());

        $user = User::create([
            'name' => 'Mask Email',
            'email' => 'maks-email@test.dev',
            'password' => 'rahasia',
        ]);
        $user->update(['email' => 'maks-email-2@test.dev']);

        $aktivitas = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'updated')
            ->latest()
            ->first();

        $this->assertNotNull($aktivitas);
        $baru = $aktivitas->changes()['attributes']['email'] ?? '';
        $lama = $aktivitas->changes()['old']['email'] ?? '';

        $this->assertStringStartsWith('*', $baru);
        $this->assertStringEndsWith('.dev', $baru);
        $this->assertStringNotContainsString('maks-email', $baru);
        $this->assertStringStartsWith('*', $lama);
        $this->assertStringEndsWith('.dev', $lama);
        $this->assertStringNotContainsString('maks-email', $lama);
    }

    #[Test]
    public function halaman_log_aktivitas_hanya_untuk_yang_punya_permission(): void
    {
        $this->actingAs($this->tanpaPermission())
            ->get(route('admin.activity-log.index'))
            ->assertForbidden();

        $this->actingAs($this->superadmin())
            ->get(route('admin.activity-log.index'))
            ->assertOk()
            ->assertSee('Log Aktivitas');
    }

    #[Test]
    public function halaman_detail_menampilkan_before_dan_after(): void
    {
        $this->actingAs($this->admin());

        $poli = Poli::create(['kode' => 'LA-D', 'nama' => 'Poli Sebelum']);
        $poli->update(['nama' => 'Poli Sesudah']);

        $aktivitas = Activity::where('subject_type', Poli::class)
            ->where('subject_id', $poli->id)
            ->where('event', 'updated')
            ->latest()
            ->firstOrFail();

        $this->actingAs($this->superadmin())
            ->get(route('admin.activity-log.show', $aktivitas))
            ->assertOk()
            ->assertSee('Poli Sebelum')
            ->assertSee('Poli Sesudah')
            ->assertSee('nama');
    }

    #[Test]
    public function pendaftaran_register_pnpp_juga_mencatat_aktivitas(): void
    {
        $this->actingAs($this->admin());

        $satker = $this->buatSatker();
        $reg = RegisterPnpp::create([
            'nama' => 'LOG PNPP',
            'jabatan' => 'Bintara',
            'satker_id' => $satker->id,
            'ttl' => 'Bogor, 01-01-1990',
            'alamat' => 'Jl. Uji',
            'no_hp' => '081234567890',
            'rencana_tanggal_kunjungan' => today()->addDays(3)->format('Y-m-d'),
            'rencana_jam_kunjungan' => '09:30',
            'status' => RegisterPnpp::STATUS_BELUM_DISETUJUI,
        ]);

        $aktivitas = Activity::where('subject_type', RegisterPnpp::class)
            ->where('subject_id', $reg->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($aktivitas);
        $this->assertSame('LOG PNPP', $aktivitas->changes()['attributes']['nama'] ?? null);
    }
}

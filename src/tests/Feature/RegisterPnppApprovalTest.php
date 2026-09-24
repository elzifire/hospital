<?php

namespace Tests\Feature;

use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\RegisterPnpp;
use App\Models\Reminder;
use App\Models\Satker;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Alur Admin\RegisterPnppController: tanda "Di PNPP", persetujuan per
 * poli (akun poli vs admin), sinkronisasi data diri ke tabel pnpps,
 * dan reminder Digital Reminder otomatis (buat saat approve, hapus saat
 * unapprove/destroy).
 */
class RegisterPnppApprovalTest extends TestCase
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

    /** Akun biasa (role "user") — tanpa permission manage register-pnpp. */
    protected function tanpaPermission(): User
    {
        return User::where('email', 'user@gmail.com')->firstOrFail();
    }

    /** Akun poli (role "poli") yang terikat ke satu poli lewat userDetail. */
    protected function petugasPoli(int $poliId): User
    {
        $user = User::create([
            'name' => 'Petugas Poli Uji',
            'email' => 'petugas-poli-uji-'.$poliId.'@test.dev',
            'password' => 'rahasia',
        ]);
        $user->assignRole('poli');
        $user->userDetail()->updateOrCreate([], ['poli_id' => $poliId]);

        return $user;
    }

    /**
     * Satker + dua poli uji (kode unik agar tidak bentrok dengan PoliSeeder).
     *
     * @return array{satker: Satker, poliA: Poli, poliB: Poli}
     */
    protected function kerangka(): array
    {
        $satker = Satker::firstOrCreate(['kode' => 'TEST-SD'], ['nama' => 'Satker Uji']);
        $poliA = Poli::firstOrCreate(['kode' => 'UMUM-SD'], ['nama' => 'Poli Umum SD']);
        $poliB = Poli::firstOrCreate(['kode' => 'GIGI-SD'], ['nama' => 'Poli Gigi SD']);

        return compact('satker', 'poliA', 'poliB');
    }

    protected function buatPendaftar(array $overrides = []): RegisterPnpp
    {
        $satker = Satker::firstOrCreate(['kode' => 'TEST-SD'], ['nama' => 'Satker Uji']);

        return RegisterPnpp::create(array_merge([
            'nama' => 'BUDI SANTOSO',
            'nik' => '1101010101010001',
            'nip' => '9001',
            'jabatan' => 'Bintara',
            'satker_id' => $satker->id,
            'unit' => 'Bagian Operasional',
            'ttl' => 'Bogor, 01-01-1990',
            'alamat' => 'Jl. Uji No. 1',
            'no_hp' => '081234567890',
            'rencana_tanggal_kunjungan' => today()->addDays(3)->format('Y-m-d'),
            'rencana_jam_kunjungan' => '09:30',
            'status' => RegisterPnpp::STATUS_BELUM_DISETUJUI,
        ], $overrides));
    }

    protected function buatPnpp(array $overrides = []): Pnpp
    {
        $satker = Satker::firstOrCreate(['kode' => 'TEST-SD'], ['nama' => 'Satker Uji']);

        return Pnpp::create(array_merge([
            'nama' => 'BUDI SANTOSO',
            'nip' => '9001',
            'nik' => '1101010101010001',
            'no_hp' => '080000000000',
            'satker_id' => $satker->id,
            'alamat' => 'Alamat Lama',
        ], $overrides));
    }

    #[Test]
    public function guest_dialihkan_ke_halaman_login(): void
    {
        $this->get(route('admin.register-pnpp.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function akun_tanpa_permission_tidak_bisa_mengakses_modul(): void
    {
        extract($this->kerangka());
        $reg = $this->buatPendaftar();
        $reg->polis()->attach($poliA->id);

        $this->actingAs($this->tanpaPermission());

        $this->get(route('admin.register-pnpp.index'))->assertForbidden();
        $this->get(route('admin.register-pnpp.show', $reg))->assertForbidden();
        $this->patch(route('admin.register-pnpp.approve', $reg))->assertForbidden();
        $this->patch(route('admin.register-pnpp.unapprove', $reg))->assertForbidden();
        $this->delete(route('admin.register-pnpp.destroy', $reg))->assertForbidden();

        // Data tidak tersentuh.
        $this->assertSame(RegisterPnpp::STATUS_BELUM_DISETUJUI, $reg->fresh()->status);
        $this->assertSame(0, $reg->approvedPolis()->count());
        $this->assertNull(Pnpp::where('nip', '9001')->first());
        $this->assertSame(0, Reminder::where('register_pnpp_id', $reg->id)->count());
    }

    #[Test]
    public function index_menampilkan_tanda_baru_dan_sudah_ada_di_pnpp(): void
    {
        extract($this->kerangka());

        $regBaru = $this->buatPendaftar(['nama' => 'ANDI BARU', 'nip' => '9100', 'nik' => '1101010101010900']);
        $regBaru->polis()->attach($poliA->id);

        $regLama = $this->buatPendaftar(['nama' => 'RINA LAMA', 'nip' => '9101', 'nik' => '1101010101010901']);
        $regLama->polis()->attach($poliB->id);
        $this->buatPnpp(['nama' => 'RINA LAMA', 'nip' => '9101', 'nik' => '1101010101010901']);

        $this->actingAs($this->superadmin())
            ->get(route('admin.register-pnpp.index'))
            ->assertOk()
            ->assertSee('ANDI BARU')
            ->assertSee('RINA LAMA')
            ->assertSee('Baru')
            ->assertSee('Sudah Ada')
            ->assertSee('0/1 poli disetujui');
    }

    #[Test]
    public function akun_poli_index_hanya_menampilkan_pendaftaran_polinya(): void
    {
        extract($this->kerangka());

        $regA = $this->buatPendaftar(['nama' => 'CANDRA A', 'nip' => '9200', 'nik' => '1101010101010910']);
        $regA->polis()->attach($poliA->id);

        $regDuo = $this->buatPendaftar(['nama' => 'DONI DUO', 'nip' => '9201', 'nik' => '1101010101010911']);
        $regDuo->polis()->attach([$poliA->id, $poliB->id]);

        $regB = $this->buatPendaftar(['nama' => 'EKA B', 'nip' => '9202', 'nik' => '1101010101010912']);
        $regB->polis()->attach($poliB->id);

        $this->actingAs($this->petugasPoli($poliA->id))
            ->get(route('admin.register-pnpp.index'))
            ->assertOk()
            ->assertSee('CANDRA A')
            ->assertSee('DONI DUO')
            ->assertDontSee('EKA B');
    }

    #[Test]
    public function index_filter_poli_untuk_admin(): void
    {
        extract($this->kerangka());

        $regA = $this->buatPendaftar(['nama' => 'CANDRA A', 'nip' => '9200', 'nik' => '1101010101010910']);
        $regA->polis()->attach($poliA->id);

        $regB = $this->buatPendaftar(['nama' => 'EKA B', 'nip' => '9202', 'nik' => '1101010101010912']);
        $regB->polis()->attach($poliB->id);

        $this->actingAs($this->admin())
            ->get(route('admin.register-pnpp.index', ['poli' => $poliA->id]))
            ->assertOk()
            ->assertSee('CANDRA A')
            ->assertDontSee('EKA B');
    }

    #[Test]
    public function index_filter_status_menampilkan_sesuai_status(): void
    {
        extract($this->kerangka());

        $regBaru = $this->buatPendaftar(['nama' => 'ANDI BARU', 'nip' => '9100', 'nik' => '1101010101010900']);
        $regBaru->polis()->attach($poliA->id);

        $regSetuju = $this->buatPendaftar(['nama' => 'RINA SETUJU', 'nip' => '9101', 'nik' => '1101010101010901']);
        $regSetuju->polis()->attach($poliB->id);

        $this->actingAs($this->admin())
            ->patch(route('admin.register-pnpp.approve', $regSetuju))
            ->assertSessionHas('success');

        $this->actingAs($this->admin())
            ->get(route('admin.register-pnpp.index', ['status' => RegisterPnpp::STATUS_DISETUJUI]))
            ->assertOk()
            ->assertSee('RINA SETUJU')
            ->assertDontSee('ANDI BARU');

        $this->actingAs($this->admin())
            ->get(route('admin.register-pnpp.index', ['status' => RegisterPnpp::STATUS_BELUM_DISETUJUI]))
            ->assertOk()
            ->assertSee('ANDI BARU')
            ->assertDontSee('RINA SETUJU');
    }

    #[Test]
    public function show_menampilkan_detail_pendaftaran(): void
    {
        extract($this->kerangka());
        $reg = $this->buatPendaftar();
        $reg->polis()->attach([$poliA->id, $poliB->id]);

        $this->actingAs($this->superadmin())
            ->get(route('admin.register-pnpp.show', $reg))
            ->assertOk()
            ->assertSee('BUDI SANTOSO')
            ->assertSee('Poli Umum SD')
            ->assertSee('Poli Gigi SD');
    }

    #[Test]
    public function approve_semua_poli_membuat_pnpp_baru_dan_reminder_per_poli(): void
    {
        extract($this->kerangka());
        $reg = $this->buatPendaftar();
        $reg->polis()->attach([$poliA->id, $poliB->id]);

        $this->actingAs($this->superadmin())
            ->patch(route('admin.register-pnpp.approve', $reg), [], [
                'Referer' => route('admin.register-pnpp.show', $reg),
            ])
            ->assertRedirect(route('admin.register-pnpp.show', $reg))
            ->assertSessionHas('success');

        $reg->refresh();

        // Status keseluruhan jadi "disetujui" karena semua poli sudah.
        $this->assertSame(RegisterPnpp::STATUS_DISETUJUI, $reg->status);
        $this->assertSame(2, $reg->approvedPolis()->count());

        $pivotA = $reg->polis()->find($poliA->id)->pivot;
        $this->assertNotNull($pivotA->approved_at);
        $this->assertSame($this->superadmin()->id, (int) $pivotA->approved_by);

        // Data diri disinkronkan ke tabel pnpps (belum ada kandidat → baru).
        $pnpp = Pnpp::where('nip', '9001')->first();
        $this->assertNotNull($pnpp);
        $this->assertSame('BUDI SANTOSO', $pnpp->nama);
        $this->assertSame('Bintara', $pnpp->jabatan);
        $this->assertSame('Bagian Operasional', $pnpp->bagian);
        $this->assertSame('081234567890', $pnpp->no_hp);
        $this->assertSame('Jl. Uji No. 1', $pnpp->alamat);
        $this->assertSame((int) $reg->satker_id, (int) $pnpp->satker_id);

        // Satu reminder otomatis per poli, mengikuti rencana kunjungan.
        $this->assertSame(2, Reminder::where('register_pnpp_id', $reg->id)->count());

        foreach ([$poliA, $poliB] as $poli) {
            $reminder = Reminder::where('register_pnpp_id', $reg->id)
                ->where('poli_id', $poli->id)
                ->firstOrFail();
            $this->assertSame($pnpp->id, $reminder->pnpp_id);
            $this->assertSame('terjadwal', $reminder->status);
            $this->assertSame(
                $reg->rencana_tanggal_kunjungan->format('Y-m-d'),
                $reminder->tanggal->format('Y-m-d'),
            );
            $this->assertSame('09:30', $reminder->jam->format('H:i'));
        }
    }

    #[Test]
    public function approve_satu_poli_via_poli_id_hanya_menandai_poli_tersebut(): void
    {
        extract($this->kerangka());
        $reg = $this->buatPendaftar();
        $reg->polis()->attach([$poliA->id, $poliB->id]);

        $this->actingAs($this->admin())
            ->patch(route('admin.register-pnpp.approve', ['registerPnpp' => $reg->id, 'poli_id' => $poliA->id]))
            ->assertSessionHas('success');

        $reg->refresh();

        // Status keseluruhan masih "belum disetujui" — poliB belum ikut.
        $this->assertSame(RegisterPnpp::STATUS_BELUM_DISETUJUI, $reg->status);
        $this->assertSame(1, $reg->approvedPolis()->count());
        $this->assertTrue($reg->approvedPolis()->whereKey($poliA->id)->exists());
        $this->assertFalse($reg->approvedPolis()->whereKey($poliB->id)->exists());

        // Reminder hanya untuk poli yang disetujui.
        $this->assertSame(1, Reminder::where('register_pnpp_id', $reg->id)->count());
        $this->assertSame(
            $poliA->id,
            (int) Reminder::where('register_pnpp_id', $reg->id)->value('poli_id'),
        );
    }

    #[Test]
    public function approve_ditolak_untuk_poli_yang_bukan_tujuan(): void
    {
        extract($this->kerangka());
        $reg = $this->buatPendaftar();
        $reg->polis()->attach($poliA->id);

        $this->actingAs($this->admin())
            ->patch(route('admin.register-pnpp.approve', ['registerPnpp' => $reg->id, 'poli_id' => $poliB->id]))
            ->assertForbidden();

        $this->assertSame(RegisterPnpp::STATUS_BELUM_DISETUJUI, $reg->fresh()->status);
        $this->assertSame(0, $reg->approvedPolis()->count());
        $this->assertSame(0, Reminder::where('register_pnpp_id', $reg->id)->count());
        $this->assertNull(Pnpp::where('nip', '9001')->first());
    }

    #[Test]
    public function akun_poli_menyetujui_hanya_polinya_sendiri(): void
    {
        extract($this->kerangka());
        $reg = $this->buatPendaftar();
        $reg->polis()->attach([$poliA->id, $poliB->id]);

        $this->actingAs($this->petugasPoli($poliA->id))
            ->patch(route('admin.register-pnpp.approve', $reg))
            ->assertSessionHas('success');

        $reg->refresh();

        // Hanya poli A yang disetujui → status keseluruhan tetap belum disetujui.
        $this->assertSame(RegisterPnpp::STATUS_BELUM_DISETUJUI, $reg->status);
        $this->assertSame(1, $reg->approvedPolis()->count());
        $this->assertTrue($reg->approvedPolis()->whereKey($poliA->id)->exists());

        $this->assertSame(1, Reminder::where('register_pnpp_id', $reg->id)->count());
        $this->assertSame(
            $poliA->id,
            (int) Reminder::where('register_pnpp_id', $reg->id)->value('poli_id'),
        );
    }

    #[Test]
    public function akun_poli_tidak_bisa_mengakses_pendaftaran_polinya_lain(): void
    {
        extract($this->kerangka());
        $reg = $this->buatPendaftar();
        $reg->polis()->attach($poliB->id);

        $petugas = $this->petugasPoli($poliA->id);

        $this->actingAs($petugas)
            ->get(route('admin.register-pnpp.show', $reg))
            ->assertForbidden();

        $this->actingAs($petugas)
            ->patch(route('admin.register-pnpp.approve', $reg))
            ->assertForbidden();

        $this->actingAs($petugas)
            ->patch(route('admin.register-pnpp.unapprove', $reg))
            ->assertForbidden();

        $this->assertSame(RegisterPnpp::STATUS_BELUM_DISETUJUI, $reg->fresh()->status);
        $this->assertSame(0, $reg->approvedPolis()->count());
        $this->assertSame(0, Reminder::where('register_pnpp_id', $reg->id)->count());
    }

    #[Test]
    public function approve_memperbarui_pnpp_yang_sudah_ada_bukan_membuat_baru(): void
    {
        extract($this->kerangka());

        $pnpp = $this->buatPnpp([
            'nama' => 'BUDI LAMA',
            'nip' => '9001',
            'nik' => '1101010101010001',
            'no_hp' => '080000000000',
            'alamat' => 'Alamat Lama',
            'jabatan' => 'Lama',
        ]);

        $reg = $this->buatPendaftar();
        $reg->polis()->attach($poliA->id);

        $this->actingAs($this->admin())
            ->patch(route('admin.register-pnpp.approve', $reg))
            ->assertSessionHas('success');

        // Kocok dengan NIP → tetap satu baris (tidak dibuat baru).
        $this->assertSame(1, Pnpp::where('nip', '9001')->count());

        $pnpp->refresh();
        $this->assertSame('BUDI SANTOSO', $pnpp->nama);
        $this->assertSame('081234567890', $pnpp->no_hp);
        $this->assertSame('Jl. Uji No. 1', $pnpp->alamat);
        $this->assertSame('Bagian Operasional', $pnpp->bagian);
        $this->assertSame('Bintara', $pnpp->jabatan);

        // Reminder menunjuk pnpp lama (bukan duplikat).
        $reminder = Reminder::where('register_pnpp_id', $reg->id)->firstOrFail();
        $this->assertSame($pnpp->id, $reminder->pnpp_id);
    }

    #[Test]
    public function approve_kedua_kali_idempoten_tidak_menduplikasi_reminder(): void
    {
        extract($this->kerangka());
        $reg = $this->buatPendaftar();
        $reg->polis()->attach([$poliA->id, $poliB->id]);

        foreach ([$this->superadmin(), $this->superadmin()] as $pemilik) {
            $this->actingAs($pemilik)
                ->patch(route('admin.register-pnpp.approve', $reg))
                ->assertSessionHas('success');
        }

        $this->assertSame(2, Reminder::where('register_pnpp_id', $reg->id)->count());
        $this->assertSame(2, $reg->approvedPolis()->count());
        $this->assertSame(1, Pnpp::where('nip', '9001')->count());
    }

    #[Test]
    public function unapprove_semua_poli_menghapus_reminder_dan_mengembalikan_status(): void
    {
        extract($this->kerangka());
        $reg = $this->buatPendaftar();
        $reg->polis()->attach([$poliA->id, $poliB->id]);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('admin.register-pnpp.approve', $reg))
            ->assertSessionHas('success');
        $this->assertSame(2, Reminder::where('register_pnpp_id', $reg->id)->count());

        $this->actingAs($admin)
            ->patch(route('admin.register-pnpp.unapprove', $reg), [], [
                'Referer' => route('admin.register-pnpp.show', $reg),
            ])
            ->assertRedirect(route('admin.register-pnpp.show', $reg))
            ->assertSessionHas('success');

        $reg->refresh();
        $this->assertSame(RegisterPnpp::STATUS_BELUM_DISETUJUI, $reg->status);
        $this->assertSame(0, $reg->approvedPolis()->count());

        // Tanda persetujuan pivot dibersihkan.
        foreach ([$poliA, $poliB] as $poli) {
            $pivot = $reg->polis()->find($poli->id)->pivot;
            $this->assertNull($pivot->approved_at);
            $this->assertNull($pivot->approved_by);
        }

        // Reminder otomatis ikut dihapus (soft delete) — baris tetap ada.
        $this->assertSame(
            2,
            Reminder::withTrashed()
                ->where('register_pnpp_id', $reg->id)
                ->whereNotNull('deleted_at')
                ->count(),
        );
    }

    #[Test]
    public function unapprove_satu_poli_hanya_menghapus_reminder_poli_itu_saja(): void
    {
        extract($this->kerangka());
        $reg = $this->buatPendaftar();
        $reg->polis()->attach([$poliA->id, $poliB->id]);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('admin.register-pnpp.approve', $reg));

        $this->actingAs($admin)
            ->patch(route('admin.register-pnpp.unapprove', [
                'registerPnpp' => $reg->id,
                'poli_id' => $poliA->id,
            ]))
            ->assertSessionHas('success');

        $reg->refresh();

        // PoliA batal, PoliB masih disetujui → status keseluruhan belum disetujui.
        $this->assertSame(RegisterPnpp::STATUS_BELUM_DISETUJUI, $reg->status);
        $this->assertSame(1, $reg->approvedPolis()->count());
        $this->assertTrue($reg->approvedPolis()->whereKey($poliB->id)->exists());

        // Reminder poliA soft-deleted, poliB tetap aktif.
        $this->assertSame(
            1,
            Reminder::withTrashed()
                ->where('register_pnpp_id', $reg->id)
                ->where('poli_id', $poliA->id)
                ->whereNotNull('deleted_at')
                ->count(),
        );
        $this->assertSame(
            1,
            Reminder::where('register_pnpp_id', $reg->id)
                ->where('poli_id', $poliB->id)
                ->count(),
        );
    }

    #[Test]
    public function setujui_kembali_pola_persetujuan_yang_pernah_dibatalkan(): void
    {
        extract($this->kerangka());
        $reg = $this->buatPendaftar();
        $reg->polis()->attach($poliA->id);

        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.register-pnpp.approve', $reg));
        $this->actingAs($admin)->patch(route('admin.register-pnpp.unapprove', $reg));

        // Reminder otomatis lama sudah soft-deleted — approve lagi tidak
        // menduplikasikan: row baru dibuat karena row lama tidak dianggap.
        $this->actingAs($admin)->patch(route('admin.register-pnpp.approve', $reg));

        $reg->refresh();
        $this->assertSame(RegisterPnpp::STATUS_DISETUJUI, $reg->status);
        $this->assertSame(1, $reg->approvedPolis()->count());
        $this->assertSame(
            1,
            Reminder::where('register_pnpp_id', $reg->id)
                ->where('poli_id', $poliA->id)
                ->count(),
        );
        $this->assertSame(
            1,
            Reminder::withTrashed()
                ->where('register_pnpp_id', $reg->id)
                ->where('poli_id', $poliA->id)
                ->whereNotNull('deleted_at')
                ->count(),
        );
    }

    #[Test]
    public function destroy_menghapus_pendaftaran_beserta_reminder_otomatisnya(): void
    {
        extract($this->kerangka());
        $reg = $this->buatPendaftar();
        $reg->polis()->attach([$poliA->id, $poliB->id]);

        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.register-pnpp.approve', $reg));
        $this->assertSame(2, Reminder::where('register_pnpp_id', $reg->id)->count());
        $pnppId = Reminder::where('register_pnpp_id', $reg->id)->value('pnpp_id');

        $this->actingAs($admin)
            ->delete(route('admin.register-pnpp.destroy', $reg))
            ->assertRedirect(route('admin.register-pnpp.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('register_pnpp', ['id' => $reg->id]);

        // Reminder turunannya ikut soft-deleted (bukan hilang permanen).
        // Catatan: FK register_pnpp_id nullOnDelete → setelah pendaftaran
        // dihapus kolomnya dinolkan, jadi identifikasi lewat pnpp_id.
        $this->assertSame(
            2,
            Reminder::withTrashed()
                ->where('pnpp_id', $pnppId)
                ->whereNotNull('deleted_at')
                ->count(),
        );
        $this->assertSame(0, Reminder::where('pnpp_id', $pnppId)->count());
    }
}

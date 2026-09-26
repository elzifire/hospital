<?php

namespace Tests\Feature;

use App\Models\Poli;
use App\Models\RegisterPnpp;
use App\Models\Satker;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Edit jadwal kunjungan & poli tujuan pada pendaftaran PNPP oleh admin:
 *  - hanya admin/superadmin (akun poli dilarang).
 *  - hanya selama belum ada poli yang disetujui (setelah approve, terkunci).
 *  - validasi ulang jam/hari/libur terhadap poli tujuan baru.
 */
class RegisterPnppEditTest extends TestCase
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
            'email' => 'petugas-edit-'.$poliId.'@test.dev',
            'password' => 'rahasia',
        ]);
        $user->assignRole('poli');
        $user->userDetail()->updateOrCreate([], ['poli_id' => $poliId]);

        return $user;
    }

    protected function buatPendaftar(array $overrides = []): RegisterPnpp
    {
        $satker = Satker::firstOrCreate(['kode' => 'TEST-EDIT'], ['nama' => 'Satker Uji']);

        return RegisterPnpp::create(array_merge([
            'nama' => 'BUDI SANTOSO',
            'nik' => '1101010101010001',
            'nip' => '9301',
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

    protected function payloadJadwal(array $overrides = []): array
    {
        return array_merge([
            'poli_dituju' => [],
            'rencana_tanggal_kunjungan' => today()->addDays(5)->format('Y-m-d'),
            'rencana_jam_kunjungan' => '10:15',
        ], $overrides);
    }

    #[Test]
    public function guest_dialihkan_ke_halaman_login(): void
    {
        $reg = $this->buatPendaftar();

        $this->get(route('admin.register-pnpp.edit', $reg))->assertRedirect(route('login'));
        $this->patch(route('admin.register-pnpp.update', $reg))->assertRedirect(route('login'));
    }

    #[Test]
    public function akun_tanpa_permission_tidak_bisa_mengakses_edit(): void
    {
        $reg = $this->buatPendaftar();

        $this->actingAs($this->tanpaPermission());

        $this->get(route('admin.register-pnpp.edit', $reg))->assertForbidden();
        $this->patch(route('admin.register-pnpp.update', $reg))->assertForbidden();
    }

    #[Test]
    public function akun_poli_tidak_bisa_edit_bahkan_untuk_pendaftaran_polinya(): void
    {
        $poli = Poli::create(['kode' => 'EDIT-A', 'nama' => 'Poli Edit A']);
        $reg = $this->buatPendaftar();
        $reg->polis()->attach($poli->id);

        $this->actingAs($this->petugasPoli($poli->id));

        $this->get(route('admin.register-pnpp.edit', $reg))->assertForbidden();
        $this->patch(route('admin.register-pnpp.update', $reg), $this->payloadJadwal(['poli_dituju' => [$poli->id]]))
            ->assertForbidden();
    }

    #[Test]
    public function admin_atau_superadmin_bisa_membuka_halaman_edit(): void
    {
        extract($this->kerangkaPolis());
        $reg = $this->buatPendaftar();
        $reg->polis()->attach([$poliA->id, $poliB->id]);

        foreach ([$this->superadmin(), $this->admin()] as $pemilik) {
            $this->actingAs($pemilik)
                ->get(route('admin.register-pnpp.edit', $reg))
                ->assertOk()
                ->assertSee('Edit Jadwal Registrasi PNPP')
                ->assertSee('Poli Umum Edit')
                ->assertSee('Poli Gigi Edit')
                ->assertSee($reg->rencana_tanggal_kunjungan->format('Y-m-d'))
                ->assertSee('09:30');
        }
    }

    #[Test]
    public function edit_menyimpan_perubahan_tanggal_dan_jam(): void
    {
        [$poliA, $poliB] = $this->kerangkaPolisPasangan();
        $reg = $this->buatPendaftar();
        $reg->polis()->attach([$poliA->id, $poliB->id]);

        $baru = $this->payloadJadwal(['poli_dituju' => [$poliA->id, $poliB->id]]);

        $this->actingAs($this->admin())
            ->from(route('admin.register-pnpp.show', $reg))
            ->patch(route('admin.register-pnpp.update', $reg), $baru)
            ->assertRedirect(route('admin.register-pnpp.show', $reg))
            ->assertSessionHas('success');

        $reg->refresh();
        $this->assertSame($baru['rencana_tanggal_kunjungan'], $reg->rencana_tanggal_kunjungan->format('Y-m-d'));
        $this->assertSame($baru['rencana_jam_kunjungan'], $reg->rencana_jam_kunjungan);
        $this->assertSame(RegisterPnpp::STATUS_BELUM_DISETUJUI, $reg->status);
    }

    #[Test]
    public function edit_bisa_mengubah_poli_tujuan(): void
    {
        [$poliA, $poliB, $poliC] = $this->kerangkaPolisLengkap();
        $reg = $this->buatPendaftar();
        $reg->polis()->attach([$poliA->id, $poliB->id]);

        $this->actingAs($this->superadmin())
            ->patch(route('admin.register-pnpp.update', $reg), $this->payloadJadwal([
                'poli_dituju' => [$poliB->id, $poliC->id],
            ]))
            ->assertSessionHas('success');

        $this->assertSame(
            [$poliB->id, $poliC->id],
            $reg->fresh()->polis()->pluck('polis.id')->sort()->values()->all(),
        );
    }

    #[Test]
    public function edit_ditolak_setelah_ada_poli_yang_disetujui(): void
    {
        [$poliA, $poliB] = $this->kerangkaPolisPasangan();
        $reg = $this->buatPendaftar();
        $reg->polis()->attach([$poliA->id, $poliB->id]);

        // Setujui salah satu poli — pendaftaran masih "belum disetujui" tapi
        // sudah ada poli yang disetujui → jadwal terkunci.
        $this->actingAs($this->admin())
            ->patch(route('admin.register-pnpp.approve', ['registerPnpp' => $reg->id, 'poli_id' => $poliA->id]))
            ->assertSessionHas('success');

        $this->actingAs($this->admin());

        $this->get(route('admin.register-pnpp.edit', $reg))->assertForbidden();

        $tanggalLama = $reg->fresh()->rencana_tanggal_kunjungan->format('Y-m-d');
        $this->patch(route('admin.register-pnpp.update', $reg), $this->payloadJadwal([
            'poli_dituju' => [$poliA->id],
        ]))->assertForbidden();

        $reg->refresh();
        $this->assertSame($tanggalLama, $reg->rencana_tanggal_kunjungan->format('Y-m-d'));
        $this->assertSame('09:30', $reg->rencana_jam_kunjungan);
        $this->assertSame(
            [$poliA->id, $poliB->id],
            $reg->fresh()->polis()->pluck('polis.id')->sort()->values()->all(),
        );
    }

    #[Test]
    public function edit_menolak_jam_di_luar_jam_layanan_poli_baru(): void
    {
        [$poliA, $poliB] = $this->kerangkaPolisPasangan();
        $poliB->update(['jam_buka' => '08:00', 'jam_tutup' => '12:00']);
        $reg = $this->buatPendaftar();
        $reg->polis()->attach($poliA->id);

        // 14:00 di luar jam layanan poliB yang baru dipilih → ditolak.
        $this->actingAs($this->admin())
            ->patch(route('admin.register-pnpp.update', $reg), $this->payloadJadwal([
                'poli_dituju' => [$poliB->id],
                'rencana_jam_kunjungan' => '14:00',
            ]))
            ->assertSessionHasErrors('rencana_jam_kunjungan');

        $reg->refresh();
        $this->assertSame('09:30', $reg->rencana_jam_kunjungan);
        $this->assertSame([$poliA->id], $reg->fresh()->polis()->pluck('polis.id')->all());
    }

    #[Test]
    public function edit_mewajibkan_minimal_satu_poli(): void
    {
        [$poliA] = $this->kerangkaPolisPasangan();
        $reg = $this->buatPendaftar();
        $reg->polis()->attach($poliA->id);

        $this->actingAs($this->admin())
            ->patch(route('admin.register-pnpp.update', $reg), $this->payloadJadwal([
                'poli_dituju' => [],
            ]))
            ->assertSessionHasErrors('poli_dituju');
    }

    #[Test]
    public function show_menampilkan_tombol_edit_hanya_untuk_admin_dan_sebelum_disetujui(): void
    {
        [$poliA, $poliB] = $this->kerangkaPolisPasangan();
        $reg = $this->buatPendaftar();
        $reg->polis()->attach([$poliA->id, $poliB->id]);

        // Admin → tombol edit tampil.
        $this->actingAs($this->admin())
            ->get(route('admin.register-pnpp.show', $reg))
            ->assertOk()
            ->assertSee('Edit Jadwal & Poli', false);

        // Akun poli → tidak tampil.
        $this->actingAs($this->petugasPoli($poliA->id))
            ->get(route('admin.register-pnpp.show', $reg))
            ->assertOk()
            ->assertDontSee('Edit Jadwal & Poli', false);

        // Setelah ada poli disetujui → tombol edit hilang.
        $this->actingAs($this->admin())
            ->patch(route('admin.register-pnpp.approve', ['registerPnpp' => $reg->id, 'poli_id' => $poliA->id]))
            ->assertSessionHas('success');

        $this->actingAs($this->admin())
            ->get(route('admin.register-pnpp.show', $reg))
            ->assertOk()
            ->assertDontSee('Edit Jadwal & Poli', false);
    }

    /** @return array{poliA: Poli, poliB: Poli} */
    protected function kerangkaPolis(): array
    {
        $poliA = Poli::firstOrCreate(['kode' => 'EDIT-A'], ['nama' => 'Poli Umum Edit']);
        $poliB = Poli::firstOrCreate(['kode' => 'EDIT-B'], ['nama' => 'Poli Gigi Edit']);

        return compact('poliA', 'poliB');
    }

    /** @return array{0: Poli, 1: Poli} */
    protected function kerangkaPolisPasangan(): array
    {
        $p = $this->kerangkaPolis();

        return [$p['poliA'], $p['poliB']];
    }

    /** @return array{0: Poli, 1: Poli, 2: Poli} */
    protected function kerangkaPolisLengkap(): array
    {
        [$poliA, $poliB] = $this->kerangkaPolisPasangan();
        $poliC = Poli::firstOrCreate(['kode' => 'EDIT-C'], ['nama' => 'Poli Anak Edit']);

        return [$poliA, $poliB, $poliC];
    }
}

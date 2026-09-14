<?php

namespace Tests\Feature;

use App\Jobs\ImportMasterJob;
use App\Models\ResponManual;
use App\Models\User;
use App\Support\CsvHelper;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResponImportManualTest extends TestCase
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

    #[Test]
    public function input_manual_menyimpan_data_respon(): void
    {
        $this->actingAs($this->superadmin())
            ->post(route('admin.respon.manual-store'), [
                'nama' => 'Budi Santoso',
                'nrp_nip' => '198501012010011001',
                'no_hp' => '081234567890',
                'satker' => 'Dinas Kesehatan',
                'isi' => 'Baik, saya hadir kontrol.',
                'waktu' => '2026-09-14 10:00',
            ])
            ->assertRedirect(route('admin.respon.index', ['tab' => 'data']));

        $this->assertDatabaseHas('respon_manuals', [
            'nama' => 'Budi Santoso',
            'nrp_nip' => '198501012010011001',
            'no_hp' => '6281234567890',
            'satker' => 'Dinas Kesehatan',
            'isi' => 'Baik, saya hadir kontrol.',
            'waktu' => '2026-09-14 10:00:00',
            'sumber' => 'manual',
        ]);
    }

    #[Test]
    public function input_manual_memakai_waktu_sekarang_saat_kosong(): void
    {
        $sekarang = now();

        $this->actingAs($this->superadmin())
            ->post(route('admin.respon.manual-store'), [
                'no_hp' => '6289999999999',
                'isi' => 'Balasan tanpa waktu',
            ])
            ->assertRedirect(route('admin.respon.index', ['tab' => 'data']));

        $this->assertDatabaseCount('respon_manuals', 1);
        $this->assertTrue(
            ResponManual::first()->waktu->between($sekarang->copy()->subMinute(), now())
        );
    }

    #[Test]
    public function input_manual_nomor_tidak_valid_ditolak(): void
    {
        $this->actingAs($this->superadmin())
            ->post(route('admin.respon.manual-store'), [
                'no_hp' => 'abc',
                'isi' => 'Tes',
            ])
            ->assertSessionHasErrors(['no_hp']);

        $this->assertDatabaseCount('respon_manuals', 0);
    }

    #[Test]
    public function import_csv_diproses_per_batch_dan_menyimpannya(): void
    {
        $csv = CsvHelper::build([
            ['Budi Santoso', '198501012010011001', '081234567890', 'Dinas Kesehatan', 'Baik, saya hadir kontrol.'],
            ['Siti Aminah', '197903152001122001', '6289999999999', 'RSUD', 'Jadwal saya minta diubah.'],
        ], ['Nama', 'NRP/NIP', 'No. HP', 'Satker', 'Isi']);

        $path = tempnam(sys_get_temp_dir(), 'respon');
        file_put_contents($path, $csv);
        $file = new UploadedFile($path, 'respon.csv', 'text/csv', null, true);

        $this->actingAs($this->superadmin())
            ->post(route('admin.respon.import-upload'), ['file' => $file])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.respon.index', ['tab' => 'import']));

        // Queue sync → preview selesai langsung.
        $token = session('respon_import_token');
        $this->assertSame('preview_ready', Cache::get(ImportMasterJob::cacheKey($token))['status']);
        $this->assertSame(2, Cache::get(ImportMasterJob::cacheKey($token))['total']);

        $this->actingAs($this->superadmin())
            ->post(route('admin.respon.import-confirm'), ['token' => $token])
            ->assertRedirect(route('admin.respon.index', ['tab' => 'import']));

        $this->assertDatabaseHas('respon_manuals', [
            'nama' => 'Budi Santoso',
            'nrp_nip' => '198501012010011001',
            'no_hp' => '6281234567890',
            'satker' => 'Dinas Kesehatan',
            'isi' => 'Baik, saya hadir kontrol.',
            'sumber' => 'import',
        ]);
        $this->assertDatabaseHas('respon_manuals', [
            'no_hp' => '6289999999999',
            'isi' => 'Jadwal saya minta diubah.',
            'sumber' => 'import',
        ]);
        $this->assertDatabaseCount('respon_manuals', 2);
    }

    #[Test]
    public function import_duplikat_tidak_menambah_baris_baru(): void
    {
        $csv = CsvHelper::build([
            ['Budi Santoso', '198501012010011001', '081234567890', 'Dinas Kesehatan', 'Siap dok.'],
        ], ['Nama', 'NRP/NIP', 'No. HP', 'Satker', 'Isi']);

        $path = tempnam(sys_get_temp_dir(), 'respon');
        file_put_contents($path, $csv);
        $file = new UploadedFile($path, 'respon.csv', 'text/csv', null, true);

        $this->actingAs($this->superadmin())
            ->post(route('admin.respon.import-upload'), ['file' => $file]);
        $token = session('respon_import_token');
        $this->actingAs($this->superadmin())
            ->post(route('admin.respon.import-confirm'), ['token' => $token]);

        $this->assertDatabaseCount('respon_manuals', 1);

        // Import lagi: baris sama → diperbarui, bukan digandakan.
        $this->actingAs($this->superadmin())
            ->post(route('admin.respon.import-upload'), ['file' => $file]);
        $token = session('respon_import_token');

        $this->assertSame('preview_ready', Cache::get(ImportMasterJob::cacheKey($token))['status']);

        $this->actingAs($this->superadmin())
            ->post(route('admin.respon.import-confirm'), ['token' => $token]);

        $this->assertDatabaseCount('respon_manuals', 1);
    }

    #[Test]
    public function data_respon_bisa_dihapus_dari_index(): void
    {
        $d = ResponManual::create([
            'nama' => 'Budi Santoso',
            'nrp_nip' => '198501012010011001',
            'no_hp' => '6281234567890',
            'satker' => 'Dinas Kesehatan',
            'isi' => 'Akan dihapus',
            'waktu' => now(),
            'sumber' => 'manual',
        ]);

        $this->actingAs($this->superadmin())
            ->delete(route('admin.respon.manual-destroy', $d))
            ->assertRedirect(route('admin.respon.index', ['tab' => 'data']));

        $this->assertDatabaseCount('respon_manuals', 0);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\Satker;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReproduksiAdminCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function buka_form_create_sebagai_admin(): void
    {
        $satker = Satker::create(['kode' => 'KDT', 'nama' => 'Satker Uji']);
        $poliA = Poli::create(['kode' => 'KDT-A', 'nama' => 'Poli A']);
        Pnpp::create(['nama' => 'Budi Santoso', 'nip' => '9001', 'satker_id' => $satker->id]);

        $this->actingAs(User::where('email', 'superadmin@gmail.com')->firstOrFail());

        $this->get(route('admin.kunjungan.create'))
            ->assertOk()
            ->assertSee('Tambah Kunjungan')
            ->assertSee('Budi Santoso');
    }
}
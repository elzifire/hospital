<?php

namespace Tests\Feature;

use App\Models\BroadcastRule;
use App\Models\MessageTemplate;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingAturanTest extends TestCase
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
    public function superadmin_membuka_tab_aturan_pesan(): void
    {
        $this->actingAs($this->superadmin())
            ->get(route('admin.setting.index', ['tab' => 'aturan']))
            ->assertOk()
            ->assertSee('Aturan Generate Pesan')
            ->assertSee('H-7')
            ->assertDontSee('Tidak Datang')
            ->assertSee('Tanpa template (rule dilewati)');
    }

    #[Test]
    public function memasangkan_template_dan_menonaktifkan_aturan(): void
    {
        $template = MessageTemplate::create([
            'judul' => 'Undangan Kontrol',
            'channel' => 'WhatsApp',
            'konten' => 'Halo {nama}, kontrol Anda {tanggal}.',
            'is_active' => true,
        ]);

        $aturan = BroadcastRule::where(['jenis' => 'outreach', 'rule' => 'h-7'])->first();

        $this->actingAs($this->superadmin())
            ->put(route('admin.setting.aturan.update', $aturan), [
                'message_template_id' => $template->id,
                'is_active' => '1',
            ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('broadcast_rules', [
            'id' => $aturan->id,
            'message_template_id' => $template->id,
            'is_active' => true,
        ]);

        // Nonaktifkan kembali
        $this->put(route('admin.setting.aturan.update', $aturan), [
            'message_template_id' => $template->id,
            'is_active' => '0',
        ])->assertRedirect();

        $this->assertSame(false, $aturan->refresh()->is_active);
    }

    #[Test]
    public function aturan_menolak_template_nonaktif(): void
    {
        $template = MessageTemplate::create([
            'judul' => 'Template Mati',
            'channel' => 'WhatsApp',
            'konten' => 'Halo {nama}.',
            'is_active' => false,
        ]);

        $aturan = BroadcastRule::first();

        $this->actingAs($this->superadmin())
            ->put(route('admin.setting.aturan.update', $aturan), [
                'message_template_id' => $template->id,
                'is_active' => '1',
            ])->assertSessionHasErrors('message_template_id');
    }

    #[Test]
    public function user_biasa_ditolak_dari_setting(): void
    {
        $user = User::where('email', 'user@gmail.com')->firstOrFail();
        $aturan = BroadcastRule::first();

        $this->actingAs($user)->get(route('admin.setting.index'))->assertForbidden();
        $this->actingAs($user)->put(route('admin.setting.aturan.update', $aturan), [])->assertForbidden();
    }
}

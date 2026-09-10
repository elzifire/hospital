<?php

namespace Tests\Feature;

use App\Models\MessageLog;
use App\Models\MessageReply;
use App\Models\Pnpp;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhookResponFollowUpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        // Webhook default tanpa token; tiap test menyetel sendiri.
        config(['whatsapp.webhook.verify_token' => null, 'whatsapp.webhook.secret' => null]);
    }

    protected function superadmin(): User
    {
        return User::where('email', 'superadmin@gmail.com')->firstOrFail();
    }

    /**
     * Payload WAHA event 'message' standar.
     */
    protected function eventPesan(array $payload = [], array $extra = []): array
    {
        return array_merge([
            'event' => 'message',
            'session' => 'rsb-wa-1',
            'payload' => array_merge([
                'id' => 'msg-1',
                'from' => '6281234567890@c.us',
                'to' => '6289887766554@c.us',
                'timestamp' => now()->subMinutes(5)->timestamp,
                'fromMe' => false,
                'body' => 'Baik dok, saya hadir besok.',
                'type' => 'chat',
            ], $payload),
        ], $extra);
    }

    #[Test]
    public function webhook_menyimpan_balasan_dan_mengaitkan_pasien_terdaftar(): void
    {
        $budi = Pnpp::create(['nama' => 'Budi Santoso', 'no_hp' => '081234567890']);

        $waktu = now()->subMinutes(5)->timestamp;

        $this->postJson(route('webhook.whatsapp'), $this->eventPesan(['timestamp' => $waktu]))
            ->assertOk()
            ->assertJson(['ok' => true, 'disimpan' => true]);

        $this->assertDatabaseHas('message_replies', [
            'no_hp' => '6281234567890',
            'nama' => 'Budi Santoso',
            'isi_pesan' => 'Baik dok, saya hadir besok.',
            'driver' => 'waha',
        ]);

        $balasan = MessageReply::first();

        $this->assertSame($budi->id, $balasan->pnpp_id);
        $this->assertSame($waktu, $balasan->waktu_masuk->timestamp);
    }

    #[Test]
    public function webhook_menolak_permintaan_tanpa_token_yang_benar(): void
    {
        config(['whatsapp.webhook.verify_token' => 'rahasia-123']);

        $this->postJson(route('webhook.whatsapp'), $this->eventPesan())->assertUnauthorized();
        $this->postJson(route('webhook.whatsapp'), $this->eventPesan(), ['X-Webhook-Token' => 'salah'])->assertUnauthorized();

        // Token benar via header → diterima
        $this->postJson(route('webhook.whatsapp'), $this->eventPesan(), ['X-Webhook-Token' => 'rahasia-123'])
            ->assertOk();

        // Token benar via query ?token= → diterima (uji manual)
        $this->postJson(route('webhook.whatsapp', ['token' => 'rahasia-123']), $this->eventPesan())
            ->assertOk();
    }

    #[Test]
    public function webhook_memverifikasi_hmac_sha512(): void
    {
        config(['whatsapp.webhook.secret' => 'kunci-rahasia']);

        $body = json_encode($this->eventPesan());
        $hmac = hash_hmac('sha512', $body, 'kunci-rahasia');

        $this->postJson(route('webhook.whatsapp'), $this->eventPesan(), ['X-Webhook-Hmac' => 'palsu'])
            ->assertUnauthorized();

        $this->postJson(route('webhook.whatsapp'), $this->eventPesan(), ['X-Webhook-Hmac' => $hmac])
            ->assertOk();
    }

    #[Test]
    public function webhook_mengabaikan_pesan_keluar_grup_dan_event_lain(): void
    {
        // Pesan yang kita kirim sendiri (fromMe)
        $this->postJson(route('webhook.whatsapp'), $this->eventPesan(['fromMe' => true]))
            ->assertOk()->assertJson(['disimpan' => false]);

        // Pesan dari group (@g.us)
        $this->postJson(route('webhook.whatsapp'), $this->eventPesan(['from' => '1203630261@g.us']))
            ->assertOk()->assertJson(['disimpan' => false]);

        // Event lain (session.status) cukup di-ack
        $this->postJson(route('webhook.whatsapp'), ['event' => 'session.status', 'payload' => []])
            ->assertOk()->assertJson(['disimpan' => false]);

        $this->assertSame(0, MessageReply::count());
    }

    #[Test]
    public function webhook_mengabaikan_event_duplikat(): void
    {
        $event = $this->eventPesan();

        $this->postJson(route('webhook.whatsapp'), $event)->assertOk();
        $this->postJson(route('webhook.whatsapp'), $event)->assertOk()->assertJson(['disimpan' => false]);

        $this->assertSame(1, MessageReply::count());
    }

    #[Test]
    public function halaman_respon_menampilkan_balasan_dan_statistik(): void
    {
        MessageReply::create([
            'no_hp' => '6281234567890', 'nama' => 'Budi Santoso',
            'isi_pesan' => 'Baik dok, saya hadir.', 'waktu_masuk' => now(), 'driver' => 'waha',
        ]);
        MessageReply::create([
            'no_hp' => '6289999999999', 'nama' => null,
            'isi_pesan' => 'Siapa ini?', 'waktu_masuk' => now(), 'driver' => 'waha',
        ]);

        $this->actingAs($this->superadmin())
            ->get(route('admin.respon.index'))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Baik dok, saya hadir.')
            ->assertSee('Nomor Tak Dikenal');
    }

    #[Test]
    public function halaman_percakapan_menampilkan_pesan_keluar_dan_balasan(): void
    {
        $budi = Pnpp::create(['nama' => 'Budi Santoso', 'no_hp' => '081234567890']);

        MessageLog::create([
            'jenis' => 'follow_up', 'rule' => 'h-1', 'pnpp_id' => $budi->id, 'penerima_nama' => 'Budi Santoso',
            'penerima_no_hp' => '6281234567890', 'konten' => 'Jangan lupa kontrol besok ya.', 'status' => 'menunggu',
        ]);
        MessageReply::create([
            'pnpp_id' => $budi->id, 'no_hp' => '6281234567890', 'nama' => 'Budi Santoso',
            'isi_pesan' => 'Siap dok, saya hadir.', 'waktu_masuk' => now()->addMinute(), 'driver' => 'waha',
        ]);

        $this->actingAs($this->superadmin())
            ->get(route('admin.respon.show', '6281234567890'))
            ->assertOk()
            ->assertSee('Jangan lupa kontrol besok ya.')
            ->assertSee('Siap dok, saya hadir.')
            ->assertSee('Budi Santoso');
    }

    #[Test]
    public function halaman_riwayat_follow_up_hanya_menampilkan_jenis_follow_up(): void
    {
        $budi = Pnpp::create(['nama' => 'Budi Santoso', 'no_hp' => '081234567890']);

        MessageLog::create([
            'jenis' => 'follow_up', 'rule' => 'h-1', 'pnpp_id' => $budi->id, 'penerima_nama' => 'Budi Santoso',
            'penerima_no_hp' => '6281234567890', 'konten' => 'Follow up kabar Anda.', 'status' => 'terkirim',
        ]);
        MessageLog::create([
            'jenis' => 'outreach', 'rule' => 'h-7', 'pnpp_id' => $budi->id, 'penerima_nama' => 'Budi Santoso',
            'penerima_no_hp' => '6281234567890', 'konten' => 'Pesan outreach.', 'status' => 'terkirim',
        ]);

        $this->actingAs($this->superadmin())
            ->get(route('admin.follow-up.index'))
            ->assertOk()
            ->assertSee('Follow up kabar Anda.')
            ->assertDontSee('Pesan outreach.');
    }

    #[Test]
    public function user_biasa_ditolak_dari_modul_follow_up_dan_outreach(): void
    {
        $user = User::where('email', 'user@gmail.com')->firstOrFail();

        $this->actingAs($user)->get(route('admin.follow-up.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.follow-up.generate'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.outreach.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.outreach.generate'))->assertForbidden();
    }
}

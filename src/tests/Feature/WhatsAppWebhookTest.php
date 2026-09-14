<?php

namespace Tests\Feature;

use App\Models\Pnpp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('whatsapp.webhook.verify_token', 'token_uji');
        Config::set('whatsapp.webhook.app_secret', 'rahasia_uji');
    }

    protected function balasanPayload(): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '108633889005396',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'contacts' => [
                                    ['profile' => ['name' => 'Budi Santoso'], 'wa_id' => '6281234567890'],
                                ],
                                'messages' => [
                                    [
                                        'from' => '6281234567890',
                                        'id' => 'wamid.ABC123',
                                        'timestamp' => '1700000000',
                                        'type' => 'text',
                                        'text' => ['body' => 'Siap dok, saya hadir.'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[Test]
    public function verifikasi_meta_mengembalikan_challenge(): void
    {
        $this->get('/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=token_uji&hub_challenge=123456789')
            ->assertOk()
            ->assertSee('123456789');
    }

    #[Test]
    public function verifikasi_token_salah_ditolak(): void
    {
        $this->get('/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=salah&hub_challenge=123456789')
            ->assertForbidden();
    }

    #[Test]
    public function handle_menyimpan_balasan_dan_mencocokkan_pnpp(): void
    {
        $budi = Pnpp::create(['nama' => 'Budi Santoso', 'no_hp' => '081234567890']);

        $payload = $this->balasanPayload();
        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'rahasia_uji');

        $this->postJson('/whatsapp/webhook', $payload, ['X-Hub-Signature-256' => $signature])
            ->assertOk()
            ->assertJson(['status' => 'ok', 'diproses' => 1]);

        $this->assertDatabaseHas('message_replies', [
            'no_hp' => '6281234567890',
            'pnpp_id' => $budi->id,
            'nama' => 'Budi Santoso',
            'isi_pesan' => 'Siap dok, saya hadir.',
            'driver' => 'meta',
        ]);
    }

    #[Test]
    public function tanda_tangan_salah_ditolak(): void
    {
        $this->postJson('/whatsapp/webhook', $this->balasanPayload(), [
            'X-Hub-Signature-256' => 'sha256=signature-salah',
        ])->assertForbidden();
    }

    #[Test]
    public function pesan_non_teks_diabadikan_sebagai_penanda(): void
    {
        $payload = $this->balasanPayload();
        $payload['entry'][0]['changes'][0]['value']['messages'][0] = [
            'from' => '6281234567890',
            'id' => 'wamid.IMG1',
            'timestamp' => '1700000100',
            'type' => 'image',
            'image' => ['caption' => 'Foto surat rujukan', 'mime_type' => 'image/jpeg'],
        ];

        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'rahasia_uji');

        $this->postJson('/whatsapp/webhook', $payload, ['X-Hub-Signature-256' => $signature])
            ->assertOk();

        $this->assertDatabaseHas('message_replies', [
            'no_hp' => '6281234567890',
            'isi_pesan' => '[image] Foto surat rujukan',
            'driver' => 'meta',
        ]);
    }

    #[Test]
    public function status_pengiriman_tidak_membuat_balasan(): void
    {
        $payload = $this->balasanPayload();
        $payload['entry'][0]['changes'][0]['value'] = [
            'statuses' => [
                [
                    'id' => 'wamid.STATUS1',
                    'status' => 'sent',
                    'timestamp' => '1700000200',
                    'recipient_id' => '6281234567890',
                ],
            ],
        ];

        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'rahasia_uji');

        $this->postJson('/whatsapp/webhook', $payload, ['X-Hub-Signature-256' => $signature])
            ->assertOk()
            ->assertJson(['status' => 'ok', 'diproses' => 0]);

        $this->assertDatabaseCount('message_replies', 0);
    }
}

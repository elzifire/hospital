<?php

namespace Tests\Feature;

use App\Models\MessageLog;
use App\Models\MessageReply;
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
    public function pilihan_tombol_template_tersimpan_dengan_labelnya(): void
    {
        $payload = $this->balasanPayload();
        $payload['entry'][0]['changes'][0]['value']['messages'][0] = [
            'from' => '6281234567890',
            'id' => 'wamid.BTN1',
            'timestamp' => '1700000120',
            'type' => 'button',
            'button' => ['text' => 'Jadwalkan Kunjungan', 'payload' => 'JADWAL_KUNJUNGAN_V1'],
            'text' => ['body' => 'Jadwalkan Kunjungan'],
        ];

        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'rahasia_uji');

        $this->postJson('/whatsapp/webhook', $payload, ['X-Hub-Signature-256' => $signature])
            ->assertOk()
            ->assertJson(['status' => 'ok', 'diproses' => 1]);

        $this->assertDatabaseHas('message_replies', [
            'no_hp' => '6281234567890',
            'isi_pesan' => 'Jadwalkan Kunjungan',
            'driver' => 'meta',
        ]);

        $balasan = MessageReply::where('no_hp', '6281234567890')->firstOrFail();
        $this->assertSame('JADWAL_KUNJUNGAN_V1', data_get($balasan->payload, 'pesan.button.payload'));
    }

    #[Test]
    public function pilihan_interactive_button_reply_tersimpan_dengan_labelnya(): void
    {
        $payload = $this->balasanPayload();
        $payload['entry'][0]['changes'][0]['value']['messages'][0] = [
            'from' => '6281234567890',
            'id' => 'wamid.INT1',
            'timestamp' => '1700000130',
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button_reply',
                'button_reply' => ['id' => 'feedback_memuaskan', 'title' => 'Memuaskan'],
            ],
        ];

        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'rahasia_uji');

        $this->postJson('/whatsapp/webhook', $payload, ['X-Hub-Signature-256' => $signature])
            ->assertOk()
            ->assertJson(['status' => 'ok', 'diproses' => 1]);

        $this->assertDatabaseHas('message_replies', [
            'no_hp' => '6281234567890',
            'isi_pesan' => 'Memuaskan',
            'driver' => 'meta',
        ]);
    }

    #[Test]
    public function pilihan_interactive_list_reply_tersimpan_dengan_labelnya(): void
    {
        $payload = $this->balasanPayload();
        $payload['entry'][0]['changes'][0]['value']['messages'][0] = [
            'from' => '6281234567890',
            'id' => 'wamid.INT2',
            'timestamp' => '1700000140',
            'type' => 'interactive',
            'interactive' => [
                'type' => 'list_reply',
                'list_reply' => ['id' => 'poli_dalam', 'title' => 'Poli Penyakit Dalam'],
            ],
        ];

        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'rahasia_uji');

        $this->postJson('/whatsapp/webhook', $payload, ['X-Hub-Signature-256' => $signature])
            ->assertOk()
            ->assertJson(['status' => 'ok', 'diproses' => 1]);

        $this->assertDatabaseHas('message_replies', [
            'no_hp' => '6281234567890',
            'isi_pesan' => 'Poli Penyakit Dalam',
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

    #[Test]
    public function status_pengiriman_failed_memperbarui_message_log(): void
    {
        MessageLog::create([
            'jenis' => 'outreach',
            'rule' => 'manual',
            'penerima_nama' => 'Budi Santoso',
            'penerima_no_hp' => '6289516236766',
            'konten' => 'Test.',
            'status' => 'terkirim',
            'provider' => 'meta',
            'provider_message_id' => 'wamid.STATUS1',
        ]);

        $payload = $this->balasanPayload();
        $payload['entry'][0]['changes'][0]['value'] = [
            'statuses' => [
                [
                    'id' => 'wamid.STATUS1',
                    'status' => 'failed',
                    'timestamp' => '1700000200',
                    'recipient_id' => '6289516236766',
                    'errors' => [
                        ['code' => 131026, 'message' => 'Message failed to send because more than 24 hours have passed since the customer last replied.'],
                    ],
                ],
            ],
        ];

        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'rahasia_uji');

        $this->postJson('/whatsapp/webhook', $payload, ['X-Hub-Signature-256' => $signature])
            ->assertOk();

        $log = MessageLog::where('provider_message_id', 'wamid.STATUS1')->first();
        $this->assertSame('gagal', $log->status);
        $this->assertStringContainsString('131026', (string) $log->error);
        $this->assertStringContainsString('24 hours', (string) $log->error);
    }

    #[Test]
    public function status_sent_memperbarui_message_log_menjadi_terkirim(): void
    {
        MessageLog::create([
            'jenis' => 'outreach',
            'rule' => 'manual',
            'penerima_nama' => 'Budi Santoso',
            'penerima_no_hp' => '6289516236766',
            'konten' => 'Test.',
            'status' => 'mengirim',
            'provider' => 'meta',
            'provider_message_id' => 'wamid.STATUS2',
        ]);

        $payload = $this->balasanPayload();
        $payload['entry'][0]['changes'][0]['value'] = [
            'statuses' => [
                [
                    'id' => 'wamid.STATUS2',
                    'status' => 'sent',
                    'timestamp' => '1700000200',
                    'recipient_id' => '6289516236766',
                ],
            ],
        ];

        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'rahasia_uji');

        $this->postJson('/whatsapp/webhook', $payload, ['X-Hub-Signature-256' => $signature])
            ->assertOk();

        $log = MessageLog::where('provider_message_id', 'wamid.STATUS2')->first();
        $this->assertSame('terkirim', $log->status);
        $this->assertNotNull($log->sent_at);
    }
}

<?php

namespace Tests\Feature;

use App\Broadcasting\WhatsApp\AntreanKirim;
use App\Broadcasting\WhatsApp\LogSender;
use App\Broadcasting\WhatsApp\MetaSender;
use App\Jobs\KirimPesanJob;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Models\Pnpp;
use App\Models\Satker;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KirimPesanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        config([
            'whatsapp.meta.token' => 'test-token',
            'whatsapp.meta.phone_number_id' => '1403030969556500',
            'whatsapp.meta.base_url' => 'https://graph.facebook.com',
            'whatsapp.meta.version' => 'v25.0',
            'whatsapp.meta.timeout' => 15,
        ]);
    }

    protected function superadmin(): User
    {
        return User::where('email', 'superadmin@gmail.com')->firstOrFail();
    }

    protected function buatLog(array $attrs = []): MessageLog
    {
        $satker = Satker::create(['kode' => 'TS-'.str()->random(6), 'nama' => 'Satker Uji']);
        $pnpp = Pnpp::create(array_merge([
            'nama' => 'Budi Santoso',
            'nip' => '123-'.str()->random(6),
            'satker_id' => $satker->id,
            'no_hp' => '081234567890',
        ], $attrs['pnpp'] ?? []));

        $template = MessageTemplate::create([
            'judul' => 'Template Uji',
            'channel' => 'WhatsApp',
            'konten' => 'Halo {nama}.',
            'is_active' => true,
        ]);

        return MessageLog::create([
            'jenis' => 'outreach',
            'rule' => 'h-7',
            'message_template_id' => $template->id,
            'pnpp_id' => $pnpp->id,
            'penerima_nama' => $pnpp->nama,
            'penerima_no_hp' => '6281234567890',
            'konten' => 'Halo Budi Santoso.',
            'status' => 'menunggu',
            'meta_template_name' => 'promo_h1',
            'meta_language' => 'en_US',
            'template_params' => ['Budi Santoso'],
            ...($attrs['log'] ?? []),
        ]);
    }

    #[Test]
    public function log_sender_menandai_pesan_terkirim_dengan_provider_log(): void
    {
        $log = $this->buatLog();

        $sender = app(LogSender::class);
        $hasil = $sender->kirim($log);

        $this->assertTrue($hasil->ok);
        $this->assertSame('log', $hasil->provider);
        $this->assertStringStartsWith('log-', $hasil->messageId);
    }

    #[Test]
    public function job_sukses_dengan_sender_log(): void
    {
        $log = $this->buatLog();

        $job = new KirimPesanJob($log->id);
        $job->handle(app(LogSender::class));

        $this->assertSame('terkirim', $log->refresh()->status);
        $this->assertNotNull($log->sent_at);
        $this->assertSame('log', $log->provider);
        $this->assertStringStartsWith('log-', (string) $log->provider_message_id);
    }

    #[Test]
    public function job_skip_jika_status_bukan_menunggu(): void
    {
        $log = $this->buatLog(['log' => ['status' => 'terkirim']]);

        $job = new KirimPesanJob($log->id);
        $job->handle(app(LogSender::class));

        // Tidak berubah — status sudah final
        $this->assertSame('terkirim', $log->refresh()->status);
    }

    #[Test]
    public function meta_sender_membangun_payload_template_dengan_param(): void
    {
        $log = $this->buatLog([
            'log' => [
                'template_params' => ['Budi', 'Poli Umum'],
            ],
        ]);

        $url = config('whatsapp.meta.base_url')
            .'/'.config('whatsapp.meta.version')
            .'/'.config('whatsapp.meta.phone_number_id')
            .'/messages';

        Http::fake([
            str_replace('https://', '', $url) => Http::response([
                'messages' => [['id' => 'wamid.abc123']],
            ], 200),
        ]);

        $hasil = app(MetaSender::class)->kirim($log);

        $this->assertTrue($hasil->ok);
        $this->assertSame('wamid.abc123', $hasil->messageId);

        Http::assertSent(function ($request) use ($url) {
            $payload = $request->data();

            return $request->url() === $url
                && $payload['messaging_product'] === 'whatsapp'
                && $payload['type'] === 'template'
                && $payload['template']['name'] === 'promo_h1'
                && $payload['template']['language']['code'] === 'en_US'
                && $payload['to'] === '6281234567890'
                && $payload['template']['components'][0]['parameters'][0]['text'] === 'Budi'
                && ($payload['template']['components'][0]['parameters'][0]['parameter_name'] ?? null) === 'nama'
                && $payload['template']['components'][0]['parameters'][1]['text'] === 'Poli Umum'
                && ! isset($payload['template']['components'][0]['parameters'][1]['parameter_name']);
        });
    }

    #[Test]
    public function meta_sender_membangun_payload_header_gambar_sebelum_body(): void
    {
        $log = $this->buatLog([
            'log' => [
                'template_params' => ['Budi'],
            ],
        ]);

        $log->template->update(['image_url' => 'https://rs-bhayangkara.id/images/sampul.jpg']);

        $url = config('whatsapp.meta.base_url')
            .'/'.config('whatsapp.meta.version')
            .'/'.config('whatsapp.meta.phone_number_id')
            .'/messages';

        Http::fake([
            str_replace('https://', '', $url) => Http::response([
                'messages' => [['id' => 'wamid.img001']],
            ], 200),
        ]);

        $hasil = app(MetaSender::class)->kirim($log);

        $this->assertTrue($hasil->ok);

        Http::assertSent(function ($request) use ($url) {
            $payload = $request->data();
            $components = $payload['template']['components'] ?? [];

            return $request->url() === $url
                && ($components[0]['type'] ?? null) === 'header'
                && ($components[0]['parameters'][0]['type'] ?? null) === 'image'
                && ($components[0]['parameters'][0]['image']['link'] ?? null) === 'https://rs-bhayangkara.id/images/sampul.jpg'
                && ($components[1]['type'] ?? null) === 'body';
        });
    }

    #[Test]
    public function meta_sender_mengirim_teks_bebas_saat_tanpa_template(): void
    {
        $log = $this->buatLog([
            'log' => [
                'meta_template_name' => null,
                'template_params' => [],
            ],
        ]);

        $url = config('whatsapp.meta.base_url')
            .'/'.config('whatsapp.meta.version')
            .'/'.config('whatsapp.meta.phone_number_id')
            .'/messages';

        Http::fake([
            str_replace('https://', '', $url) => Http::response([
                'messages' => [['id' => 'wamid.teks001']],
            ], 200),
        ]);

        $hasil = app(MetaSender::class)->kirim($log);

        $this->assertTrue($hasil->ok);

        Http::assertSent(function ($request) use ($url) {
            $payload = $request->data();

            return $request->url() === $url
                && $payload['type'] === 'text'
                && $payload['text']['body'] === 'Halo Budi Santoso.'
                && ($payload['recipient_type'] ?? null) === 'individual'
                && ! isset($payload['template']);
        });
    }

    #[Test]
    public function meta_sender_menyelaraskan_jumlah_param_dengan_template_sinkron(): void
    {
        $log = $this->buatLog();

        // Template hasil sinkron dari Meta: body memakai 3 placeholder.
        $log->template->update(['meta_components' => [
            ['type' => 'BODY', 'text' => 'Halo {{1}}, jadwal Anda {{2}} pukul {{3}}.'],
        ]]);

        $url = config('whatsapp.meta.base_url')
            .'/'.config('whatsapp.meta.version')
            .'/'.config('whatsapp.meta.phone_number_id')
            .'/messages';

        Http::fake([
            str_replace('https://', '', $url) => Http::response([
                'messages' => [['id' => 'wamid.s123']],
            ], 200),
        ]);

        // Kurang dari 3 → digenapi dengan "—" (hindari #132000).
        $log->update(['template_params' => ['Budi', 'Senin']]);
        app(MetaSender::class)->kirim($log);

        Http::assertSent(function ($request) {
            $parameters = $request->data()['template']['components'][0]['parameters'] ?? [];

            return count($parameters) === 3
                && $parameters[2]['text'] === '—';
        });

        // Lebih dari 3 → dipotong rapi.
        $log->update(['template_params' => ['Budi', 'Senin', '09:00', 'ekstra']]);
        app(MetaSender::class)->kirim($log);

        Http::assertSent(function ($request) {
            $parameters = $request->data()['template']['components'][0]['parameters'] ?? [];

            return count($parameters) === 3
                && $parameters[2]['text'] === '09:00';
        });
    }

    #[Test]
    public function meta_sender_gagal_ketika_api_menolak(): void
    {
        $log = $this->buatLog();

        $url = config('whatsapp.meta.base_url')
            .'/'.config('whatsapp.meta.version')
            .'/'.config('whatsapp.meta.phone_number_id')
            .'/messages';

        Http::fake([
            str_replace('https://', '', $url) => Http::response([
                'error' => ['message' => 'Unsupported post request.'],
            ], 400),
        ]);

        $hasil = app(MetaSender::class)->kirim($log);

        $this->assertFalse($hasil->ok);
        $this->assertStringContainsString('Meta Cloud API menolak', $hasil->error);
    }

    #[Test]
    public function meta_sender_gagal_tanpa_token_config(): void
    {
        config(['whatsapp.meta.token' => null, 'whatsapp.meta.phone_number_id' => null]);

        $log = $this->buatLog();
        $hasil = app(MetaSender::class)->kirim($log);

        $this->assertFalse($hasil->ok);
        $this->assertStringContainsString('belum diisi', $hasil->error);
    }

    #[Test]
    public function command_broadcast_kirim_menggunakan_queue(): void
    {
        Queue::fake();

        MessageLog::create([
            'jenis' => 'outreach',
            'rule' => 'h-7',
            'penerima_nama' => 'Budi',
            'penerima_no_hp' => '6281234567890',
            'konten' => 'Test.',
            'status' => 'menunggu',
        ]);

        $this->artisan('broadcast:kirim')
            ->expectsOutput('1 pesan masuk antrean pengiriman WhatsApp.')
            ->assertExitCode(0);

        Queue::assertPushed(KirimPesanJob::class, 1);
    }

    #[Test]
    public function command_broadcast_kirim_skip_jika_tidak_ada_menunggu(): void
    {
        Queue::fake();

        $this->artisan('broadcast:kirim')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function antrean_melewatkan_pesan_terjadwal_di_masa_depan(): void
    {
        Queue::fake();

        $tunda = $this->buatLog(['log' => ['kirim_pada' => now()->addDay()]]);
        $lewat = $this->buatLog(['log' => ['kirim_pada' => now()->subMinute()]]);
        $tanpaJadwal = $this->buatLog();

        app(AntreanKirim::class)->antarkan(50);

        Queue::assertPushed(KirimPesanJob::class, 2);
        Queue::assertPushed(KirimPesanJob::class, fn ($job) => $job->logId === $lewat->id);
        Queue::assertPushed(KirimPesanJob::class, fn ($job) => $job->logId === $tanpaJadwal->id);
        Queue::assertNotPushed(KirimPesanJob::class, fn ($job) => $job->logId === $tunda->id);
    }

    #[Test]
    public function kirim_sinkron_via_antrean_kirim(): void
    {
        $log = $this->buatLog();

        $hasil = app(AntreanKirim::class)->kirimSinkron([$log]);

        $this->assertSame(1, $hasil['terkirim']);
        $this->assertSame(0, $hasil['gagal']);
        $this->assertSame('terkirim', $log->refresh()->status);
    }

    #[Test]
    public function kirim_sinkron_skip_jika_sudah_final(): void
    {
        $log = $this->buatLog(['log' => ['status' => 'terkirim']]);

        $hasil = app(AntreanKirim::class)->kirimSinkron([$log]);

        $this->assertSame(0, $hasil['terkirim']);
        $this->assertSame(1, $hasil['dilewati']);
    }

    #[Test]
    public function kirim_sinkron_gagal_ketika_api_error(): void
    {
        $log = $this->buatLog();

        $url = config('whatsapp.meta.base_url')
            .'/'.config('whatsapp.meta.version')
            .'/'.config('whatsapp.meta.phone_number_id')
            .'/messages';

        Http::fake([
            str_replace('https://', '', $url) => Http::response([
                'error' => ['message' => 'Unsupported post request.'],
            ], 400),
        ]);

        // Gunakan MetaSender langsung — container bind ke LogSender karena
        // WA_DRIVER=log di phpunit.xml, tapi test ini ingin uji path gagal API.
        $antrean = new AntreanKirim(app(MetaSender::class));
        $hasil = $antrean->kirimSinkron([$log]);

        $this->assertSame(0, $hasil['terkirim']);
        $this->assertSame(1, $hasil['gagal']);
        $this->assertSame('gagal', $log->refresh()->status);
        $this->assertStringContainsString('Meta Cloud API', $log->error);
    }
}

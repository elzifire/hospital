<?php

namespace Tests\Feature;

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

/**
 * Alur "Kirim Sekarang" masuk antrean (async via queue) — form diperbaiki
 * agar tidak tersendat saat banyak penerima: pesan dibuat lalu job
 * KirimPesanJob di-queue, redirect langsung, worker mengirim di background.
 */
class ManualBroadcastAsyncTest extends TestCase
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

    protected function buatTemplate(): MessageTemplate
    {
        return MessageTemplate::create([
            'judul' => 'Jadwal Poli',
            'kode' => 'TMP-'.str()->random(6),
            'channel' => 'WhatsApp',
            'konten' => 'Halo {nama}, jadwal Anda di {poli} pada {tanggal} pukul {jam}.',
            'is_active' => true,
            'meta_param_tokens' => ['nama', 'tanggal'],
            'meta_template_name' => 'jadwal_poli',
            'meta_language' => 'id',
        ]);
    }

    protected function buatPnpp(int $jumlah): array
    {
        $satker = Satker::create(['kode' => 'TA-'.str()->random(6), 'nama' => 'Satker Async']);

        $ids = [];
        for ($i = 0; $i < $jumlah; $i++) {
            $ids[] = Pnpp::create([
                'nama' => 'Pasien '.($i + 1),
                'nip' => '900-'.str()->random(6),
                'satker_id' => $satker->id,
                'no_hp' => '08'.str_pad((string) (100000000 + $i), 9, '0', STR_PAD_LEFT),
            ])->id;
        }

        return $ids;
    }

    #[Test]
    public function kirim_sekarang_mengantrekan_satu_job_per_pasien(): void
    {
        Queue::fake();
        Http::fake();

        $ids = $this->buatPnpp(100);

        $response = $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => $ids,
                'jenis' => 'outreach',
                'message_template_id' => $this->buatTemplate()->id,
                'mode' => 'sekarang',
            ]);

        $response->assertRedirect(route('admin.outreach.index'));
        $response->assertSessionHas('success');

        $this->assertSame(100, MessageLog::count());
        $this->assertSame(100, MessageLog::where('status', 'menunggu')->count());

        Queue::assertPushed(KirimPesanJob::class, 100);
        foreach ($ids as $id) {
            $logId = MessageLog::where('pnpp_id', $id)->value('id');
            Queue::assertPushed(KirimPesanJob::class, fn (KirimPesanJob $job) => $job->logId === $logId);
        }

        // Tidak boleh ada pemanggilan HTTP keluar (Meta/holder tidak di-hit).
        Http::assertNothingSent();
    }

    #[Test]
    public function kirim_sekarang_tanpa_nomor_valid_tidak_mengantrekan_pesan(): void
    {
        Queue::fake();

        $tidakAda = Pnpp::create([
            'nama' => 'Tanpa Nomor',
            'nip' => '901-'.str()->random(6),
            'no_hp' => null,
        ]);

        $response = $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => [$tidakAda->id],
                'jenis' => 'outreach',
                'message_template_id' => $this->buatTemplate()->id,
                'mode' => 'sekarang',
            ]);

        $response->assertRedirect(route('admin.outreach.index'));
        $response->assertSessionHas('error');

        $this->assertSame('gagal', MessageLog::where('pnpp_id', $tidakAda->id)->value('status'));
        Queue::assertNothingPushed();
    }

    #[Test]
    public function mode_jadwalkan_tetap_tidak_mengantrekan_sebelum_waktunya(): void
    {
        Queue::fake();

        $ids = $this->buatPnpp(3);

        $this->actingAs($this->superadmin())
            ->post(route('admin.outreach.store'), [
                'pnpp_ids' => $ids,
                'jenis' => 'outreach',
                'message_template_id' => $this->buatTemplate()->id,
                'mode' => 'jadwalkan',
                'kirim_pada' => now()->addHour()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('admin.outreach.index'))
            ->assertSessionHas('success');

        $this->assertSame(3, MessageLog::where('status', 'menunggu')->count());
        $this->assertSame(3, MessageLog::whereNotNull('kirim_pada')->count());

        // Belum waktunya — tidak boleh ada job yang diantrekan dulu.
        Queue::assertNothingPushed();
    }
}
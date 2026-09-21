<?php

namespace Tests\Feature;

use App\Models\Dokter;
use App\Models\Jadwal;
use App\Models\Kunjungan;
use App\Models\MessageLog;
use App\Models\MessageReply;
use App\Models\MessageTemplate;
use App\Models\PenyakitKronis;
use App\Models\PenyakitMenahun;
use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\Reminder;
use App\Models\Satker;
use App\Models\User;
use App\Support\MonitoringRegistry;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonitoringReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->seedReportData();
    }

    /**
     * Data contoh untuk semua entitas laporan (master, kunjungan, broadcasting).
     */
    private function seedReportData(): void
    {
        $dinkes = Satker::create(['kode' => 'DINKES', 'nama' => 'Dinas Kesehatan']);
        Satker::create(['kode' => 'BPJS', 'nama' => 'BPJS Kesehatan']);

        $hipertensi = PenyakitKronis::create(['kode' => 'HTN', 'nama' => 'Hipertensi']);
        $ginjal = PenyakitMenahun::create(['kode' => 'GINJAL', 'nama' => 'Gagal Ginjal Kronis']);

        $budi = Pnpp::create([
            'nama' => 'Budi Santoso',
            'nip' => '198501012010011001',
            'no_bpjs' => '0001234567890',
            'satker_id' => $dinkes->id,
            'no_hp' => '081234567890',
            'email' => 'budi@contoh.id',
            'status_aktif' => 'aktif',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '1985-01-01',
        ]);
        $budi->penyakit()->attach($hipertensi);
        $budi->penyakitMenahun()->attach($ginjal);

        $siti = Pnpp::create([
            'nama' => 'Siti Aminah',
            'nip' => '199003152015122002',
            'satker_id' => $dinkes->id,
            'status_aktif' => 'nonaktif',
            'jenis_kelamin' => 'P',
        ]);

        Kunjungan::create(['pnpp_id' => $budi->id, 'tanggal_kunjungan' => '2026-09-01', 'keluhan' => 'Pusing', 'diagnosa' => 'Hipertensi derajat 1']);
        Kunjungan::create(['pnpp_id' => $siti->id, 'tanggal_kunjungan' => '2026-08-15', 'keluhan' => 'Batuk', 'diagnosa' => 'ISPA']);

        $poli = Poli::create(['kode' => 'UMUM-MON', 'nama' => 'Poli Umum']);
        $dokter = Dokter::create(['poli_id' => $poli->id, 'nama' => 'dr. Rina Pratiwi', 'spesialisasi' => 'Dokter Umum']);
        Jadwal::create(['dokter_id' => $dokter->id, 'hari' => 'Senin', 'jam_mulai' => '08:00', 'jam_selesai' => '12:00']);
        Jadwal::create(['dokter_id' => $dokter->id, 'hari' => 'Rabu', 'jam_mulai' => '09:00', 'jam_selesai' => '13:00']);

        // Data contoh laporan broadcasting: penjadwalan (reminders) sebagai
        // sumber pesan, pesan keluar (message_logs), dan balasan masuk.
        $templateWa = MessageTemplate::create([
            'judul' => 'Pengingat Kontrol',
            'channel' => 'WhatsApp',
            'konten' => 'Halo {nama}, jangan lupa kontrol Anda.',
            'is_active' => true,
        ]);

        $reminderBudi = Reminder::create([
            'pnpp_id' => $budi->id,
            'poli_id' => $poli->id,
            'dokter_id' => $dokter->id,
            'tanggal' => today()->addDays(2)->format('Y-m-d'),
            'jam' => '09:00',
            'home_visit' => true,
            'status' => 'terjadwal',
            'catatan' => 'Home visit rutin bulanan',
        ]);

        // Jadwal kunjungan RS untuk sumber pesan follow up — pesan yang
        // berasal dari jadwal home visit dikecualikan dari laporan follow up.
        $reminderBudiKunjunganRs = Reminder::create([
            'pnpp_id' => $budi->id,
            'poli_id' => $poli->id,
            'dokter_id' => $dokter->id,
            'tanggal' => today()->subDays(2)->format('Y-m-d'),
            'jam' => '09:00',
            'home_visit' => false,
            'status' => 'tidak_datang',
        ]);

        MessageLog::create([
            'jenis' => 'outreach',
            'rule' => 'h-7',
            'reminder_id' => $reminderBudi->id,
            'message_template_id' => $templateWa->id,
            'pnpp_id' => $budi->id,
            'penerima_nama' => 'Budi Santoso',
            'penerima_no_hp' => '6281234567890',
            'konten' => 'Halo Budi Santoso, jangan lupa kontrol Anda.',
            'status' => 'terkirim',
            'sent_at' => now()->subDay(),
        ]);
        MessageLog::create([
            'jenis' => 'outreach',
            'rule' => 'h-1',
            'message_template_id' => $templateWa->id,
            'pnpp_id' => $siti->id,
            'penerima_nama' => 'Siti Aminah',
            'penerima_no_hp' => '081298765432',
            'konten' => 'Halo Siti Aminah, jangan lupa kontrol Anda.',
            'status' => 'gagal',
            'error' => 'Pasien tidak memiliki nomor WhatsApp yang valid.',
        ]);
        MessageLog::create([
            'jenis' => 'follow_up',
            'rule' => 'tidak_datang',
            'reminder_id' => $reminderBudiKunjunganRs->id,
            'message_template_id' => $templateWa->id,
            'pnpp_id' => $budi->id,
            'penerima_nama' => 'Budi Santoso',
            'penerima_no_hp' => '6281234567890',
            'konten' => 'Kok tidak datang pada jadwal kemarin?',
            'status' => 'menunggu',
        ]);
        MessageReply::create([
            'pnpp_id' => $budi->id,
            'no_hp' => '6281234567890',
            'nama' => 'Budi Santoso',
            'isi_pesan' => 'Baik, saya sudah terima. Terima kasih.',
            'waktu_masuk' => now()->subDays(3)->addHour(),
            'driver' => 'waha',
        ]);
        MessageReply::create([
            'no_hp' => '081355557777',
            'isi_pesan' => 'Stop broadcast',
            'waktu_masuk' => now()->subHours(5),
            'driver' => 'waha',
        ]);
        MessageReply::create([
            'pnpp_id' => $budi->id,
            'no_hp' => '6281234567890',
            'nama' => 'Budi Santoso',
            'isi_pesan' => 'HADIR',
            'waktu_masuk' => now()->subHours(2),
            'driver' => 'meta',
            'payload' => [
                'pesan' => ['type' => 'button', 'text' => 'HADIR'],
                'metadata' => null,
            ],
        ]);
    }

    private function superadmin(): User
    {
        return User::where('email', 'superadmin@gmail.com')->firstOrFail();
    }

    private function plainUser(): User
    {
        return User::where('email', 'user@gmail.com')->firstOrFail();
    }

    #[Test]
    public function superadmin_melihat_hub_monitoring_dengan_grup_sidebar()
    {
        $response = $this->actingAs($this->superadmin())->get('/admin/monitoring');

        $response->assertOk();
        $response->assertSee('Data Master');
        $response->assertSee('Broadcasting');
        $response->assertSee('PNPP');
        $response->assertSee('Kunjungan');
        $response->assertSee('Outreach');
        $response->assertSee('Digital Reminder');
        $response->assertSee('Respon');
        $response->assertSee('Follow Up');
        $response->assertSee('10 laporan aktif'); // semua laporan kini tersedia
    }

    #[Test]
    public function semua_laporan_yang_tersedia_bisa_diakses()
    {
        $this->actingAs($this->superadmin());

        foreach (MonitoringRegistry::configs() as $entity => $config) {
            if (! ($config['available'] ?? false) || ($config['hidden'] ?? false)) {
                continue;
            }

            $response = $this->get(route('admin.monitoring.report.show', $entity));

            $response->assertOk();
            $response->assertSee('Laporan '.$config['label']);
        }
    }

    #[Test]
    public function semua_sort_filter_dan_pencarian_tereksekusi_tanpa_error()
    {
        $this->actingAs($this->superadmin());

        foreach (MonitoringRegistry::configs() as $entity => $config) {
            if (! ($config['available'] ?? false) || ($config['hidden'] ?? false)) {
                continue;
            }

            $base = route('admin.monitoring.report.show', $entity);

            // Semua opsi sort
            foreach ($config['sorts'] ?? [] as $key => $sort) {
                $this->get($base.'?sort='.$key)->assertOk();
            }

            // Semua filter (pilih opsi pertama yang valid)
            foreach ($config['filters'] ?? [] as $filter) {
                $value = ($filter['type'] ?? 'select') === 'date'
                    ? '2026-08-01'
                    : array_key_first(($filter['options'])());

                $this->get($base.'?'.$filter['key'].'='.urlencode((string) $value))->assertOk();
            }

            // Pencarian: aman untuk semua entitas & tampil empty state bila tanpa hasil
            $this->get($base.'?search=Budi')->assertOk();
            $this->get($base.'?search=zzz tidak ada')->assertOk()->assertSee('Tidak ada data');
        }

        // Entitas yang datanya menyimpan nama pasien: pencarian menemukan hasil
        $this->get(route('admin.monitoring.report.show', ['pnpp', 'search' => 'Budi']))->assertOk()->assertSee('Budi Santoso');
        $this->get(route('admin.monitoring.report.show', ['kunjungan', 'search' => 'Siti']))->assertOk()->assertSee('Siti Aminah');
    }

    #[Test]
    public function pagination_berjalan_di_sisi_server()
    {
        $this->actingAs($this->superadmin());

        $response = $this->get(route('admin.monitoring.report.show', ['kunjungan', 'per_page' => 1, 'page' => 2]));

        $response->assertOk();
        $response->assertSee('Menampilkan');

        // per_page di luar daftar valid otomatis fallback ke 10
        $this->get(route('admin.monitoring.report.show', ['kunjungan', 'per_page' => 7]))->assertOk();
    }

    #[Test]
    public function export_xlsx_dan_csv_mengikuti_filter_aktif()
    {
        $this->actingAs($this->superadmin());

        foreach (['xlsx', 'csv'] as $format) {
            $response = $this->get(route('admin.monitoring.report.export', ['kunjungan', 'format' => $format, 'from' => '2026-08-01', 'to' => '2026-08-31']));

            $response->assertOk();
            $this->assertSame(
                $format === 'csv' ? 'text/csv; charset=UTF-8' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                $response->headers->get('Content-Type')
            );
            $this->assertStringContainsString('laporan_kunjungan_', (string) $response->headers->get('Content-Disposition'));
        }

        // Semua entitas tersedia bisa di-export (mengeksekusi toRow tiap baris)
        foreach (MonitoringRegistry::configs() as $entity => $config) {
            if (! ($config['available'] ?? false) || ($config['hidden'] ?? false)) {
                continue;
            }

            $this->get(route('admin.monitoring.report.export', $entity))->assertOk();
        }
    }

    #[Test]
    public function entitas_tidak_dikenal_ditolak()
    {
        $this->actingAs($this->superadmin());

        $this->get(route('admin.monitoring.report.show', 'entitas-aneh'))->assertNotFound();
        $this->get(route('admin.monitoring.report.export', 'entitas-aneh'))->assertNotFound();
    }

    #[Test]
    public function kartu_dokter_dan_jadwal_disembunyikan_sementara()
    {
        $this->actingAs($this->superadmin());

        // Kartu tidak muncul di hub (cek via URL laporan yang tidak dirender)
        $hub = $this->get('/admin/monitoring');
        $hub->assertOk();
        $hub->assertDontSee(route('admin.monitoring.report.show', 'dokter'));
        $hub->assertDontSee(route('admin.monitoring.report.show', 'jadwal'));
        $hub->assertSee('5/5 laporan'); // grup master tanpa dokter & jadwal

        // Akses langsung laporan & export tetap tertutup
        $this->get(route('admin.monitoring.report.show', 'dokter'))->assertNotFound();
        $this->get(route('admin.monitoring.report.show', 'jadwal'))->assertNotFound();
        $this->get(route('admin.monitoring.report.export', 'dokter'))->assertNotFound();
        $this->get(route('admin.monitoring.report.export', 'jadwal'))->assertNotFound();
    }

    #[Test]
    public function user_tanpa_permission_master_tidak_bisa_lihat_laporan_master()
    {
        $user = $this->plainUser();
        $this->assertFalse($user->can('manage pnpp'));

        // Laporan master ditolak
        $this->actingAs($user)->get(route('admin.monitoring.report.show', 'pnpp'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.monitoring.report.export', 'pnpp'))->assertForbidden();

        // Hub tetap terbuka, tetapi tanpa grup Data Master maupun Broadcasting
        // (user biasa tidak memegang permission fitur apa pun — "Broadcasting"
        // di cek lewat subjudul grup, karena label itu juga ada di sidebar)
        $hub = $this->actingAs($user)->get('/admin/monitoring');
        $hub->assertOk();
        $hub->assertDontSee('Total PNPP');
        $hub->assertDontSee('Ringkasan aktivitas pengiriman pesan');
        $hub->assertSee('0 laporan aktif');

        // Laporan kunjungan ikut terkunci (butuh permission manage kunjungan)
        $this->actingAs($user)->get(route('admin.monitoring.report.show', 'kunjungan'))->assertForbidden();
    }

    #[Test]
    public function laporan_broadcasting_menampilkan_data_dan_filternya()
    {
        $this->actingAs($this->superadmin());

        // Laporan outreach: baris terkirim & gagal tampil + badge aturan
        $outreach = $this->get(route('admin.monitoring.report.show', 'outreach'));
        $outreach->assertOk();
        $outreach->assertSee('Budi Santoso');
        $outreach->assertSee('Siti Aminah');
        $outreach->assertSee('Pengingat Kontrol');
        $outreach->assertSee('H-7');

        // Filter status: hanya baris gagal
        $gagal = $this->get(route('admin.monitoring.report.show', ['outreach', 'status' => 'gagal']));
        $gagal->assertOk();
        $gagal->assertSee('Siti Aminah');
        $gagal->assertDontSee('Budi Santoso');

        // Laporan digital reminder: berbasis tabel reminders (bukan pesan)
        $reminder = $this->get(route('admin.monitoring.report.show', 'digital-reminder'));
        $reminder->assertOk();
        $reminder->assertSee('Budi Santoso');
        $reminder->assertSee('Home Visit');
        $reminder->assertDontSee('Siti Aminah');

        // Laporan follow up: badge aturan rule tidak datang
        $followUp = $this->get(route('admin.monitoring.report.show', 'follow-up'));
        $followUp->assertOk();
        $followUp->assertSee('Budi Santoso');
        $followUp->assertSee('Tidak Datang');

        // Laporan respon: balasan pasien terdaftar & nomor tak dikenal
        $respon = $this->get(route('admin.monitoring.report.show', 'respon'));
        $respon->assertOk();
        $respon->assertSee('Baik, saya sudah terima. Terima kasih.');
        $respon->assertSee('Tidak Terdaftar');
        $respon->assertSee('HADIR'); // balasan tombol (isi label)
        $respon->assertSee('Tombol'); // kolom Sumber

        // Filter respon: hanya nomor tak dikenal
        $takDikenal = $this->get(route('admin.monitoring.report.show', ['respon', 'terdaftar' => 'no']));
        $takDikenal->assertOk();
        $takDikenal->assertSee('Stop broadcast');
        $takDikenal->assertDontSee('Baik, saya sudah terima');

        // Filter respon: hanya pilihan tombol (payload type button/interactive)
        $tombol = $this->get(route('admin.monitoring.report.show', ['respon', 'jenis' => 'tombol']));
        $tombol->assertOk();
        $tombol->assertSee('HADIR');
        $tombol->assertDontSee('Stop broadcast');
        $tombol->assertDontSee('Baik, saya sudah terima');

        // Filter respon: hanya teks biasa (bukan dari tombol)
        $teks = $this->get(route('admin.monitoring.report.show', ['respon', 'jenis' => 'teks']));
        $teks->assertOk();
        $teks->assertSee('Stop broadcast');
        $teks->assertSee('Baik, saya sudah terima');
        $teks->assertDontSee('HADIR');
    }

    #[Test]
    public function filter_template_laporan_outreach_menyaring_baris()
    {
        $this->actingAs($this->superadmin());

        $templateLain = MessageTemplate::create([
            'judul' => 'Pengingat Alternatif',
            'channel' => 'WhatsApp',
            'konten' => 'Halo {nama}, cek jadwal Anda.',
            'is_active' => true,
        ]);

        $budi = Pnpp::where('nama', 'Budi Santoso')->firstOrFail();

        MessageLog::create([
            'jenis' => 'outreach',
            'rule' => 'manual',
            'message_template_id' => $templateLain->id,
            'pnpp_id' => $budi->id,
            'penerima_nama' => 'Cici via Alternatif',
            'penerima_no_hp' => '6281234567891',
            'konten' => 'Halo Cici, cek jadwal Anda.',
            'status' => 'terkirim',
        ]);

        // Tanpa filter: kedua baris tampil.
        $this->get(route('admin.monitoring.report.show', 'outreach'))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Cici via Alternatif');

        // Filter template: hanya baris dengan template terpilih.
        $this->get(route('admin.monitoring.report.show', ['outreach', 'template' => $templateLain->id]))
            ->assertOk()
            ->assertSee('Cici via Alternatif')
            ->assertDontSee('Budi Santoso');
    }

    #[Test]
    public function user_tanpa_permission_broadcasting_ditolak_dari_laporan_broadcasting()
    {
        $user = $this->plainUser();
        $this->assertFalse($user->can('manage outreach'));

        // Laporan pesan & balasan ditolak (show + export)
        foreach (['outreach', 'digital-reminder', 'respon', 'follow-up'] as $entity) {
            $this->actingAs($user)->get(route('admin.monitoring.report.show', $entity))->assertForbidden();
            $this->actingAs($user)->get(route('admin.monitoring.report.export', $entity))->assertForbidden();
        }

        // Hub: semua kartu laporan (termasuk kunjungan) tersembunyi untuk user biasa
        $hub = $this->actingAs($user)->get('/admin/monitoring');
        $hub->assertOk();
        $hub->assertDontSee(route('admin.monitoring.report.show', 'outreach'));
        $hub->assertDontSee(route('admin.monitoring.report.show', 'respon'));
        $hub->assertDontSee(route('admin.monitoring.report.show', 'kunjungan'));
    }
}

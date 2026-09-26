<?php

namespace Tests\Feature;

use App\Models\HariLibur;
use App\Support\GoogleCalendarSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Sinkronisasi hari libur dari Google Calendar publik (read-only):
 *  - event all-day (start.date) & event dengan jam (start.dateTime) masuk
 *    ke hari_liburs dengan sumber google_calendar + event_id.
 *  - event yang sudah ada diperbarui mengikuti kalender (bukan duplikat).
 *  - baris manual tidak pernah disentuh.
 *  - event hasil sinkron yang tidak lagi muncul dihapus (stale).
 *  - api key / calendar id kosong -> error, tanpa request HTTP.
 */
class GoogleCalendarSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'google-calendar.api_key' => 'kunci-uji',
            'google-calendar.calendar_id' => 'id.indonesia#holiday@group.v.calendar.google.com',
            'google-calendar.lookahead_months' => 12,
            'app.timezone' => 'Asia/Jakarta',
        ]);
    }

    #[Test]
    public function sync_menaruh_event_all_day_dan_timed_ke_hari_liburs(): void
    {
        $allDay = today()->addDays(3);
        $timed = today()->addDays(5);

        Http::fake([
            'googleapis.com/calendar/v3/*' => Http::response([
                'items' => [
                    ['id' => 'ev-all-day', 'status' => 'confirmed', 'summary' => 'Tahun Baru', 'start' => ['date' => $allDay->format('Y-m-d')]],
                    ['id' => 'ev-timed', 'status' => 'confirmed', 'summary' => 'Cuti Bersama', 'start' => ['dateTime' => $timed->format('Y-m-d').'T12:00:00+07:00']],
                    ['id' => 'ev-dibatalkan', 'status' => 'cancelled', 'summary' => 'Batal', 'start' => ['date' => $allDay->format('Y-m-d')]],
                ],
            ], 200),
        ]);

        $hasil = app(GoogleCalendarSync::class)->sync();

        $this->assertNull($hasil['error'], 'tidak boleh ada error');
        $this->assertSame(2, $hasil['dibuat']);
        $this->assertSame(2, $hasil['jumlah']);

        $this->assertDatabaseHas('hari_liburs', [
            'tanggal' => $allDay->format('Y-m-d'),
            'nama' => 'Tahun Baru',
            'event_id' => 'ev-all-day',
            'sumber' => HariLibur::SUMBER_GOOGLE_CALENDAR,
        ]);
        $this->assertDatabaseHas('hari_liburs', [
            'tanggal' => $timed->format('Y-m-d'),
            'nama' => 'Cuti Bersama',
            'event_id' => 'ev-timed',
            'sumber' => HariLibur::SUMBER_GOOGLE_CALENDAR,
        ]);
        $this->assertDatabaseMissing('hari_liburs', ['event_id' => 'ev-dibatalkan']);
    }

    #[Test]
    public function sync_memperbarui_event_yang_sudah_ada_alih_alih_duplikat(): void
    {
        $allDay = today()->addDays(3);

        HariLibur::create([
            'tanggal' => $allDay->format('Y-m-d'),
            'nama' => 'Nama Lama',
            'sumber' => HariLibur::SUMBER_GOOGLE_CALENDAR,
            'event_id' => 'ev-lama',
        ]);

        Http::fake([
            'googleapis.com/calendar/v3/*' => Http::response([
                'items' => [
                    ['id' => 'ev-lama', 'status' => 'confirmed', 'summary' => 'Nama Baru', 'start' => ['date' => $allDay->format('Y-m-d')]],
                ],
            ], 200),
        ]);

        $hasil = app(GoogleCalendarSync::class)->sync();

        $this->assertNull($hasil['error']);
        $this->assertSame(0, $hasil['dibuat']);
        $this->assertSame(1, $hasil['diperbarui']);

        $this->assertDatabaseCount('hari_liburs', 1);
        $this->assertDatabaseHas('hari_liburs', [
            'event_id' => 'ev-lama',
            'nama' => 'Nama Baru',
            'sumber' => HariLibur::SUMBER_GOOGLE_CALENDAR,
        ]);
    }

    #[Test]
    public function sync_menghapus_baris_google_stale_dan_mempertahankan_manual(): void
    {
        $tanggal = today()->addDays(3)->format('Y-m-d');

        HariLibur::create(['tanggal' => $tanggal, 'nama' => 'Stale Google', 'sumber' => HariLibur::SUMBER_GOOGLE_CALENDAR, 'event_id' => 'ev-hilang']);
        HariLibur::create(['tanggal' => $tanggal, 'nama' => 'Tetap Manual', 'sumber' => HariLibur::SUMBER_MANUAL, 'event_id' => null]);
        HariLibur::create(['tanggal' => $tanggal, 'nama' => 'Tetap Google', 'sumber' => HariLibur::SUMBER_GOOGLE_CALENDAR, 'event_id' => 'ev-tetap']);

        Http::fake([
            'googleapis.com/calendar/v3/*' => Http::response([
                'items' => [
                    ['id' => 'ev-tetap', 'status' => 'confirmed', 'summary' => 'Tetap Google', 'start' => ['date' => $tanggal]],
                ],
            ], 200),
        ]);

        $hasil = app(GoogleCalendarSync::class)->sync();

        $this->assertNull($hasil['error']);
        $this->assertSame(1, $hasil['dihapus']);
        $this->assertDatabaseCount('hari_liburs', 2);
        $this->assertDatabaseMissing('hari_liburs', ['event_id' => 'ev-hilang']);
        $this->assertDatabaseHas('hari_liburs', ['id' => HariLibur::where('nama', 'Tetap Manual')->first()->id]);
        $this->assertDatabaseHas('hari_liburs', ['event_id' => 'ev-tetap']);
    }

    #[Test]
    public function kalender_kosong_menghapus_semua_baris_google_dan_menyimpan_manual(): void
    {
        HariLibur::create(['tanggal' => today()->format('Y-m-d'), 'nama' => 'Google Lama', 'sumber' => HariLibur::SUMBER_GOOGLE_CALENDAR, 'event_id' => 'ev-lama']);
        HariLibur::create(['tanggal' => today()->format('Y-m-d'), 'nama' => 'Manual', 'sumber' => HariLibur::SUMBER_MANUAL, 'event_id' => null]);

        Http::fake([
            'googleapis.com/calendar/v3/*' => Http::response(['items' => []], 200),
        ]);

        $hasil = app(GoogleCalendarSync::class)->sync();

        $this->assertNull($hasil['error']);
        $this->assertSame(1, $hasil['dihapus']);
        $this->assertDatabaseCount('hari_liburs', 1);
        $this->assertDatabaseHas('hari_liburs', ['nama' => 'Manual']);
    }

    #[Test]
    public function sync_mengikuti_paginasi_next_page_token(): void
    {
        $tanggal = today()->format('Y-m-d');

        Http::fake([
            'googleapis.com/calendar/v3/*' => Http::sequence()
                ->push(['items' => [
                    ['id' => 'ev-hal-1', 'status' => 'confirmed', 'summary' => 'Halaman Satu', 'start' => ['date' => $tanggal]],
                ], 'nextPageToken' => 'lanjut'], 200)
                ->push(['items' => [
                    ['id' => 'ev-hal-2', 'status' => 'confirmed', 'summary' => 'Halaman Dua', 'start' => ['date' => $tanggal]],
                ]], 200),
        ]);

        $hasil = app(GoogleCalendarSync::class)->sync();

        $this->assertNull($hasil['error']);
        $this->assertSame(2, $hasil['jumlah']);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'calendars/id.indonesia%23holiday%40group.v.calendar.google.com/events');
        });
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'pageToken=lanjut') || str_contains($request->url(), 'pageToken%5D=lanjut'));
    }

    #[Test]
    public function api_key_kosong_mengembalikan_error_tanpa_request(): void
    {
        config(['google-calendar.api_key' => null]);

        Http::fake();

        $hasil = app(GoogleCalendarSync::class)->sync();

        $this->assertNotNull($hasil['error']);
        Http::assertNothingSent();
    }

    #[Test]
    public function calendar_id_kosong_mengembalikan_error_tanpa_request(): void
    {
        config(['google-calendar.calendar_id' => null]);

        Http::fake();

        $hasil = app(GoogleCalendarSync::class)->sync();

        $this->assertNotNull($hasil['error']);
        Http::assertNothingSent();
    }

    #[Test]
    public function respons_error_dari_google_dikembalikan_ke_hasil(): void
    {
        Http::fake([
            'googleapis.com/calendar/v3/*' => Http::response([
                'error' => ['message' => 'API key not valid'],
            ], 403),
        ]);

        $hasil = app(GoogleCalendarSync::class)->sync();

        $this->assertSame('API key not valid', $hasil['error']);
    }
}
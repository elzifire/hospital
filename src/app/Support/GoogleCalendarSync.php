<?php

namespace App\Support;

use App\Models\HariLibur;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sinkronisasi hari libur dari satu kalender Google publik (read-only).
 *
 * Mengambil event ke depan (timeMin = sekarang, timeMax = sekarang +
 * lookahead_months) lewat endpoint Calendar API v3 events.list memakai
 * API key. Setiap event dipetakan ke satu baris hari_liburs dan
 * diidentifikasi lewat kolom unik `event_id`:
 *  - event baru        -> dibuat (sumber = google_calendar)
 *  - event yang sudah  -> diperbarui (nama/tanggal mengikuti kalender)
 *  - baris google yang tidak lagi muncul di rentang -> dihapus (stale)
 * Baris dengan sumber "manual" tidak pernah disentuh.
 */
class GoogleCalendarSync
{
    private const MAKS_RESULT = 2500;

    /**
     * @return array{dibuat: int, diperbarui: int, dihapus: int, jumlah: int, error: ?string}
     */
    public function sync(int $bulanKeDepan = 0): array
    {
        $config = config('google-calendar');
        $base = rtrim((string) ($config['base_url'] ?? 'https://www.googleapis.com/calendar/v3'), '/');
        $apiKey = (string) ($config['api_key'] ?? '');
        $calendarId = (string) ($config['calendar_id'] ?? '');
        $bulanKeDepan = $bulanKeDepan > 0 ? $bulanKeDepan : (int) ($config['lookahead_months'] ?? 12);

        if ($apiKey === '') {
            return $this->gagal('GOOGLE_CALENDAR_API_KEY belum dikonfigurasi.');
        }

        if ($calendarId === '') {
            return $this->gagal('GOOGLE_CALENDAR_ID belum dikonfigurasi.');
        }

        $client = Http::acceptJson()->timeout((int) ($config['timeout'] ?? 15));

        $dibuat = 0;
        $diperbarui = 0;
        $dihapus = 0;
        $jumlah = 0;

        $timeMin = now();
        $timeMax = $timeMin->copy()->addMonths($bulanKeDepan);
        $pageToken = null;

        $terpakai = [];

        do {
            $respon = $client->get(
                "{$base}/calendars/".rawurlencode($calendarId).'/events',
                array_filter([
                    'key' => $apiKey,
                    'timeMin' => $timeMin->toRfc3339String(),
                    'timeMax' => $timeMax->toRfc3339String(),
                    'singleEvents' => 'true',
                    'orderBy' => 'startTime',
                    'maxResults' => self::MAKS_RESULT,
                    'pageToken' => $pageToken,
                ], fn ($v) => $v !== null && $v !== ''),
            );

            if ($respon->failed()) {
                $pesan = (string) ($respon->json('error.message') ?: $respon->reason());

                Log::warning('GoogleCalendarSync: request events gagal (HTTP '.$respon->status().').', [
                    'calendar_id' => $calendarId,
                    'error' => $pesan,
                ]);

                return $this->gagal($pesan === '' ? 'Gagal menghubungi Google Calendar (HTTP '.$respon->status().').' : $pesan, $dibuat, $diperbarui, $dihapus);
            }

            $itemHalaman = $respon->json('items', []);

            foreach ($itemHalaman as $item) {
                $item = (array) $item;

                if (strtolower((string) ($item['status'] ?? '')) !== 'confirmed') {
                    continue;
                }

                $eventId = (string) ($item['id'] ?? '');
                $tanggal = $this->tanggalEvent((array) ($item['start'] ?? []));

                if ($eventId === '' || $tanggal === null) {
                    continue;
                }

                $hasil = $this->simpan($eventId, $tanggal, (string) ($item['summary'] ?? ''));

                $dibuat += $hasil === 'created' ? 1 : 0;
                $diperbarui += $hasil === 'updated' ? 1 : 0;
                $jumlah++;

                $terpakai[] = $eventId;
            }

            $pageToken = $respon->json('nextPageToken');
        } while ($pageToken !== null && $pageToken !== '');

        // Hapus baris hasil sinkron yang tidak lagi muncul di rentang
        // (event dihapus/dipindah dari kalender). Baris manual aman.
        $dihapus = HariLibur::where('sumber', HariLibur::SUMBER_GOOGLE_CALENDAR)
            ->when($terpakai !== [], fn ($q) => $q->whereNotIn('event_id', $terpakai))
            ->delete();

        Log::info('GoogleCalendarSync: sinkronisasi selesai.', [
            'calendar_id' => $calendarId,
            'jumlah' => $jumlah,
            'dibuat' => $dibuat,
            'diperbarui' => $diperbarui,
            'dihapus' => $dihapus,
        ]);

        return ['dibuat' => $dibuat, 'diperbarui' => $diperbarui, 'dihapus' => $dihapus, 'jumlah' => $jumlah, 'error' => null];
    }

    /**
     * Insert/update satu event kalender ke hari_liburs.
     *
     * @return string 'created' | 'updated'
     */
    protected function simpan(string $eventId, string $tanggal, string $nama): string
    {
        $data = [
            'tanggal' => $tanggal,
            'nama' => $nama === '' ? 'Hari libur' : $nama,
            'sumber' => HariLibur::SUMBER_GOOGLE_CALENDAR,
        ];

        $ada = HariLibur::where('event_id', $eventId)->first();

        if ($ada !== null) {
            $ada->update($data + ['event_id' => $eventId]);

            return 'updated';
        }

        HariLibur::create($data + ['event_id' => $eventId]);

        return 'created';
    }

    /**
     * Tanggal (Y-m-d) dari blok start event: utamakan tanggal all-day
     * (start.date), lalu bagian tanggal dari tanggal+jam (start.dateTime).
     */
    protected function tanggalEvent(array $start): ?string
    {
        if (filled($start['date'] ?? null)) {
            return Carbon::parse((string) $start['date'])->format('Y-m-d');
        }

        if (filled($start['dateTime'] ?? null)) {
            return Carbon::parse((string) $start['dateTime'])
                ->setTimezone(config('app.timezone'))
                ->format('Y-m-d');
        }

        return null;
    }

    /**
     * @return array{dibuat: int, diperbarui: int, dihapus: int, jumlah: int, error: ?string}
     */
    protected function gagal(string $pesan, int $dibuat = 0, int $diperbarui = 0, int $dihapus = 0): array
    {
        return [
            'dibuat' => $dibuat,
            'diperbarui' => $diperbarui,
            'dihapus' => $dihapus,
            'jumlah' => $dibuat + $diperbarui,
            'error' => $pesan,
        ];
    }
}
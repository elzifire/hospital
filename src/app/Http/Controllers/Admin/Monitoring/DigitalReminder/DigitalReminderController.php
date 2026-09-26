<?php

namespace App\Http\Controllers\Admin\Monitoring\DigitalReminder;

use App\Http\Controllers\Admin\Monitoring\ReportController;
use App\Models\Poli;
use App\Models\Reminder;
use App\Support\MonitoringIcons;
use App\Support\MonitoringSpecs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DigitalReminderController extends ReportController
{
    /**
     * Data grafik tab "Grafik" — sejalan dengan filter aktif di layar
     * (rentang tanggal, poli, status, jenis) dan scope akun poli.
     *
     * @param  array<string, mixed>  $config
     * @return array<int, array<string, mixed>>
     */
    protected function chartData(Request $request, array $config): array
    {
        $base = $this->reportFilteredQuery($config, $request);

        $trend = $this->chartTrend($base, 'tanggal', [
            ['name' => 'Kunjungan RS', 'color' => '#0ea5e9', 'q' => fn (Builder $b) => $b->whereRaw('coalesce(home_visit, false) = false')],
            ['name' => 'Home Visit', 'color' => '#8b5cf6', 'q' => fn (Builder $b) => $b->where('home_visit', true)],
        ]);

        $status = $this->chartDonut($base, [
            ['name' => 'Terjadwal', 'color' => '#0ea5e9', 'q' => fn (Builder $b) => $b->where('status', 'terjadwal')],
            ['name' => 'Selesai', 'color' => '#10b981', 'q' => fn (Builder $b) => $b->where('status', 'selesai')],
            ['name' => 'Tidak Datang', 'color' => '#f43f5e', 'q' => fn (Builder $b) => $b->where('status', 'tidak_datang')],
            ['name' => 'Dibatalkan', 'color' => '#f59e0b', 'q' => fn (Builder $b) => $b->where('status', 'dibatalkan')],
            ['name' => 'Jadwal Ulang', 'color' => '#6366f1', 'q' => fn (Builder $b) => $b->where('status', 'jadwal_ulang')],
        ]);

        $jenis = $this->chartDonut($base, [
            ['name' => 'Kunjungan RS', 'color' => '#0ea5e9', 'q' => fn (Builder $b) => $b->whereRaw('coalesce(home_visit, false) = false')],
            ['name' => 'Home Visit', 'color' => '#8b5cf6', 'q' => fn (Builder $b) => $b->where('home_visit', true)],
        ]);

        $poliLabels = Poli::orderBy('nama')->pluck('nama', 'id')->all();
        $perPoli = $this->chartTop($base, 'poli_id', $poliLabels, 8, 'Lainnya', 'Tanpa Poli');

        return [
            [
                'key' => 'trend',
                'title' => 'Tren Jadwal per Minggu',
                'subtitle' => '8 minggu terakhir · mengikuti filter aktif',
                'type' => 'column',
                'span' => 2,
                'height' => 340,
                'unit' => 'jadwal',
                'categories' => $trend['categories'],
                'series' => $trend['series'],
                'legend' => true,
            ],
            [
                'key' => 'status',
                'title' => 'Status Jadwal',
                'subtitle' => 'Komposisi status penjadwalan digital reminder',
                'type' => 'pie',
                'height' => 330,
                'unit' => 'jadwal',
                'total' => $status['total'],
                'series' => $status['series'],
                'legend' => true,
            ],
            [
                'key' => 'jenis',
                'title' => 'Kunjungan RS vs Home Visit',
                'subtitle' => 'Klasifikasi jenis kunjungan terjadwal',
                'type' => 'pie',
                'height' => 330,
                'unit' => 'jadwal',
                'total' => $jenis['total'],
                'series' => $jenis['series'],
                'legend' => true,
            ],
            [
                'key' => 'per-poli',
                'title' => 'Jadwal per Poli',
                'subtitle' => 'Instalasi dengan penjadwalan terbanyak',
                'type' => 'bar',
                'span' => 2,
                'height' => 340,
                'unit' => 'jadwal',
                'categories' => array_column($perPoli, 'name'),
                'series' => [['name' => 'Jadwal', 'data' => array_column($perPoli, 'y')]],
                'colors' => self::CHART_PALETTE,
                'legend' => false,
            ],
        ];
    }

    public static function meta(): array
    {
        return [
            'label' => 'Digital Reminder',
            'group' => 'broadcasting',
            'permission' => 'manage digital-reminder',
            'description' => 'Laporan penjadwalan kunjungan pasien (digital reminder).',
            'icon' => MonitoringIcons::BELL,
            'tone' => 'sky',
            'available' => true,
            'count' => fn () => Reminder::query()->tap(MonitoringSpecs::poliScope())->count(),
        ];
    }

    public static function spec(): array
    {
        $scopePoli = MonitoringSpecs::poliScope();

        return [
            'model' => Reminder::class,
            'eager' => ['pnpp.satker', 'poli', 'dokter'],
            'withCount' => ['messageLogs'],
            'poliScope' => $scopePoli,
            'searchHint' => 'Cari nama/NIP pasien, poli, dokter, atau catatan...',
            'search' => function (Builder $q, string $t) {
                $tAtas = strtoupper($t);

                return $q->where(fn ($w) => $w
                    ->where('catatan', 'like', "%{$t}%")
                    ->orWhereHas('pnpp', fn ($p) => $p
                        ->whereRaw('UPPER(nama) LIKE ?', ["%{$tAtas}%"])
                        ->orWhere('nip', 'like', "%{$t}%"))
                    ->orWhereHas('poli', fn ($p) => $p->whereRaw('UPPER(nama) LIKE ?', ["%{$tAtas}%"]))
                    ->orWhereHas('dokter', fn ($p) => $p->whereRaw('UPPER(nama) LIKE ?', ["%{$tAtas}%"])));
            },
            'filters' => [
                [
                    'key' => 'status',
                    'label' => 'Semua Status',
                    'type' => 'select',
                    'options' => fn () => array_combine(Reminder::STATUS, array_map('ucfirst', Reminder::STATUS)),
                    'apply' => fn (Builder $q, string $v) => $q->where('status', $v),
                ],
                [
                    'key' => 'poli',
                    'label' => 'Semua Poli',
                    'type' => 'select',
                    'options' => fn () => Poli::query()
                        ->when(auth()->user()?->poliId(), fn ($q, $id) => $q->whereKey($id))
                        ->orderBy('nama')->pluck('nama', 'id')->all(),
                    'apply' => fn (Builder $q, string $v) => $q->where('poli_id', (int) $v),
                ],
                [
                    'key' => 'home_visit',
                    'label' => 'Semua Jenis',
                    'type' => 'select',
                    'options' => fn () => ['1' => 'Home Visit', '0' => 'Kunjungan RS'],
                    'apply' => fn (Builder $q, string $v) => $q->where('home_visit', (bool) $v),
                ],
                [
                    'key' => 'from',
                    'label' => 'Jadwal Dari',
                    'type' => 'date',
                    'apply' => fn (Builder $q, string $v) => $q->whereDate('tanggal', '>=', $v),
                ],
                [
                    'key' => 'to',
                    'label' => 'Jadwal Sampai',
                    'type' => 'date',
                    'apply' => fn (Builder $q, string $v) => $q->whereDate('tanggal', '<=', $v),
                ],
            ],
            'sorts' => [
                'terbaru' => ['label' => 'Jadwal Terbaru', 'apply' => fn (Builder $q) => $q->orderByDesc('tanggal')],
                'terlama' => ['label' => 'Jadwal Terlama', 'apply' => fn (Builder $q) => $q->orderBy('tanggal')],
            ],
            'defaultSort' => 'terbaru',
            'stats' => fn () => [
                ['label' => 'Total Jadwal', 'value' => Reminder::query()->tap($scopePoli)->count(), 'icon' => MonitoringIcons::BELL, 'tone' => 'sky'],
                ['label' => 'Terjadwal', 'value' => Reminder::where('status', 'terjadwal')->tap($scopePoli)->count(), 'icon' => MonitoringIcons::CLOCK, 'tone' => 'violet'],
                ['label' => 'Mendatang', 'value' => Reminder::where('status', 'terjadwal')->whereDate('tanggal', '>=', today())->tap($scopePoli)->count(), 'icon' => MonitoringIcons::CHECK, 'tone' => 'emerald'],
                ['label' => 'Tidak Datang', 'value' => Reminder::where('status', 'tidak_datang')->tap($scopePoli)->count(), 'icon' => MonitoringIcons::WARN, 'tone' => 'rose'],
            ],
            'columns' => [
                ['label' => 'Jadwal', 'type' => 'strong', 'value' => fn ($m) => $m->tanggal?->translatedFormat('d M Y').' · '.$m->jam?->format('H:i')],
                ['label' => 'Pasien', 'type' => 'profile', 'tone' => 'sky', 'value' => fn ($m) => [$m->pnpp?->nama ?? '—', $m->pnpp?->nip]],
                ['label' => 'Poli', 'type' => 'text', 'value' => fn ($m) => $m->poli?->nama],
                ['label' => 'Dokter', 'type' => 'text', 'value' => fn ($m) => $m->dokter?->nama ?? '—'],
                ['label' => 'Home Visit', 'type' => 'badge', 'value' => fn ($m) => [$m->home_visit ? 'Ya' : 'Tidak', $m->home_visit ? 'teal' : 'slate']],
                ['label' => 'Pesan', 'type' => 'stat', 'tone' => 'violet', 'value' => fn ($m) => [(string) $m->message_logs_count, 'pesan']],
                ['label' => 'Status', 'type' => 'badge', 'value' => fn ($m) => [$m->status, ['terjadwal' => 'sky', 'selesai' => 'emerald', 'tidak_datang' => 'rose', 'dibatalkan' => 'amber', 'jadwal_ulang' => 'indigo'][$m->status] ?? 'slate']],
            ],
            'export' => [
                'headers' => ['Tanggal', 'Jam', 'Pasien', 'NIP/NRP', 'Satker', 'Poli', 'Dokter', 'Home Visit', 'Status', 'Catatan'],
                'toRow' => fn ($m) => [
                    $m->tanggal?->format('Y-m-d') ?? '',
                    $m->jam?->format('H:i') ?? '',
                    $m->pnpp?->nama ?? '',
                    $m->pnpp?->nip ?? '',
                    $m->pnpp?->satker?->nama ?? '',
                    $m->poli?->nama ?? '',
                    $m->dokter?->nama ?? '',
                    $m->home_visit ? 'Ya' : 'Tidak',
                    $m->status,
                    $m->catatan ?? '',
                ],
            ],
        ];
    }
}
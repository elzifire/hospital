<?php

namespace App\Http\Controllers\Admin\Monitoring\Kunjungan;

use App\Http\Controllers\Admin\Monitoring\ReportController;
use App\Models\Kunjungan;
use App\Models\Poli;
use App\Models\Satker;
use App\Support\MonitoringIcons;
use App\Support\MonitoringSpecs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class KunjunganController extends ReportController
{
    /**
     * Data grafik tab "Grafik" — sejalan dengan filter aktif di layar
     * (rentang tanggal, poli, satker, jenis, pencarian) dan scope akun poli.
     *
     * @param  array<string, mixed>  $config
     * @return array<int, array<string, mixed>>
     */
    protected function chartData(Request $request, array $config): array
    {
        $base = $this->reportFilteredQuery($config, $request);

        $trend = $this->chartTrend($base, 'tanggal_kunjungan', [
            ['name' => 'Kunjungan RS', 'color' => '#0ea5e9', 'q' => fn (Builder $b) => $b->whereRaw('coalesce(home_visit, false) = false')],
            ['name' => 'Home Visit', 'color' => '#14b8a6', 'q' => fn (Builder $b) => $b->where('home_visit', true)],
        ]);

        $sumber = $this->chartDonut($base, [
            ['name' => 'Realisasi Reminder', 'color' => '#0ea5e9', 'q' => fn (Builder $b) => $b->whereNotNull('reminder_id')],
            ['name' => 'Manual', 'color' => '#f43f5e', 'q' => fn (Builder $b) => $b->whereNull('reminder_id')],
        ]);

        $poliLabels = Poli::orderBy('nama')->pluck('nama', 'id')->all();
        $perPoli = $this->chartTop($base, 'poli_id', $poliLabels, 8, 'Lainnya', 'Tanpa Poli');

        // Per satker (lewat relasi pnpp) — join agar bisa di-group per satker.
        $columnsSatker = (clone $base)->reorder()
            ->leftJoin('pnpps', 'kunjungans.pnpp_id', '=', 'pnpps.id')
            ->leftJoin('satkers', 'pnpps.satker_id', '=', 'satkers.id')
            ->select('satkers.nama')
            ->selectRaw('count(*) as total')
            ->groupBy('satkers.nama')
            ->orderByDesc('total')
            ->limit(9)
            ->get();

        $perSatker = [];
        $elsewhere = 0;
        foreach ($columnsSatker as $i => $row) {
            $value = (int) $row->total;
            if ($i >= 8) {
                $elsewhere += $value;
                continue;
            }
            $perSatker[] = ['name' => $row->nama ?? 'Tanpa Satker', 'y' => $value];
        }
        if ($elsewhere > 0) {
            $perSatker[] = ['name' => 'Lainnya', 'y' => $elsewhere];
        }

        return [
            [
                'key' => 'trend',
                'title' => 'Tren Kunjungan per Minggu',
                'subtitle' => '8 minggu terakhir · mengikuti filter aktif',
                'type' => 'column',
                'span' => 2,
                'height' => 340,
                'unit' => 'kunjungan',
                'categories' => $trend['categories'],
                'series' => $trend['series'],
                'legend' => true,
            ],
            [
                'key' => 'sumber',
                'title' => 'Sumber Kunjungan',
                'subtitle' => 'Realisasi reminder vs pencatatan manual',
                'type' => 'pie',
                'height' => 330,
                'unit' => 'kunjungan',
                'total' => $sumber['total'],
                'series' => $sumber['series'],
                'legend' => true,
            ],
            [
                'key' => 'per-poli',
                'title' => 'Kunjungan per Poli',
                'subtitle' => 'Instalasi dengan kunjungan terbanyak',
                'type' => 'bar',
                'height' => 330,
                'unit' => 'kunjungan',
                'categories' => array_column($perPoli, 'name'),
                'series' => [['name' => 'Kunjungan', 'data' => array_column($perPoli, 'y')]],
                'colors' => self::CHART_PALETTE,
                'legend' => false,
            ],
            [
                'key' => 'per-satker',
                'title' => 'Kunjungan per Satker',
                'subtitle' => 'Wilayah kerja pasien dengan kunjungan terbanyak',
                'type' => 'bar',
                'span' => 2,
                'height' => 340,
                'unit' => 'kunjungan',
                'categories' => array_column($perSatker, 'name'),
                'series' => [['name' => 'Kunjungan', 'data' => array_column($perSatker, 'y')]],
                'colors' => self::CHART_PALETTE,
                'legend' => false,
            ],
        ];
    }
    public static function meta(): array
    {
        return [
            'label' => 'Kunjungan',
            'group' => 'broadcasting',
            'permission' => 'manage kunjungan',
            'description' => 'Riwayat kunjungan PNPP — satu baris = satu kunjungan per poli',
            'icon' => MonitoringIcons::PIN,
            'tone' => 'rose',
            'available' => true,
            'count' => fn () => Kunjungan::query()->tap(MonitoringSpecs::poliScope())->count(),
        ];
    }

    public static function spec(): array
    {
        $scopePoli = MonitoringSpecs::poliScope();

        return [
            'model' => Kunjungan::class,
            'eager' => ['pnpp.satker', 'poli'],
            'withCount' => [],
            'poliScope' => $scopePoli,
            'searchHint' => 'Cari nama/NIP pasien, keluhan, atau diagnosa...',
            'search' => function (Builder $q, string $t) {
                $tAtas = strtoupper($t);

                return $q->where(fn ($w) => $w
                    ->whereHas('pnpp', fn ($p) => $p
                        ->whereRaw('UPPER(nama) LIKE ?', ["%{$tAtas}%"])
                        ->orWhere('nip', 'like', "%{$t}%"))
                    ->orWhere('keluhan', 'like', "%{$t}%")
                    ->orWhere('diagnosa', 'like', "%{$t}%"));
            },
            'filters' => [
                [
                    'key' => 'home',
                    'label' => 'Semua Jenis',
                    'type' => 'select',
                    'options' => fn () => ['1' => 'Home Visit', '0' => 'Kunjungan RS'],
                    'apply' => fn (Builder $q, string $v) => $q->where('home_visit', (bool) $v),
                ],
                [
                    'key' => 'from',
                    'label' => 'Dari Tanggal',
                    'type' => 'date',
                    'apply' => fn (Builder $q, string $v) => $q->where('tanggal_kunjungan', '>=', $v),
                ],
                [
                    'key' => 'to',
                    'label' => 'Sampai Tanggal',
                    'type' => 'date',
                    'apply' => fn (Builder $q, string $v) => $q->where('tanggal_kunjungan', '<=', $v),
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
                    'key' => 'satker',
                    'label' => 'Semua Satker',
                    'type' => 'select',
                    'options' => fn () => Satker::orderBy('nama')->pluck('nama', 'id')->all(),
                    'apply' => fn (Builder $q, string $v) => $q->whereHas('pnpp', fn ($p) => $p->where('satker_id', (int) $v)),
                ],
            ],
            'sorts' => [
                'terbaru' => ['label' => 'Terbaru', 'apply' => fn (Builder $q) => $q->orderByDesc('tanggal_kunjungan')],
                'terlama' => ['label' => 'Terlama', 'apply' => fn (Builder $q) => $q->orderBy('tanggal_kunjungan')],
            ],
            'defaultSort' => 'terbaru',
            'stats' => fn () => [
                ['label' => 'Total Kunjungan', 'value' => Kunjungan::query()->tap($scopePoli)->count(), 'icon' => MonitoringIcons::PIN, 'tone' => 'rose'],
                ['label' => 'Pasien', 'value' => Kunjungan::query()->tap($scopePoli)->distinct()->count('pnpp_id'), 'icon' => MonitoringIcons::USERS, 'tone' => 'violet'],
                ['label' => 'Realisasi Reminder', 'value' => Kunjungan::query()->whereNotNull('reminder_id')->tap($scopePoli)->count(), 'icon' => MonitoringIcons::CHECK, 'tone' => 'teal'],
                ['label' => 'Manual', 'value' => Kunjungan::query()->whereNull('reminder_id')->tap($scopePoli)->count(), 'icon' => MonitoringIcons::TAP, 'tone' => 'amber'],
            ],
            'columns' => [
                ['label' => 'Tanggal', 'type' => 'strong', 'value' => fn ($m) => $m->tanggal_kunjungan?->translatedFormat('d M Y')],
                ['label' => 'Pasien', 'type' => 'profile', 'tone' => 'rose', 'value' => fn ($m) => [$m->pnpp?->nama ?? '—', $m->pnpp?->nip]],
                ['label' => 'Satker', 'type' => 'text', 'value' => fn ($m) => $m->pnpp?->satker?->nama],
                ['label' => 'Poli', 'type' => 'badge', 'tone' => 'sky', 'value' => fn ($m) => $m->poli ? [$m->poli->nama, 'sky'] : null],
                ['label' => 'Jenis', 'type' => 'badge', 'value' => fn ($m) => $m->home_visit ? ['Home Visit', 'teal'] : ['Kunjungan RS', 'sky']],
                ['label' => 'Sumber', 'type' => 'badge', 'value' => fn ($m) => filled($m->reminder_id) ? ['Realisasi Reminder', 'emerald'] : ['Manual', 'teal']],
                ['label' => 'Keluhan', 'type' => 'text', 'value' => fn ($m) => $m->keluhan],
                ['label' => 'Diagnosa', 'type' => 'text', 'value' => fn ($m) => $m->diagnosa],
            ],
            'export' => [
                'headers' => ['Tanggal', 'Pasien', 'NIP/NRP', 'Satker', 'Poli', 'Jenis', 'Sumber', 'Keluhan', 'Diagnosa'],
                'toRow' => fn ($m) => [
                    $m->tanggal_kunjungan?->format('Y-m-d') ?? '',
                    $m->pnpp?->nama ?? '',
                    $m->pnpp?->nip ?? '',
                    $m->pnpp?->satker?->nama ?? '',
                    $m->poli?->nama ?? '',
                    $m->home_visit ? 'Home Visit' : 'Kunjungan RS',
                    filled($m->reminder_id) ? 'Realisasi Reminder' : 'Manual',
                    $m->keluhan ?? '',
                    $m->diagnosa ?? '',
                ],
            ],
        ];
    }
}
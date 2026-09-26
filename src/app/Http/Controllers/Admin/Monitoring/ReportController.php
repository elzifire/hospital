<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Admin\Monitoring\Concerns\BuildsReportQuery;
use App\Http\Controllers\Admin\Monitoring\DigitalReminder\DigitalReminderController;
use App\Http\Controllers\Admin\Monitoring\FollowUp\FollowUpController;
use App\Http\Controllers\Admin\Monitoring\Kunjungan\KunjunganController;
use App\Http\Controllers\Admin\Monitoring\Outreach\OutreachController;
use App\Http\Controllers\Admin\Monitoring\Penyakit\PenyakitController;
use App\Http\Controllers\Admin\Monitoring\PenyakitMenahun\PenyakitMenahunController;
use App\Http\Controllers\Admin\Monitoring\Pnpp\PnppController;
use App\Http\Controllers\Admin\Monitoring\Poli\PoliController;
use App\Http\Controllers\Admin\Monitoring\Respon\ResponController;
use App\Http\Controllers\Admin\Monitoring\Satker\SatkerController;
use App\Http\Controllers\Controller;
use App\Support\SheetHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Basis bersama seluruh fitur laporan modul Monitoring.
 *
 * Setiap fitur laporan punya 1 controller turunan yang memegang:
 *  - meta()  : kartu hub (label, grup, permission, deskripsi, ikon, tone, count)
 *  - spec()  : perilaku laporannya (query, search, filter, sort, statistik,
 *              kolom, dan export) — sepenuhnya milik fitur tersebut agar
 *              tiap laporan bisa berkembang berbeda-beda.
 *
 * Halaman (index) dan unduhan (export) berbagi alur generik di sini:
 * pagination sisi server, filter aktif, dan export xlsx/csv via SheetHelper.
 */
abstract class ReportController extends Controller
{
    use BuildsReportQuery;

    /**
     * Palet warna grafik (hex) untuk antar-kategori yang tidak punya warna
     * khusus — dipakai grafik batang/pai per-poli, per-satker, dsb.
     */
    public const CHART_PALETTE = [
        '#0ea5e9', '#8b5cf6', '#f43f5e', '#10b981', '#f59e0b',
        '#6366f1', '#14b8a6', '#ef4444', '#84cc16', '#f97316',
    ];

    /**
     * Registri fitur laporan: key rute/slug → class controller.
     * Dipakai hub Monitoring dan test — satu-satunya daftar terpusat.
     *
     * @return array<string, class-string<static>>
     */
    public static function features(): array
    {
        return [
            'pnpp' => PnppController::class,
            'satker' => SatkerController::class,
            'penyakit' => PenyakitController::class,
            'penyakit-menahun' => PenyakitMenahunController::class,
            'poli' => PoliController::class,
            'kunjungan' => KunjunganController::class,
            'digital-reminder' => DigitalReminderController::class,
            'outreach' => OutreachController::class,
            'respon' => ResponController::class,
            'follow-up' => FollowUpController::class,
        ];
    }

    /**
     * Metadata kartu di hub Monitoring & hak akses fitur.
     *
     * @return array{label: string, group: string, permission?: string, description: string, icon: string, tone: string, available?: bool, count: callable}
     */
    abstract public static function meta(): array;

    /**
     * Spec laporan fitur (model, eager, search, filters, sorts, stats,
     * columns, export, dsb.). View khusus bisa diset lewat key 'view'.
     *
     * @return array<string, mixed>
     */
    abstract public static function spec(): array;

    /**
     * Slug rute & folder view fitur, diturunkan dari nama class.
     */
    public static function slug(): string
    {
        return Str::kebab(Str::beforeLast(class_basename(static::class), 'Controller'));
    }

    /**
     * Halaman detail laporan fitur: read-only + pagination sisi server +
     * pencarian + filter + sorting, sesuai spec milik fitur ini.
     */
    public function index(Request $request)
    {
        // Spec perilaku laporan + metadata kartu (label/deskripsi/ikon) —
        // view bersama memakai keduanya.
        $config = array_merge(static::spec(), static::meta());

        $rows = $this->reportQuery($config, $request)
            ->paginate($this->reportPerPage($request))
            ->withQueryString();

        $stats = empty($config['stats']) ? [] : ($config['stats'])();

        $chartData = $this->chartData($request, $config);

        return view($config['view'] ?? 'admin.monitoring.'.static::slug().'.index', [
            'entity' => static::slug(),
            'config' => $config,
            'rows' => $rows,
            'stats' => $stats,
            'filterOptions' => $this->reportFilterOptions($config),
            'chartData' => $chartData,
            'chartHasData' => $this->chartTotal($chartData) > 0,
        ]);
    }

    /**
     * Unduh laporan sebagai Excel (.xlsx) atau CSV.
     * Export mengikuti pencarian & filter yang sedang aktif di halaman.
     */
    public function export(Request $request)
    {
        $spec = static::spec();

        $format = $request->query('format') === 'csv' ? 'csv' : 'xlsx';

        $rows = $this->reportQuery($spec, $request)
            ->get()
            ->map($spec['export']['toRow'])
            ->all();

        $content = SheetHelper::content($rows, $spec['export']['headers'], $format);
        $filename = 'laporan_'.static::slug().'_'.date('Ymd_His');

        return $format === 'xlsx'
            ? response($content, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.xlsx"',
            ])
            : response($content, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
            ]);
    }

    /**
     * Data grafik (tab "Grafik") — default kosong. Fitur dengan dukungan
     * grafik menimpa metode ini dan mengembalikan daftar spec grafik yang
     * dirender oleh partial `admin.monitoring.partials._charts`.
     *
     * Setiap spec mendukung key:
     *  - 'key'       : id unik (jadi id DOM `monChart-{key}`)
     *  - 'title'     : judul kartu grafik
     *  - 'subtitle'  : sub-judul (opsional)
     *  - 'type'      : 'column' | 'bar' | 'pie'
     *  - 'span'      : 1 (setengah) / 2 (penuh) di grid
     *  - 'height'    : tinggi grafik (px, default 320)
     *  - 'unit'      : satuan untuk sumbu/tooltip (mis. "kunjungan")
     *  - 'categories': label sumbu-X (chart kolom/batang)
     *  - 'series'    : [['name','y'|'data','color'], ...]
     *  - 'colors'    : palet warna per titik (opsional)
     *  - 'total'     : angka tengah donat (opsional, type pie)
     *  - 'legend'    : tampilkan legend (default mengikuti jumlah seri)
     *
     * @param  array<string, mixed>  $config
     * @return array<int, array<string, mixed>>
     */
    protected function chartData(Request $request, array $config): array
    {
        return [];
    }

    /**
     * Jumlah total seluruh nilai pada daftar grafik (untuk deteksi kosong).
     *
     * @param  array<int, array<string, mixed>>  $chartData
     */
    protected function chartTotal(array $chartData): int
    {
        $total = 0;

        foreach ($chartData as $chart) {
            foreach ($chart['series'] ?? [] as $s) {
                if (isset($s['data'])) {
                    $total += array_sum($s['data']);
                } else {
                    $total += (int) ($s['y'] ?? 0);
                }
            }
        }

        return $total;
    }

    /**
     * Tren N minggu terakhir (kolom) dari query yang sudah ter-filter.
     * Setiap seri punya nilai boolean pemisah (opsi 'q' berupa closure).
     *
     * @param  Builder  $query
     * @param  array<int, array{name: string, color: string, q: \Closure}>  $series
     * @return array{categories: string[], series: array<int, array{name: string, color: string, data: int[]}>}
     */
    protected function chartTrend(Builder $query, string $dateColumn, array $series, int $weeks = 8): array
    {
        $start = Carbon::now()->startOfWeek()->subWeeks(max(1, $weeks) - 1);
        $endEx = Carbon::now()->addWeek()->startOfWeek();

        $buckets = [];
        for ($i = 0; $i < $weeks; $i++) {
            $date = $start->copy()->addWeeks($i);
            $buckets[$date->format('Y-m-d')] = $date->translatedFormat('d M');
        }

        $prepared = [];
        foreach ($series as $s) {
            $rows = (clone $query)
                ->reorder()
                ->where($dateColumn, '>=', $start)
                ->where($dateColumn, '<', $endEx)
                ->where(fn (Builder $b) => ($s['q'])($b))
                ->selectRaw("to_char(date_trunc('week', {$dateColumn}), 'YYYY-MM-DD') as wk, count(*) as total")
                ->groupBy('wk')
                ->get();

            $keys = array_keys($buckets);
            $data = array_fill(0, $weeks, 0);

            foreach ($rows as $row) {
                $idx = array_search($row->wk, $keys, true);
                if ($idx !== false) {
                    $data[$idx] = (int) $row->total;
                }
            }

            $prepared[] = ['name' => $s['name'], 'color' => $s['color'], 'data' => $data];
        }

        return ['categories' => array_values($buckets), 'series' => $prepared];
    }

    /**
     * Donut / pie sederhana dari query ter-filter: hitung per grup.
     *
     * @param  Builder  $query
     * @param  array<int, array{name: string, color: string, q: \Closure}>  $groups
     * @return array{total: int, series: array<int, array{name: string, y: int, color: string}>}
     */
    protected function chartDonut(Builder $query, array $groups): array
    {
        $series = [];
        $total = 0;

        foreach ($groups as $g) {
            $count = (int) (clone $query)->where(fn (Builder $b) => ($g['q'])($b))->count();
            $total += $count;
            $series[] = ['name' => $g['name'], 'y' => $count, 'color' => $g['color']];
        }

        return ['total' => $total, 'series' => $series];
    }

    /**
     * Top N berdasarkan kolom lokal (mis. poli_id) lengkap dengan label,
     * sisa yang tidak masuk diagregasi menjadi satu batang "Lainnya".
     *
     * @param  Builder  $query
     * @param  array<int|string, string>  $labels   peta id → nama
     * @return array<int, array{name: string, y: int}>
     */
    protected function chartTop(Builder $query, string $column, array $labels, int $limit = 8, string $others = 'Lainnya', string $fallback = 'Tanpa Kategori'): array
    {
        $rows = (clone $query)
            ->reorder()
            ->select($column)
            ->selectRaw('count(*) as total')
            ->groupBy($column)
            ->orderByDesc('total')
            ->limit(max(1, $limit) + 1)
            ->get();

        $items = [];
        $elsewhere = 0;

        foreach ($rows as $i => $row) {
            $value = (int) $row->total;
            if ($i >= $limit) {
                $elsewhere += $value;
                continue;
            }
            $items[] = ['name' => $labels[$row->{$column}] ?? $fallback, 'y' => $value];
        }

        if ($elsewhere > 0) {
            $items[] = ['name' => $others, 'y' => $elsewhere];
        }

        return $items;
    }
}
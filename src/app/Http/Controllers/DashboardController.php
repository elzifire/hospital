<?php

namespace App\Http\Controllers;

use App\Models\Kunjungan;
use App\Models\MessageLog;
use App\Models\MessageReply;
use App\Models\Poli;
use App\Models\Pnpp;
use App\Models\Reminder;
use App\Models\ResponManual;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Bulan pendek bahasa Indonesia untuk judul grafik trend.
     */
    protected const BULAN = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    /**
     * Tampilkan halaman dashboard — seluruh angka dihitung dari database.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $targetPnpp = (int) config('dashboard.target_pnpp', 1025);
        $targetMon = (array) config('dashboard.monitoring', []);

        // ===================== PONDASI ANGKA =====================
        $totalPnpp = Pnpp::count();
        $kunjungan = Kunjungan::count();
        $respon = ResponManual::count() + MessageReply::count();

        $outreachTerkirim = MessageLog::jenis('outreach')->status('terkirim');
        $followupTerkirim = MessageLog::jenis('follow_up')->status('terkirim');

        $totalOutreach = (clone $outreachTerkirim)->count();
        $pnppDiOutreach = (clone $outreachTerkirim)->whereNotNull('pnpp_id')->distinct()->count('pnpp_id');

        $totalFollowupTerkirim = (clone $followupTerkirim)->count();

        // ===================== STATISTIK KARTU ATAS =====================
        $stats = [
            ['label' => 'PNPP DALAM DATABASE',  'value' => $totalPnpp,
             'note' => $this->persen($totalPnpp / max(1, $targetPnpp) * 100).' dari target data',
             'color' => 'blue', 'icon' => 'users'],
            ['label' => 'TARGET PNPP',          'value' => number_format($targetPnpp, 0, ',', '.'),
             'note' => 'Target data PNPP', 'color' => 'gray', 'icon' => 'target'],
            ['label' => 'DIGITAL REMINDER',     'value' => $totalFollowupTerkirim,
             'note' => $this->persen($totalFollowupTerkirim / max(1, $totalPnpp) * 100).' dari target',
             'color' => 'yellow', 'icon' => 'bell'],
            ['label' => 'PNPP DI-OUTREACH',     'value' => $pnppDiOutreach,
             'note' => $this->persen($pnppDiOutreach / max(1, $targetPnpp) * 100).' dari target',
             'color' => 'green', 'icon' => 'send'],
            ['label' => 'RESPON PNPP',          'value' => $respon,
             'note' => $this->persen($respon / max(1, $totalOutreach) * 100).' dari outreach',
             'color' => 'orange', 'icon' => 'chat'],
            ['label' => 'FOLLOW-UP',            'value' => $totalFollowupTerkirim,
             'note' => $this->persen($totalFollowupTerkirim / max(1, $respon) * 100).' dari respons',
             'color' => 'cyan', 'icon' => 'refresh'],
            ['label' => 'KUNJUNGAN PNPP',       'value' => $kunjungan,
             'note' => 'dari baseline '.$this->persen($kunjungan / max(1, $totalPnpp) * 100),
             'color' => 'purple', 'icon' => 'hospital'],
            ['label' => 'CONVERSION RATE',      'value' => $this->persen($kunjungan / max(1, $totalOutreach) * 100),
             'note' => 'Dari outreach ke kunjungan',
             'color' => 'red', 'icon' => 'trending'],
        ];

        // ===================== TREND KUNJUNGAN 6 BULAN =====================
        $trend = $this->trendKunjungan();

        // ===================== OUTREACH PER SATKER =====================
        $outreach = $this->outreachPerSatker();

        // ===================== STATUS FOLLOW-UP (DONUT) =====================
        $followup = $this->statusFollowUp();

        // ===================== FOLLOW-UP HARI INI =====================
        $followupToday = [
            'count' => Reminder::query()->status('terjadwal')
                ->whereDate('tanggal', today()->toDateString())->count(),
            'note' => 'PNPP perlu ditindaklanjuti',
        ];

        // ===================== AKTIVITAS TERKINI =====================
        $activities = $this->aktivitasTerkini();

        // ===================== MONITORING TARGET 60 HARI =====================
        $monitoring = $this->monitoring($totalPnpp, $pnppDiOutreach, $respon, $totalOutreach,
            $totalFollowupTerkirim, $kunjungan, $targetMon);

        // ===================== KUNJUNGAN PNPP HARI INI =====================
        $kunjunganToday = $this->kunjunganHariIni();

        // ===================== ALERT & NOTIFIKASI =====================
        $alerts = [
            ['title' => 'Follow-up Terlambat',
             'count' => Reminder::query()->terlambatTanpaKunjungan()->count(), 'color' => 'red'],
            ['title' => 'Data Tidak Lengkap',
             'count' => Pnpp::query()->whereNull('no_hp')->orWhereNull('tanggal_lahir')->count(), 'color' => 'yellow'],
            ['title' => 'Reminder Gagal Terkirim',
             'count' => MessageLog::query()->status('gagal')->count(), 'color' => 'orange'],
        ];

        return view('dashboard', [
            'user' => $user,
            'role' => $user->getRoleNames()->first() ?? 'No Role',
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'stats' => $stats,
            'trend' => $trend,
            'outreach' => $outreach,
            'followup' => $followup,
            'followupToday' => $followupToday,
            'activities' => $activities,
            'monitoring' => $monitoring,
            'kunjunganToday' => $kunjunganToday,
            'alerts' => $alerts,
        ]);
    }

    /**
     * ⚠️ DATA HARDCODE — BUKAN ANGKA ASLI DARI DATABASE ⚠️
     *
     * Data kunjungan Jan–Jun 2026 per kategori (IGD / Rawat Jalan / Rawat
     * Inap) untuk mengisi grafik tren kunjungan. Angka di bawah ini adalah
     * DATA CONTOH, bukan hasil hitungan tabel `kunjungans`.
     *
     * Kenapa perlu diganti: grafik ini menampilkan angka operasional RS.
     * Selagi data contoh masih terpasang, angka di dashboard tidak boleh
     * dipakai untuk laporan, keputusan, atau Indikator KPI.
     *
     * Cara menggantinya (pilih salah satu):
     * 1. REAL: kosongkan konstanta ini `[]` → grafik otomatis kembali
     *    100% dari query `kunjungans` (tidak ada kode lain yang diubah).
     * 2. REAL: isi ulang dengan angka asli dari database per bulan.
     *
     * Ingat: hanya bulan ber-key '2026-01' s/d '2026-06' yang terpengaruh;
     * bulan lain tetap dihitung dari database.
     *
     * Key = bulan (Y-m), isi = jumlah kunjungan per kategori sesuai urutan
     * $seriesAsal: [IGD, Rawat Jalan, Rawat Inap].
     */
    protected const TREND_HARDCODE = [
        '2026-01' => [0, 70, 0],
        '2026-02' => [0, 88, 0],
        '2026-03' => [0, 87, 0],
        '2026-04' => [0, 122, 0],
        '2026-05' => [0, 93, 0],
        '2026-06' => [0, 49, 0],
    ];

    /**
     * Tren kunjungan 1 tahun (Januari sampai bulan berjalan) per kategori (IGD / Rawat Jalan / Rawat Inap).
     *
     * Angka bulan Jan–Jun 2026 memakai data hardcode (TREND_HARDCODE);
     * bulan lain dihitung dari tabel kunjungan. Kategori dipetakan dari
     * poli tempat kunjungan tercatat; kunjungan tanpa poli diperlakukan
     * sebagai Rawat Jalan (default).
     *
     * @return array{year: int, months: string[], series: array<int, array{name: string, color: string, data: int[]}>}
     */
    protected function trendKunjungan(): array
    {
        $currentYear = (int) now()->format('Y');
        $currentMonth = (int) now()->format('n');
        $bulan = [];
        $mulai = Carbon::createFromDate($currentYear, 1, 1)->startOfDay()->toDateString();

        for ($m = 1; $m <= $currentMonth; $m++) {
            $date = Carbon::createFromDate($currentYear, $m, 1)->startOfMonth();
            $bulan[$date->format('Y-m')] = $date;
        }

        $seriesAsal = [
            'IGD' => ['label' => 'IGD', 'color' => '#ef4444'],
            'Rawat Jalan' => ['label' => 'Rawat Jalan', 'color' => '#3b82f6'],
            'Rawat Inap' => ['label' => 'Rawat Inap', 'color' => '#16a34a'],
        ];
        $totalMonths = count($bulan);
        $series = array_map(fn ($s) => ['name' => $s['label'], 'color' => $s['color'], 'data' => array_fill(0, $totalMonths, 0)], $seriesAsal);

        $kunjungans = Kunjungan::query()
            ->with('poli:id,kode,nama')
            ->whereDate('tanggal_kunjungan', '>=', $mulai)
            ->whereDate('tanggal_kunjungan', '<=', now()->endOfMonth()->toDateString())
            ->get(['poli_id', 'tanggal_kunjungan']);

        foreach ($kunjungans as $k) {
            $key = $k->tanggal_kunjungan->format('Y-m');
            if (! isset($bulan[$key])) {
                continue;
            }
            $idx = array_search($key, array_keys($bulan), true);
            $series[$this->kategoriPoli($k->poli)]['data'][$idx]++;
        }

        // Terapkan angka hardcode (Jan–Jun 2026) pada posisi masing-masing;
        // bulan di luar rentang itu tetap memakai hasil hitungan database.
        foreach (self::TREND_HARDCODE as $key => $perKategori) {
            $idx = array_search($key, array_keys($bulan), true);
            if ($idx === false) {
                continue;
            }
            foreach (array_keys($seriesAsal) as $i => $kategori) {
                $series[$kategori]['data'][$idx] = $perKategori[$i];
            }
        }

        return [
            'year' => $currentYear,
            'months' => array_map(fn (Carbon $d) => self::BULAN[(int) $d->format('n')], array_values($bulan)),
            'series' => array_values($series),
        ];
    }

    /**
     * Outreach per satker — pesan outreach terkirim dikelompokkan per satker PNPP.
     *
     * @return array<int, array{name: string, value: int, percent: string, color: string}>
     */
    protected function outreachPerSatker(): array
    {
        $colors = ['navy', 'green', 'orange', 'yellow', 'cyan', 'pink'];

        $logs = MessageLog::query()->jenis('outreach')->status('terkirim')
            ->with('pnpp.satker:id,nama')
            ->get(['pnpp_id']);

        $perSatker = [];
        foreach ($logs as $log) {
            $nama = $log->pnpp?->satker?->nama ?? 'Tanpa Satker';
            $perSatker[$nama] = ($perSatker[$nama] ?? 0) + 1;
        }
        arsort($perSatker);

        $total = (int) array_sum($perSatker);
        $outreach = [];
        $i = 0;
        foreach (array_slice($perSatker, 0, 6, true) as $nama => $jumlah) {
            $outreach[] = [
                'name' => $nama,
                'value' => $jumlah,
                'percent' => $this->persen($jumlah / max(1, $total) * 100),
                'color' => $colors[$i % count($colors)],
            ];
            $i++;
        }

        return $outreach;
    }

    /**
     * Status follow-up menjadi donut Selesai / Proses / Terlambat.
     *
     * @return array{total: int, series: array<int, array{name: string, value: int, percent: string, color: string}>}
     */
    protected function statusFollowUp(): array
    {
        $base = MessageLog::query()->jenis('follow_up');
        $selesai = (clone $base)->status('terkirim')->count();
        $proses = (clone $base)->whereIn('status', ['menunggu', 'mengirim'])->count();
        $terlambat = (clone $base)->status('gagal')->count();
        $total = $selesai + $proses + $terlambat;

        $seri = fn (string $nama, int $nilai, string $warna) => [
            'name' => $nama,
            'value' => $nilai,
            'percent' => $this->persen($nilai / max(1, $total) * 100),
            'color' => $warna,
        ];

        return [
            'total' => $total,
            'series' => [
                $seri('Selesai', $selesai, '#22c55e'),
                $seri('Proses', $proses, '#f59e0b'),
                $seri('Terlambat', $terlambat, '#ef4444'),
            ],
        ];
    }

    /**
     * Lima aktivitas terakhir dari outbox pesan (terkirim/gagal/antrean).
     *
     * @return array<int, array{title: string, name: string, satker: string, time: string, color: string, icon: string}>
     */
    protected function aktivitasTerkini(): array
    {
        $warna = [
            'terkirim' => 'green',
            'gagal' => 'red',
            'menunggu' => 'yellow',
            'mengirim' => 'cyan',
            'dibatalkan' => 'gray',
        ];

        $logs = MessageLog::query()
            ->with('pnpp.satker:id,nama')
            ->latest('id')
            ->limit(5)
            ->get(['id', 'jenis', 'status', 'pnpp_id', 'penerima_nama', 'sent_at', 'created_at']);

        return $logs->map(function (MessageLog $log) use ($warna) {
            $jenis = $log->jenis === 'outreach' ? 'Outreach ke PNPP' : 'Follow-up PNPP';
            $waktu = $log->sent_at?->format('H:i') ?? $log->created_at->format('H:i');

            return [
                'title' => $jenis,
                'name' => $log->pnpp?->nama ?? $log->penerima_nama,
                'satker' => $log->pnpp?->satker?->nama ?? 'Tanpa Satker',
                'time' => $waktu,
                'color' => $warna[$log->status] ?? 'gray',
                'icon' => $log->jenis === 'outreach' ? 'phone' : 'chat',
            ];
        })->all();
    }

    /**
     * Monitoring target 60 hari — capaian dihitung dari data aktual.
     *
     * @return array<int, array{name: string, target: string, kunjungan: string, capaian: string, status: string}>
     */
    protected function monitoring(int $totalPnpp, int $pnppDiOutreach, int $respon, int $totalOutreach,
        int $followupTerkirim, int $kunjungan, array $targetMon): array
    {
        $followupSemua = MessageLog::query()->jenis('follow_up')
            ->whereIn('status', ['terkirim', 'gagal'])->count();

        $indikator = [
            ['name' => 'PNPP di-outreach',
             'aktual' => $pnppDiOutreach / max(1, $totalPnpp) * 100,
             'target' => $targetMon['outreach'] ?? 65],
            ['name' => 'Respon ke PNPP',
             'aktual' => $respon / max(1, $totalOutreach) * 100,
             'target' => $targetMon['respon'] ?? 53],
            ['name' => 'Digital Reminder',
             'aktual' => $followupTerkirim / max(1, $totalPnpp) * 100,
             'target' => $targetMon['digital_reminder'] ?? 52],
            ['name' => 'Follow-up',
             'aktual' => $followupTerkirim / max(1, $followupSemua) * 100,
             'target' => $targetMon['followup'] ?? 92],
            ['name' => 'Kunjungan PNPP',
             'aktual' => $kunjungan / max(1, $totalPnpp) * 100,
             'target' => $targetMon['kunjungan'] ?? 15],
        ];

        return array_map(function ($row) {
            $tercapai = $row['aktual'] >= $row['target'];

            return [
                'name' => $row['name'],
                'target' => '≥ '.$row['target'].'%',
                'kunjungan' => $this->persen($row['aktual']),
                'capaian' => '',
                'status' => $tercapai ? 'On Track' : 'Perlu Perhatian',
            ];
        }, $indikator);
    }

    /**
     * Kunjungan hari ini dikelompokkan per kategori poli.
     *
     * @return array{total: int, items: array<int, array{label: string, value: int, color: string}>}
     */
    protected function kunjunganHariIni(): array
    {
        $items = [
            'IGD' => ['label' => 'IGD', 'value' => 0, 'color' => 'blue'],
            'Rawat Jalan' => ['label' => 'Rawat Jalan', 'value' => 0, 'color' => 'cyan'],
            'Rawat Inap' => ['label' => 'Rawat Inap', 'value' => 0, 'color' => 'gray'],
        ];

        $kunjungans = Kunjungan::query()
            ->with('poli:id,kode,nama')
            ->whereDate('tanggal_kunjungan', today()->toDateString())
            ->get(['poli_id']);

        $total = $kunjungans->count();
        foreach ($kunjungans as $k) {
            $items[$this->kategoriPoli($k->poli)]['value']++;
        }

        return ['total' => $total, 'items' => array_values($items)];
    }

    /**
     * Klasifikasi kategori grafik kunjungan dari poli.
     */
    protected function kategoriPoli(?Poli $poli): string
    {
        if (! $poli) {
            return 'Rawat Jalan';
        }

        $kode = strtoupper((string) $poli->kode);
        $nama = strtolower((string) $poli->nama);

        if (str_contains($kode, 'IGD') || str_contains($nama, 'darurat')) {
            return 'IGD';
        }

        if (str_contains($nama, 'rawat inap') || str_contains($kode, 'RANAP') || str_contains($kode, 'INAP')) {
            return 'Rawat Inap';
        }

        return 'Rawat Jalan';
    }

    /**
     * Format persen ala Indonesia: bilangan bulat tampil tanpa desimal,
     * sisanya dengan satu angka desimal (koma sebagai pemisah desimal).
     */
    protected function persen(float $angka): string
    {
        $bulat = round($angka);

        return number_format(
            abs($angka - $bulat) < 0.05 ? $bulat : $angka,
            abs($angka - $bulat) < 0.05 ? 0 : 1,
            ',',
            '.'
        ).'%';
    }
}
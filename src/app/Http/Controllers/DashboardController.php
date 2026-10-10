<?php

namespace App\Http\Controllers;

use App\Models\Kunjungan;
use App\Models\MessageLog;
use App\Models\MessageReply;
use App\Models\Pnpp;
use App\Models\Reminder;
use App\Models\ResponManual;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
   
    protected const TARGET_PNPP = 1053;

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

        $targetPnpp = (int) (config('dashboard.target_pnpp') ?? self::TARGET_PNPP);
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

        $digitalReminder = Reminder::query()->whereIn('status', ['terjadwal', 'selesai'])->count();

        // ===================== STATISTIK KARTU ATAS =====================
        $stats = [
            ['label' => 'PNPP DALAM DATABASE',  'value' => $totalPnpp,
                'note' => $this->persen($totalPnpp / max(1, $targetPnpp) * 100).' dari target data',
                'color' => 'blue', 'icon' => 'users'],
            ['label' => 'TARGET PNPP',          'value' => number_format($targetPnpp, 0, ',', '.'),
                'note' => 'Target data PNPP', 'color' => 'gray', 'icon' => 'target'],
            ['label' => 'DIGITAL REMINDER',     'value' => $digitalReminder,
                'color' => 'yellow', 'icon' => 'bell'],
            ['label' => 'PNPP DI-OUTREACH',     'value' => $pnppDiOutreach,
                
                'color' => 'green', 'icon' => 'send'],
            ['label' => 'RESPON PNPP',          'value' => $respon,
                'color' => 'orange', 'icon' => 'chat'],
            ['label' => 'FOLLOW-UP',            'value' => $totalFollowupTerkirim,
                
                'color' => 'cyan', 'icon' => 'refresh'],
            ['label' => 'KUNJUNGAN PNPP',       'value' => $kunjungan,
                'color' => 'purple', 'icon' => 'hospital'],
            ['label' => 'CONVERSION RATE',      'value' => $this->persen($kunjungan / max(1, $totalOutreach) * 100),
                'note' => 'Dari digital reminder ke kunjungan',
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
     * Data hardcode total kunjungan per bulan — DITAMBAHKAN (additif) di atas
     * hasil hitungan tabel `kunjungans`, bukan pengganti.
     *
     * Jan–Agu 2026: riwayat sebelum aplikasi berjalan (data contoh lama).
     * Sep 2026: aplikasi beroperasi mulai 15 Sep; nilai 0 berarti grafik
     * September murni dari database dan siap disetel ulang kapan pun.
     *
     * Key = bulan (Y-m), isi = angka total kunjungan tambahan.
     */
    protected const TREND_HARDCODE = [
        '2026-01' => 70,
        '2026-02' => 88,
        '2026-03' => 87,
        '2026-04' => 122,
        '2026-05' => 93,
        '2026-06' => 49,
        '2026-07' => 104,
        '2026-08' => 93,
        '2026-09' => 5,
    ];

    /**
     * Tren kunjungan 1 tahun (Januari sampai bulan berjalan) sebagai satu
     * garis Total Kunjungan.
     *
     * Nilai tiap bulan = jumlah baris tabel kunjungan + nilai TREND_HARDCODE
     * (bersifat tambahan/additif). Bulan hardcode (Jan–Agu 2026 dan Sep 2026)
     * mendapat suntikan angka; bulan lainnya murni hasil database.
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

        $totalMonths = count($bulan);
        $data = array_fill(0, $totalMonths, 0);

        $kunjungans = Kunjungan::query()
            ->whereDate('tanggal_kunjungan', '>=', $mulai)
            ->whereDate('tanggal_kunjungan', '<=', now()->endOfMonth()->toDateString())
            ->get(['tanggal_kunjungan']);

        foreach ($kunjungans as $k) {
            $key = $k->tanggal_kunjungan->format('Y-m');
            $idx = array_search($key, array_keys($bulan), true);
            if ($idx === false) {
                continue;
            }
            $data[$idx]++;
        }

        // Suntik (tambahkan) angka hardcode di atas hitungan database.
        foreach (self::TREND_HARDCODE as $key => $nilai) {
            $idx = array_search($key, array_keys($bulan), true);
            if ($idx === false) {
                continue;
            }
            $data[$idx] += (int) $nilai;
        }

        return [
            'year' => $currentYear,
            'months' => array_map(fn (Carbon $d) => self::BULAN[(int) $d->format('n')], array_values($bulan)),
            'series' => [
                ['name' => 'Total Kunjungan', 'color' => '#0ea5e9', 'data' => $data],
            ],
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
     * Kunjungan hari ini berupa daftar poli — nama poli beserta jumlah
     * pasiennya, diurutkan dari yang terbanyak. Kunjungan tanpa poli
     * tercatat sebagai "Tanpa Poli".
     *
     * @return array{total: int, items: array<int, array{label: string, value: int, color: string}>}
     */
    protected function kunjunganHariIni(): array
    {
        $kunjungans = Kunjungan::query()
            ->with('poli:id,nama')
            ->whereDate('tanggal_kunjungan', today()->toDateString())
            ->get(['poli_id']);

        $perPoli = [];
        foreach ($kunjungans as $k) {
            $nama = $k->poli?->nama ?? 'Tanpa Poli';
            $perPoli[$nama] = ($perPoli[$nama] ?? 0) + 1;
        }
        arsort($perPoli);

        $warna = ['blue', 'cyan', 'green', 'orange', 'purple', 'pink', 'yellow', 'navy', 'red', 'gray'];

        $items = [];
        $i = 0;
        foreach ($perPoli as $nama => $jumlah) {
            $items[] = [
                'label' => $nama,
                'value' => $jumlah,
                'color' => $warna[$i % count($warna)],
            ];
            $i++;
        }

        return ['total' => count($kunjungans), 'items' => $items];
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

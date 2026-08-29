<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // ============================================================
        // DATA DUMMY DASHBOARD — PROAKTIF RS BHAYANGKARA BOGOR
        // ============================================================

        // Statistik kartu teratas
        $stats = [
            ['label' => 'PNPP DALAM DATABASE',  'value' => '1.026', 'note' => '100% dari target data',      'color' => 'blue',   'icon' => 'users'],
            ['label' => 'PNPP DI-OUTREACH',     'value' => '650',   'note' => '63,4% dari target',          'color' => 'green',  'icon' => 'send'],
            ['label' => 'RESPON PNPP',           'value' => '390',   'note' => '60,0% dari outreach',        'color' => 'orange', 'icon' => 'chat'],
            ['label' => 'FOLLOW-UP',             'value' => '350',   'note' => '89,7% dari respons',         'color' => 'cyan',   'icon' => 'refresh'],
            ['label' => 'KUNJUNGAN PNPP',        'value' => '120',   'note' => '>15% dari baseline',         'color' => 'purple', 'icon' => 'hospital'],
            ['label' => 'CONVERSION RATE',       'value' => '18,5%', 'note' => 'Dari outreach ke kunjungan', 'color' => 'red',    'icon' => 'trending'],
        ];

        // Tren kunjungan 6 bulan terakhir (line chart)
        $trend = [
            'months' => ['Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu'],
            'series' => [
                ['name' => 'IGD',         'color' => '#ef4444', 'data' => [40, 30, 35, 45, 50, 65]],
                ['name' => 'Rawat Jalan', 'color' => '#3b82f6', 'data' => [40, 55, 45, 60, 60, 60]],
                ['name' => 'Rawat Inap',  'color' => '#16a34a', 'data' => [15, 10, 15, 20, 12, 15]],
            ],
        ];

        // Outreach per satker
        $outreach = [
            ['name' => 'Polresta Bogor',       'value' => 220, 'percent' => '72%',  'color' => 'navy'],
            ['name' => 'Polsek Bogor Barat',   'value' => 120, 'percent' => '60%',  'color' => 'green'],
            ['name' => 'Polsek Bogor Timur',   'value' => 110, 'percent' => '55%',  'color' => 'orange'],
            ['name' => 'Polsek Tanah Sareal',  'value' => 90,  'percent' => '45%',  'color' => 'yellow'],
            ['name' => 'Polsek Bogor Selatan', 'value' => 70,  'percent' => '35%',  'color' => 'cyan'],
            ['name' => 'Polsek Bogor Utara',   'value' => 40,  'percent' => '20%',  'color' => 'pink'],
        ];

        // Status follow-up (donut)
        $followup = [
            'total'  => 350,
            'series' => [
                ['name' => 'Selesai',     'value' => 220, 'percent' => '62,9%', 'color' => '#22c55e'],
                ['name' => 'Proses',      'value' => 90,  'percent' => '25,7%', 'color' => '#f59e0b'],
                ['name' => 'Terlambat',   'value' => 40,  'percent' => '11,4%', 'color' => '#ef4444'],
            ],
        ];

        // Follow-up hari ini
        $followupToday = [
            'count' => 13,
            'note'  => 'PNPP perlu ditindaklanjuti',
        ];

        // Aktivitas terkini
        $activities = [
            ['title' => 'Outreach ke PNPP',   'name' => 'Aiptu Dedi Kurniawan',   'satker' => 'Polsek Bogor Barat',  'time' => '09:12', 'color' => 'green',  'icon' => 'phone'],
            ['title' => 'Respon PNPP',         'name' => 'Brigadir Rizky Safiet',  'satker' => 'Polresta Bogor',      'time' => '08:45', 'color' => 'orange', 'icon' => 'chat'],
            ['title' => 'Reminder Terkirim',   'name' => 'Aipda Martha Gumanti',   'satker' => 'Polsek Bogor Utara',  'time' => '08:30', 'color' => 'yellow', 'icon' => 'bell'],
            ['title' => 'Follow-up Selesai',   'name' => 'Brigadir Andi Saputra',  'satker' => 'Polsek Bogor Timur',  'time' => '07:15', 'color' => 'blue',   'icon' => 'check'],
            ['title' => 'Kunjungan PNPP',      'name' => 'Brigadir Siti Nurhaliza','satker' => 'Polsek Bogor Selatan','time' => '07:00', 'color' => 'purple', 'icon' => 'visit'],
        ];

        // Monitoring target 60 hari
        $monitoring = [
            ['name' => 'PNPP ditubung',     'target' => '≥ 65%', 'kunjungan' => '63,4%', 'capaian' => '',  'status' => 'On Track'],
            ['name' => 'Respon ke PNPP',     'target' => '≥ 53%', 'kunjungan' => '60,0%', 'capaian' => '',  'status' => 'On Track'],
            ['name' => 'Digital Reminder',   'target' => '≥ 52%', 'kunjungan' => '55,2%', 'capaian' => '',  'status' => 'On Track'],
            ['name' => 'Follow-up',          'target' => '≥ 92%', 'kunjungan' => '89,7%', 'capaian' => '',  'status' => 'Perlu Perhatian'],
            ['name' => 'Kunjungan PNPP',     'target' => '≥ 15%', 'kunjungan' => '↑13%',  'capaian' => '',  'status' => 'On Track'],
        ];

        // Kunjungan PNPP hari ini
        $kunjunganToday = [
            'total' => 8,
            'items'   => [
                ['label' => 'IGD',         'value' => 2, 'color' => 'blue'],
                ['label' => 'Rawat Jalan', 'value' => 6, 'color' => 'cyan'],
                ['label' => 'Rawat Inap',  'value' => 0, 'color' => 'gray'],
            ],
        ];

        // Alert & notifikasi
        $alerts = [
            ['title' => 'Follow-up Terlambat',    'count' => 5, 'color' => 'red'],
            ['title' => 'Data Tidak Lengkap',      'count' => 8, 'color' => 'yellow'],
            ['title' => 'Reminder Gagal Terkirim', 'count' => 3, 'color' => 'orange'],
        ];

        return view('dashboard', [
            'user'             => $user,
            'role'             => $user->getRoleNames()->first() ?? 'No Role',
            'permissions'      => $user->getAllPermissions()->pluck('name'),
            'stats'            => $stats,
            'trend'            => $trend,
            'outreach'         => $outreach,
            'followup'         => $followup,
            'followupToday'    => $followupToday,
            'activities'       => $activities,
            'monitoring'       => $monitoring,
            'kunjunganToday'   => $kunjunganToday,
            'alerts'           => $alerts,
        ]);
    }
}

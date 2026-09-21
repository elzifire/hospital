<?php

namespace App\Support;

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
use Illuminate\Database\Eloquent\Builder;

/**
 * Registri konfigurasi laporan untuk modul Monitoring.
 *
 * Setiap entitas laporan mendefinisikan:
 *  - label        : nama tampilan (konsisten dengan menu sidebar)
 *  - group        : grup laporan ('master' | 'broadcasting')
 *  - description  : deskripsi singkat (dipakai di kartu & halaman detail)
 *  - icon         : path SVG heroicons (outline)
 *  - tone         : warna tema kartu/ikon (sky, emerald, violet, dst.)
 *  - available    : false → laporan belum tersedia (kartu non-aktif di hub)
 *  - hidden       : true  → disembunyikan sementara (kartu & laporan tidak tampil)
 *  - permission   : permission yang wajib dipegang user untuk membuka laporan
 *  - count        : callback () => int untuk jumlah data di kartu hub
 *  - model/eager/withCount : dasar query laporan
 *  - query        : callback (Builder) pembatas dasar query (mis. per jenis pesan)
 *  - search       : callback (Builder, string $term) untuk pencarian
 *  - filters      : daftar filter [{ key, label, type, options?, apply }]
 *  - sorts        : opsi pengurutan { key: { label, apply } }
 *  - stats        : callback () => kartu statistik halaman detail
 *  - columns      : kolom tabel { label, type, tone?, value }
 *  - export       : { headers, toRow } untuk unduhan xlsx/csv
 *
 * Tipe kolom yang dikenali view laporan:
 *  profile | strong | text | mono | number | stat | badge | tags
 */
class MonitoringRegistry
{
    /**
     * Grup laporan (urutan & label mengikuti grouping sidebar).
     */
    public static function groups(): array
    {
        return [
            'master' => 'Data Master',
            'broadcasting' => 'Broadcasting',
        ];
    }

    /**
     * Semua key entitas laporan.
     */
    public static function entities(): array
    {
        return array_keys(self::configs());
    }

    public static function has(string $entity): bool
    {
        return array_key_exists($entity, self::configs());
    }

    public static function config(string $entity): array
    {
        return self::configs()[$entity];
    }

    public static function configs(): array
    {
        // Ikon bersama (heroicons outline)
        $iconUsers = 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z';
        $iconHeart = 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z';
        $iconWarn = 'M12 9v3.75m0 3.75h.008v.008H12v-.008ZM10.34 3.94 2.91 17.25a2.25 2.25 0 0 0 1.95 3.375h14.28a2.25 2.25 0 0 0 1.95-3.375L13.66 3.94a1.875 1.875 0 0 0-3.32 0Z';
        $iconCheck = 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z';
        $iconBuild = 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21';
        $iconClip = 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z';
        $iconUser = 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z';
        $iconCal = 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5';
        $iconClock = 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z';
        $iconPin = 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z';
        $iconBell = 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0';
        $iconMega = 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46';
        $iconInbox = 'M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-2.029 2.115 2.115 0 0 0-1.661-.586 48.744 48.744 0 0 0-8.983 0 2.115 2.115 0 0 0-1.661.586 2.126 2.126 0 0 0-.476 2.029c.172.714.308 1.44.41 2.174m3.923-2.174a41.03 41.03 0 0 0-.41 2.174c-.058.35-.088.706-.088 1.066v4.286c0 .36.03.716.088 1.066';
        $iconPhone = 'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z';
        $iconTap = 'M7.5 12a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9m5.5 0a4.5 4.5 0 1 0 0 9m8.5 0v0a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z';

        $hariTone = [
            'Senin' => 'sky',
            'Selasa' => 'emerald',
            'Rabu' => 'violet',
            'Kamis' => 'amber',
            'Jumat' => 'rose',
            'Sabtu' => 'teal',
            'Minggu' => 'slate',
        ];

        $totalPnpp = (int) Pnpp::count();
        $percentPnpp = fn (int $count): string => $totalPnpp > 0
            ? round($count * 100 / max(1, $totalPnpp)).'% dari total PNPP'
            : '—';

        // Pembatasan data per poli untuk user akun poli (role "poli"):
        // hanya baris polinya sendiri yang terlihat/dihitung/di-export.
        // Dipakai untuk laporan yang punya kolom poli_id langsung
        // (kunjungan & digital-reminder).
        $scopePoli = function (Builder $query): void {
            $poliId = auth()->user()?->poliId();

            if ($poliId !== null) {
                $query->where('poli_id', $poliId);
            }
        };

        // Pembatasan serupa untuk pesan keluar (MessageLog): pesan tidak
        // menyimpan poli_id — scope lewat reminder pembentuknya
        // (outreach & follow-up).
        $scopePoliPesan = function (Builder $query): void {
            $poliId = auth()->user()?->poliId();

            if ($poliId !== null) {
                $query->whereHas('reminder', fn ($reminder) => $reminder->where('poli_id', $poliId));
            }
        };

        // Peta permission per jenis pesan + warna status pengiriman
        // (laporan broadcasting) — laporan terkunci permission fiturnya.
        $jenisPermission = [
            'outreach' => 'manage outreach',
            'follow_up' => 'manage follow-up',
        ];
        $statusTone = [
            'menunggu' => 'slate',
            'mengirim' => 'sky',
            'terkirim' => 'emerald',
            'gagal' => 'rose',
            'dibatalkan' => 'amber',
        ];

        // Konfigurasi dasar laporan pesan keluar per modul (jenis pesan):
        // Outreach, Digital Reminder, dan Follow Up berbagi bentuk tabel,
        // filter, dan export — variasi (kolom, statistik, export) disuntik
        // per entitas lewat parameter.
        $laporanPesan = fn (array $o): array => [
            'label' => $o['label'],
            'group' => 'broadcasting',
            'permission' => $jenisPermission[$o['jenis']],
            'description' => $o['description'],
            'icon' => $o['icon'],
            'tone' => $o['tone'],
            'available' => true,
            'count' => fn () => MessageLog::where('jenis', $o['jenis'])->tap($scopePoliPesan)->count(),
            'model' => MessageLog::class,
            'eager' => ['template', 'pnpp.satker'],
            'withCount' => $o['withCount'] ?? [],
            'query' => fn (Builder $q) => $q->where('jenis', $o['jenis']),
            'poliScope' => $scopePoliPesan,
            'searchHint' => 'Cari nama/NIP pasien, nomor HP, isi pesan, atau nama template...',
            'search' => function (Builder $q, string $t) {
                $tAtas = strtoupper($t);

                return $q->where(fn ($w) => $w
                    ->whereRaw('UPPER(penerima_nama) LIKE ?', ["%{$tAtas}%"])
                    ->orWhere('penerima_no_hp', 'like', "%{$t}%")
                    ->orWhere('konten', 'like', "%{$t}%")
                    ->orWhereHas('pnpp', fn ($p) => $p
                        ->whereRaw('UPPER(nama) LIKE ?', ["%{$tAtas}%"])
                        ->orWhere('nip', 'like', "%{$t}%"))
                    ->orWhereHas('template', fn ($p) => $p->where('judul', 'like', "%{$t}%")));
            },
            'filters' => array_merge(
                [
                    [
                        'key' => 'status',
                        'label' => 'Semua Status',
                        'type' => 'select',
                        'options' => fn () => MessageLog::LABEL_STATUS,
                        'apply' => fn (Builder $q, string $v) => $q->where('status', $v),
                    ],
                    [
                        'key' => 'satker',
                        'label' => 'Semua Satker',
                        'type' => 'select',
                        'options' => fn () => Satker::orderBy('nama')->pluck('nama', 'id')->all(),
                        'apply' => fn (Builder $q, string $v) => $q->whereHas('pnpp', fn ($p) => $p->where('satker_id', (int) $v)),
                    ],
                    [
                        'key' => 'from',
                        'label' => 'Dari Tanggal',
                        'type' => 'date',
                        'apply' => fn (Builder $q, string $v) => $q->where('created_at', '>=', $v),
                    ],
                    [
                        'key' => 'to',
                        'label' => 'Sampai Tanggal',
                        'type' => 'date',
                        'apply' => fn (Builder $q, string $v) => $q->where('created_at', '<=', $v.' 23:59:59'),
                    ],
                ],
                $o['filtersExtra'] ?? [],
            ),
            'sorts' => [
                'terbaru' => ['label' => 'Terbaru', 'apply' => fn (Builder $q) => $q->orderByDesc('created_at')],
                'terlama' => ['label' => 'Terlama', 'apply' => fn (Builder $q) => $q->orderBy('created_at')],
            ],
            'defaultSort' => 'terbaru',
            'stats' => $o['stats'],
            'columns' => array_merge(
                [
                    ['label' => 'Pasien',   'type' => 'profile', 'tone' => $o['tone'], 'value' => fn ($m) => [$m->penerima_nama, $m->penerima_no_hp]],
                    ['label' => 'Satker',   'type' => 'text',                      'value' => fn ($m) => $m->pnpp?->satker?->nama],
                    ['label' => 'Template', 'type' => 'badge',  'tone' => 'sky',    'value' => fn ($m) => $m->template ? [$m->template->judul, 'sky'] : null],
                ],
                $o['columns'],
                [
                    ['label' => 'Status', 'type' => 'badge',                      'value' => fn ($m) => [$m->status, $statusTone[$m->status] ?? 'slate']],
                    ['label' => 'Waktu',  'type' => 'strong',                     'value' => fn ($m) => ($m->sent_at ?? $m->created_at)?->translatedFormat('d M Y H:i')],                ]
            ),
            'export' => [
                'headers' => array_merge(
                    ['Tanggal Dibuat', 'Pasien', 'No. HP', 'Satker', 'Template', 'Status', 'Terkirim Pada'],
                    $o['exportHeaders'],
                    ['Isi Pesan', 'Keterangan Gagal']
                ),
                'toRow' => fn ($m) => array_merge([
                    $m->created_at->format('Y-m-d H:i'),
                    $m->penerima_nama ?? '',
                    $m->penerima_no_hp ?? '',
                    $m->pnpp?->satker?->nama ?? '',
                    $m->template?->judul ?? '',
                    MessageLog::LABEL_STATUS[$m->status] ?? $m->status,
                    $m->sent_at?->format('Y-m-d H:i') ?? '',
                ], ($o['exportRow'])($m), [$m->konten ?? '', $m->error ?? '']),
            ],
        ];

        return [

            // ======================= DATA MASTER =======================

            'pnpp' => [
                'label' => 'PNPP',
                'group' => 'master',
                'permission' => 'manage pnpp',
                'description' => 'Data pegawai PNPP beserta profil, penyakit, dan riwayat kunjungannya.',
                'icon' => $iconUsers,
                'tone' => 'sky',
                'available' => true,
                'count' => fn () => Pnpp::count(),
                'model' => Pnpp::class,
                'eager' => ['satker', 'penyakit', 'penyakitMenahun', 'latestKunjungan'],
                'withCount' => ['kunjungans'],
                'searchHint' => 'Cari nama, NIP, No. BPJS, No. HP, atau email...',
                'search' => function (Builder $q, string $t) {
                    $tAtas = strtoupper($t);

                    return $q->where(fn ($w) => $w
                        ->whereRaw('UPPER(nama) LIKE ?', ["%{$tAtas}%"])
                        ->orWhere('nip', 'like', "%{$t}%")
                        ->orWhere('no_bpjs', 'like', "%{$t}%")
                        ->orWhere('no_hp', 'like', "%{$t}%")
                        ->orWhere('email', 'like', "%{$t}%"));
                },
                'filters' => [
                    [
                        'key' => 'satker',
                        'label' => 'Semua Satker',
                        'type' => 'select',
                        'options' => fn () => Satker::orderBy('nama')->pluck('nama', 'id')->all(),
                        'apply' => fn (Builder $q, string $v) => $q->where('satker_id', (int) $v),
                    ],
                    [
                        'key' => 'jk',
                        'label' => 'Semua JK',
                        'type' => 'select',
                        'options' => fn () => ['L' => 'Laki-laki', 'P' => 'Perempuan'],
                        'apply' => fn (Builder $q, string $v) => $q->where('jenis_kelamin', $v),
                    ],
                    [
                        'key' => 'status',
                        'label' => 'Semua Status',
                        'type' => 'select',
                        'options' => fn () => ['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'],
                        'apply' => fn (Builder $q, string $v) => $q->where('status_aktif', $v),
                    ],
                    [
                        'key' => 'kronis',
                        'label' => 'Penyakit Kronis',
                        'type' => 'select',
                        'options' => fn () => ['yes' => 'Ada Penyakit Kronis', 'no' => 'Tanpa Penyakit Kronis'],
                        'apply' => fn (Builder $q, string $v) => $v === 'yes'
                            ? $q->whereHas('penyakit')
                            : $q->whereDoesntHave('penyakit'),
                    ],
                ],
                'sorts' => [
                    'az' => ['label' => 'Nama A–Z',             'apply' => fn (Builder $q) => $q->orderBy('nama')],
                    'za' => ['label' => 'Nama Z–A',             'apply' => fn (Builder $q) => $q->orderByDesc('nama')],
                    'newest' => ['label' => 'Terbaru',              'apply' => fn (Builder $q) => $q->orderByDesc('created_at')],
                    'oldest' => ['label' => 'Terlama',              'apply' => fn (Builder $q) => $q->orderBy('created_at')],
                    'kunjungan' => ['label' => 'Kunjungan Terbanyak',  'apply' => fn (Builder $q) => $q->orderByDesc('kunjungans_count')],
                ],
                'defaultSort' => 'az',
                'stats' => fn () => [
                    ['label' => 'Total PNPP',       'value' => Pnpp::count(),                             'icon' => $iconUsers, 'tone' => 'sky'],
                    ['label' => 'Penyakit Kronis',  'value' => Pnpp::whereHas('penyakit')->count(),        'icon' => $iconHeart, 'tone' => 'rose'],
                    ['label' => 'Penyakit Menahun', 'value' => Pnpp::whereHas('penyakitMenahun')->count(), 'icon' => $iconWarn,  'tone' => 'amber'],
                    ['label' => 'Status Aktif',     'value' => Pnpp::where('status_aktif', 'aktif')->count(), 'icon' => $iconCheck, 'tone' => 'emerald'],
                ],
                'columns' => [
                    ['label' => 'PNPP',             'type' => 'profile', 'tone' => 'sky',     'value' => fn ($m) => [$m->nama, $m->nip]],
                    ['label' => 'No. BPJS',         'type' => 'mono',                         'value' => fn ($m) => $m->no_bpjs],
                    ['label' => 'Satker',           'type' => 'text',                         'value' => fn ($m) => $m->satker?->nama],
                    ['label' => 'Usia',             'type' => 'strong',                       'value' => fn ($m) => $m->usia !== null ? $m->usia.' th' : null],
                    ['label' => 'JK',               'type' => 'badge',                        'value' => fn ($m) => $m->jenis_kelamin ? [$m->jenis_kelamin, $m->jenis_kelamin === 'L' ? 'sky' : 'rose'] : null],
                    ['label' => 'Penyakit Kronis',  'type' => 'tags',    'tone' => 'amber',   'value' => fn ($m) => $m->penyakit->pluck('nama')->all()],
                    ['label' => 'Penyakit Menahun', 'type' => 'tags',    'tone' => 'emerald', 'value' => fn ($m) => $m->penyakitMenahun->pluck('nama')->all()],
                    ['label' => 'Status',           'type' => 'badge',                        'value' => fn ($m) => $m->status_aktif ? [ucfirst($m->status_aktif), $m->status_aktif === 'aktif' ? 'emerald' : 'slate'] : null],
                    ['label' => 'Kunjungan',        'type' => 'stat',    'tone' => 'sky',     'value' => fn ($m) => [
                        (string) $m->kunjungans_count,
                        $m->tanggal_terakhir_berobat
                            ? 'Terakhir '.$m->tanggal_terakhir_berobat->translatedFormat('d M y')
                            : 'Belum pernah berobat',
                    ]],
                ],
                'export' => [
                    'headers' => ['Nama', 'NIP/NRP', 'No. BPJS', 'Satker', 'Jenis Kelamin', 'Usia', 'Tanggal Lahir', 'No. HP', 'Email', 'Status', 'Penyakit Kronis', 'Penyakit Menahun', 'Jumlah Kunjungan', 'Terakhir Berobat'],
                    'toRow' => fn ($m) => [
                        $m->nama,
                        $m->nip ?? '',
                        $m->no_bpjs ?? '',
                        $m->satker?->nama ?? '',
                        $m->jenis_kelamin ?? '',
                        $m->usia !== null ? (string) $m->usia : '',
                        $m->tanggal_lahir?->format('Y-m-d') ?? '',
                        $m->no_hp ?? '',
                        $m->email ?? '',
                        $m->status_aktif ?? '',
                        $m->penyakit->pluck('nama')->implode(', '),
                        $m->penyakitMenahun->pluck('nama')->implode(', '),
                        (string) $m->kunjungans_count,
                        $m->tanggal_terakhir_berobat?->format('Y-m-d') ?? '',
                    ],
                ],
            ],

            'satker' => [
                'label' => 'Satker',
                'group' => 'master',
                'permission' => 'manage satker',
                'description' => 'Daftar satuan kerja asal PNPP beserta jumlah anggotanya.',
                'icon' => $iconBuild,
                'tone' => 'indigo',
                'available' => true,
                'count' => fn () => Satker::count(),
                'model' => Satker::class,
                'eager' => [],
                'withCount' => ['pnpps'],
                'searchHint' => 'Cari nama atau kode satker...',
                'search' => function (Builder $q, string $t) {
                    $tAtas = strtoupper($t);

                    return $q->where(fn ($w) => $w
                        ->whereRaw('UPPER(nama) LIKE ?', ["%{$tAtas}%"])
                        ->orWhere('kode', 'like', "%{$t}%"));
                },
                'filters' => [
                    [
                        'key' => 'pnpp',
                        'label' => 'Semua Satker',
                        'type' => 'select',
                        'options' => fn () => ['yes' => 'Punya PNPP', 'no' => 'Tanpa PNPP'],
                        'apply' => fn (Builder $q, string $v) => $v === 'yes'
                            ? $q->whereHas('pnpps')
                            : $q->whereDoesntHave('pnpps'),
                    ],
                ],
                'sorts' => [
                    'az' => ['label' => 'Nama A–Z',       'apply' => fn (Builder $q) => $q->orderBy('nama')],
                    'za' => ['label' => 'Nama Z–A',       'apply' => fn (Builder $q) => $q->orderByDesc('nama')],
                    'pnpp_desc' => ['label' => 'PNPP Terbanyak', 'apply' => fn (Builder $q) => $q->orderByDesc('pnpps_count')],
                ],
                'defaultSort' => 'az',
                'stats' => fn () => [
                    ['label' => 'Total Satker', 'value' => Satker::count(),                          'icon' => $iconBuild, 'tone' => 'indigo'],
                    ['label' => 'Total PNPP',   'value' => Pnpp::count(),                             'icon' => $iconUsers, 'tone' => 'sky'],
                    ['label' => 'Punya PNPP',   'value' => Satker::whereHas('pnpps')->count(),        'icon' => $iconCheck, 'tone' => 'emerald'],
                    ['label' => 'Tanpa PNPP',   'value' => Satker::whereDoesntHave('pnpps')->count(), 'icon' => $iconWarn,  'tone' => 'amber'],
                ],
                'columns' => [
                    ['label' => 'Satker',      'type' => 'profile', 'tone' => 'indigo', 'value' => fn ($m) => [$m->nama, $m->kode]],
                    ['label' => 'Kode',        'type' => 'mono',                        'value' => fn ($m) => $m->kode],
                    ['label' => 'Jumlah PNPP', 'type' => 'stat',    'tone' => 'sky',     'value' => fn ($m) => [(string) $m->pnpps_count, $percentPnpp($m->pnpps_count)]],
                ],
                'export' => [
                    'headers' => ['Kode', 'Nama', 'Jumlah PNPP'],
                    'toRow' => fn ($m) => [$m->kode ?? '', $m->nama, (string) $m->pnpps_count],
                ],
            ],

            'penyakit' => [
                'label' => 'Penyakit Kronis',
                'group' => 'master',
                'permission' => 'manage penyakit',
                'description' => 'Daftar penyakit kronis dan jumlah PNPP yang terdampak.',
                'icon' => $iconHeart,
                'tone' => 'rose',
                'available' => true,
                'count' => fn () => PenyakitKronis::count(),
                'model' => PenyakitKronis::class,
                'eager' => [],
                'withCount' => ['pnpps'],
                'searchHint' => 'Cari nama atau kode penyakit...',
                'search' => function (Builder $q, string $t) {
                    $tAtas = strtoupper($t);

                    return $q->where(fn ($w) => $w
                        ->whereRaw('UPPER(nama) LIKE ?', ["%{$tAtas}%"])
                        ->orWhere('kode', 'like', "%{$t}%"));
                },
                'filters' => [
                    [
                        'key' => 'pnpp',
                        'label' => 'Semua Penyakit',
                        'type' => 'select',
                        'options' => fn () => ['yes' => 'Punya PNPP', 'no' => 'Tanpa PNPP'],
                        'apply' => fn (Builder $q, string $v) => $v === 'yes'
                            ? $q->whereHas('pnpps')
                            : $q->whereDoesntHave('pnpps'),
                    ],
                ],
                'sorts' => [
                    'az' => ['label' => 'Nama A–Z',       'apply' => fn (Builder $q) => $q->orderBy('nama')],
                    'za' => ['label' => 'Nama Z–A',       'apply' => fn (Builder $q) => $q->orderByDesc('nama')],
                    'pnpp_desc' => ['label' => 'PNPP Terbanyak', 'apply' => fn (Builder $q) => $q->orderByDesc('pnpps_count')],
                ],
                'defaultSort' => 'az',
                'stats' => fn () => [
                    ['label' => 'Total Penyakit', 'value' => PenyakitKronis::count(),                          'icon' => $iconHeart, 'tone' => 'rose'],
                    ['label' => 'PNPP Terdampak', 'value' => Pnpp::whereHas('penyakit')->count(),               'icon' => $iconUsers, 'tone' => 'sky'],
                    ['label' => 'Tanpa Pasien',   'value' => PenyakitKronis::whereDoesntHave('pnpps')->count(), 'icon' => $iconWarn,  'tone' => 'amber'],
                    ['label' => 'PNPP Terbanyak', 'value' => PenyakitKronis::withCount('pnpps')->orderByDesc('pnpps_count')->value('nama') ?? '—', 'icon' => $iconCheck, 'tone' => 'emerald'],
                ],
                'columns' => [
                    ['label' => 'Penyakit',       'type' => 'profile', 'tone' => 'rose', 'value' => fn ($m) => [$m->nama, $m->kode]],
                    ['label' => 'Kode',           'type' => 'mono',                       'value' => fn ($m) => $m->kode],
                    ['label' => 'PNPP Terdampak', 'type' => 'stat',    'tone' => 'sky',   'value' => fn ($m) => [(string) $m->pnpps_count, $percentPnpp($m->pnpps_count)]],
                ],
                'export' => [
                    'headers' => ['Kode', 'Nama', 'Jumlah PNPP'],
                    'toRow' => fn ($m) => [$m->kode ?? '', $m->nama, (string) $m->pnpps_count],
                ],
            ],

            'penyakit-menahun' => [
                'label' => 'Penyakit Menahun',
                'group' => 'master',
                'permission' => 'manage penyakit',
                'description' => 'Daftar penyakit menahun dan jumlah PNPP yang terdampak.',
                'icon' => $iconWarn,
                'tone' => 'amber',
                'available' => true,
                'count' => fn () => PenyakitMenahun::count(),
                'model' => PenyakitMenahun::class,
                'eager' => [],
                'withCount' => ['pnpps'],
                'searchHint' => 'Cari nama atau kode penyakit...',
                'search' => function (Builder $q, string $t) {
                    $tAtas = strtoupper($t);

                    return $q->where(fn ($w) => $w
                        ->whereRaw('UPPER(nama) LIKE ?', ["%{$tAtas}%"])
                        ->orWhere('kode', 'like', "%{$t}%"));
                },
                'filters' => [
                    [
                        'key' => 'pnpp',
                        'label' => 'Semua Penyakit',
                        'type' => 'select',
                        'options' => fn () => ['yes' => 'Punya PNPP', 'no' => 'Tanpa PNPP'],
                        'apply' => fn (Builder $q, string $v) => $v === 'yes'
                            ? $q->whereHas('pnpps')
                            : $q->whereDoesntHave('pnpps'),
                    ],
                ],
                'sorts' => [
                    'az' => ['label' => 'Nama A–Z',       'apply' => fn (Builder $q) => $q->orderBy('nama')],
                    'za' => ['label' => 'Nama Z–A',       'apply' => fn (Builder $q) => $q->orderByDesc('nama')],
                    'pnpp_desc' => ['label' => 'PNPP Terbanyak', 'apply' => fn (Builder $q) => $q->orderByDesc('pnpps_count')],
                ],
                'defaultSort' => 'az',
                'stats' => fn () => [
                    ['label' => 'Total Penyakit', 'value' => PenyakitMenahun::count(),                           'icon' => $iconWarn,  'tone' => 'amber'],
                    ['label' => 'PNPP Terdampak', 'value' => Pnpp::whereHas('penyakitMenahun')->count(),          'icon' => $iconUsers, 'tone' => 'sky'],
                    ['label' => 'Tanpa Pasien',   'value' => PenyakitMenahun::whereDoesntHave('pnpps')->count(),  'icon' => $iconCheck, 'tone' => 'emerald'],
                    ['label' => 'PNPP Terbanyak', 'value' => PenyakitMenahun::withCount('pnpps')->orderByDesc('pnpps_count')->value('nama') ?? '—', 'icon' => $iconHeart, 'tone' => 'rose'],
                ],
                'columns' => [
                    ['label' => 'Penyakit',       'type' => 'profile', 'tone' => 'amber', 'value' => fn ($m) => [$m->nama, $m->kode]],
                    ['label' => 'Kode',           'type' => 'mono',                        'value' => fn ($m) => $m->kode],
                    ['label' => 'PNPP Terdampak', 'type' => 'stat',    'tone' => 'sky',    'value' => fn ($m) => [(string) $m->pnpps_count, $percentPnpp($m->pnpps_count)]],
                ],
                'export' => [
                    'headers' => ['Kode', 'Nama', 'Jumlah PNPP'],
                    'toRow' => fn ($m) => [$m->kode ?? '', $m->nama, (string) $m->pnpps_count],
                ],
            ],

            'poli' => [
                'label' => 'Instalasi',
                'group' => 'master',
                'permission' => 'manage poli',
                'description' => 'Daftar instalasi/poli layanan beserta dokter yang bertugas.',
                'icon' => $iconClip,
                'tone' => 'violet',
                'available' => true,
                'count' => fn () => Poli::count(),
                'model' => Poli::class,
                'eager' => [],
                'withCount' => ['dokters'],
                'searchHint' => 'Cari nama atau kode instalasi...',
                'search' => function (Builder $q, string $t) {
                    $tAtas = strtoupper($t);

                    return $q->where(fn ($w) => $w
                        ->whereRaw('UPPER(nama) LIKE ?', ["%{$tAtas}%"])
                        ->orWhere('kode', 'like', "%{$t}%"));
                },
                'filters' => [
                    [
                        'key' => 'dokter',
                        'label' => 'Semua Instalasi',
                        'type' => 'select',
                        'options' => fn () => ['yes' => 'Punya Dokter', 'no' => 'Tanpa Dokter'],
                        'apply' => fn (Builder $q, string $v) => $v === 'yes'
                            ? $q->whereHas('dokters')
                                : $q->whereDoesntHave('dokters'),
                    ],
                ],
                'sorts' => [
                    'az' => ['label' => 'Nama A–Z',        'apply' => fn (Builder $q) => $q->orderBy('nama')],
                    'za' => ['label' => 'Nama Z–A',        'apply' => fn (Builder $q) => $q->orderByDesc('nama')],
                    'dokter_desc' => ['label' => 'Dokter Terbanyak', 'apply' => fn (Builder $q) => $q->orderByDesc('dokters_count')],
                ],
                'defaultSort' => 'az',
                'stats' => fn () => [
                    ['label' => 'Total Instalasi', 'value' => Poli::count(),                     'icon' => $iconClip,  'tone' => 'violet'],
                    ['label' => 'Total Dokter',    'value' => Dokter::count(),                    'icon' => $iconUser,  'tone' => 'emerald'],
                    ['label' => 'Punya Dokter',    'value' => Poli::whereHas('dokters')->count(), 'icon' => $iconCheck, 'tone' => 'sky'],
                    ['label' => 'Jadwal Dibuat',   'value' => Jadwal::count(),                    'icon' => $iconCal,   'tone' => 'teal'],
                ],
                'columns' => [
                    ['label' => 'Instalasi', 'type' => 'profile', 'tone' => 'violet', 'value' => fn ($m) => [$m->nama, $m->kode]],
                    ['label' => 'Kode',      'type' => 'mono',                        'value' => fn ($m) => $m->kode],
                    ['label' => 'Dokter',    'type' => 'number',  'tone' => 'emerald', 'value' => fn ($m) => (string) $m->dokters_count],
                ],
                'export' => [
                    'headers' => ['Kode', 'Nama', 'Jumlah Dokter'],
                    'toRow' => fn ($m) => [$m->kode ?? '', $m->nama, (string) $m->dokters_count],
                ],
            ],

            'dokter' => [
                'label' => 'Dokter',
                'group' => 'master',
                'permission' => 'manage dokter',
                'description' => 'Daftar dokter beserta instalasi dan jumlah jadwal praktiknya.',
                'icon' => $iconUser,
                'tone' => 'emerald',
                'available' => true,
                'hidden' => true,
                'count' => fn () => Dokter::count(),
                'model' => Dokter::class,
                'eager' => ['poli'],
                'withCount' => ['jadwals'],
                'searchHint' => 'Cari nama dokter, spesialisasi, atau instalasi...',
                'search' => function (Builder $q, string $t) {
                    $tAtas = strtoupper($t);

                    return $q->where(fn ($w) => $w
                        ->whereRaw('UPPER(nama) LIKE ?', ["%{$tAtas}%"])
                        ->orWhere('spesialisasi', 'like', "%{$t}%")
                        ->orWhereHas('poli', fn ($p) => $p->whereRaw('UPPER(nama) LIKE ?', ["%{$tAtas}%"])));
                },
                'filters' => [
                    [
                        'key' => 'poli',
                        'label' => 'Semua Instalasi',
                        'type' => 'select',
                        'options' => fn () => Poli::orderBy('nama')->pluck('nama', 'id')->all(),
                        'apply' => fn (Builder $q, string $v) => $q->where('poli_id', (int) $v),
                    ],
                ],
                'sorts' => [
                    'az' => ['label' => 'Nama A–Z', 'apply' => fn (Builder $q) => $q->orderBy('nama')],
                    'za' => ['label' => 'Nama Z–A', 'apply' => fn (Builder $q) => $q->orderByDesc('nama')],
                    'newest' => ['label' => 'Terbaru',  'apply' => fn (Builder $q) => $q->orderByDesc('created_at')],
                    'oldest' => ['label' => 'Terlama',  'apply' => fn (Builder $q) => $q->orderBy('created_at')],
                ],
                'defaultSort' => 'az',
                'stats' => fn () => [
                    ['label' => 'Total Dokter',    'value' => Dokter::count(),                     'icon' => $iconUser,  'tone' => 'emerald'],
                    ['label' => 'Instalasi Aktif', 'value' => Poli::whereHas('dokters')->count(),   'icon' => $iconClip,  'tone' => 'violet'],
                    ['label' => 'Total Jadwal',    'value' => Jadwal::count(),                      'icon' => $iconCal,   'tone' => 'teal'],
                    ['label' => 'Spesialisasi',    'value' => Dokter::whereNotNull('spesialisasi')->distinct()->count('spesialisasi'), 'icon' => $iconCheck, 'tone' => 'sky'],
                ],
                'columns' => [
                    ['label' => 'Dokter',        'type' => 'profile', 'tone' => 'emerald', 'value' => fn ($m) => [$m->nama, $m->spesialisasi]],
                    ['label' => 'Instalasi',     'type' => 'badge',   'tone' => 'violet',  'value' => fn ($m) => $m->poli ? [$m->poli->nama, 'violet'] : null],
                    ['label' => 'Jadwal Praktik', 'type' => 'number', 'tone' => 'teal',    'value' => fn ($m) => (string) $m->jadwals_count],
                ],
                'export' => [
                    'headers' => ['Nama', 'Instalasi', 'Spesialisasi', 'Jumlah Jadwal'],
                    'toRow' => fn ($m) => [
                        $m->nama,
                        $m->poli?->nama ?? '',
                        $m->spesialisasi ?? '',
                        (string) $m->jadwals_count,
                    ],
                ],
            ],

            'jadwal' => [
                'label' => 'Jadwal',
                'group' => 'master',
                'permission' => 'manage jadwal',
                'description' => 'Jadwal praktik dokter per hari dan jam layanan di tiap instalasi.',
                'icon' => $iconCal,
                'tone' => 'teal',
                'available' => true,
                'hidden' => true,
                'count' => fn () => Jadwal::count(),
                'model' => Jadwal::class,
                'eager' => ['dokter.poli'],
                'withCount' => [],
                'searchHint' => 'Cari nama dokter atau instalasi...',
                'search' => function (Builder $q, string $t) {
                    $tAtas = strtoupper($t);

                    return $q->where(fn ($w) => $w
                        ->whereHas('dokter', fn ($d) => $d
                            ->whereRaw('UPPER(nama) LIKE ?', ["%{$tAtas}%"])
                            ->orWhereHas('poli', fn ($p) => $p->whereRaw('UPPER(nama) LIKE ?', ["%{$tAtas}%"]))));
                },
                'filters' => [
                    [
                        'key' => 'hari',
                        'label' => 'Semua Hari',
                        'type' => 'select',
                        'options' => fn () => array_combine(Jadwal::HARI, Jadwal::HARI),
                        'apply' => fn (Builder $q, string $v) => $q->where('hari', $v),
                    ],
                    [
                        'key' => 'poli',
                        'label' => 'Semua Instalasi',
                        'type' => 'select',
                        'options' => fn () => Poli::orderBy('nama')->pluck('nama', 'id')->all(),
                        'apply' => fn (Builder $q, string $v) => $q->whereHas('dokter', fn ($d) => $d->where('poli_id', (int) $v)),
                    ],
                ],
                'sorts' => [
                    'hari' => ['label' => 'Urutan Hari', 'apply' => fn (Builder $q) => $q
                        ->orderByRaw("CASE hari WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6 ELSE 7 END")
                        ->orderBy('jam_mulai')],
                    'jam' => ['label' => 'Jam Mulai', 'apply' => fn (Builder $q) => $q->orderBy('jam_mulai')],
                    'newest' => ['label' => 'Terbaru',   'apply' => fn (Builder $q) => $q->orderByDesc('created_at')],
                ],
                'defaultSort' => 'hari',
                'stats' => fn () => [
                    ['label' => 'Total Jadwal',        'value' => Jadwal::count(),                               'icon' => $iconCal,   'tone' => 'teal'],
                    ['label' => 'Dokter Bertugas',     'value' => Jadwal::distinct()->count('dokter_id'),         'icon' => $iconUser,  'tone' => 'emerald'],
                    ['label' => 'Instalasi Terlayani', 'value' => Poli::whereHas('dokters.jadwals')->count(),     'icon' => $iconClip,  'tone' => 'violet'],
                    ['label' => 'Hari Aktif',          'value' => Jadwal::distinct()->count('hari'),              'icon' => $iconClock, 'tone' => 'sky'],
                ],
                'columns' => [
                    ['label' => 'Dokter',     'type' => 'profile', 'tone' => 'teal', 'value' => fn ($m) => [$m->dokter?->nama ?? '—', $m->dokter?->poli?->nama]],
                    ['label' => 'Hari',       'type' => 'badge',                    'value' => fn ($m) => [$m->hari, $hariTone[$m->hari] ?? 'slate']],
                    ['label' => 'Jam Praktik', 'type' => 'mono',                     'value' => fn ($m) => $m->jam_mulai->format('H:i').' – '.$m->jam_selesai->format('H:i')],
                ],
                'export' => [
                    'headers' => ['Instalasi', 'Dokter', 'Hari', 'Jam Mulai', 'Jam Selesai'],
                    'toRow' => fn ($m) => [
                        $m->dokter?->poli?->nama ?? '',
                        $m->dokter?->nama ?? '',
                        $m->hari,
                        $m->jam_mulai->format('H:i'),
                        $m->jam_selesai->format('H:i'),
                    ],
                ],
            ],

            // ======================= BROADCASTING =======================

            'kunjungan' => [
                'label' => 'Kunjungan',
                'group' => 'broadcasting',
                'permission' => 'manage kunjungan',
                'description' => 'Riwayat kunjungan PNPP ke rumah sakit beserta keluhan dan diagnosa.',
                'icon' => $iconPin,
                'tone' => 'rose',
                'available' => true,
                'count' => fn () => Kunjungan::query()->tap($scopePoli)->count(),
                'model' => Kunjungan::class,
                'eager' => ['pnpp.satker', 'poli'],
                'withCount' => [],
                'poliScope' => $scopePoli,
                'searchHint' => 'Cari nama/NIP pasien, keluhan, atau diagnosa...',
                'search' => function (Builder $q, string $t) {
                    $tAtas = strtoupper($t);

                    return $q->where(fn ($w) => $w
                        ->where('keluhan', 'like', "%{$t}%")
                        ->orWhere('diagnosa', 'like', "%{$t}%")
                        ->orWhereHas('pnpp', fn ($p) => $p
                            ->whereRaw('UPPER(nama) LIKE ?', ["%{$tAtas}%"])
                            ->orWhere('nip', 'like', "%{$t}%")));
                },
                'filters' => [
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
                    ['label' => 'Total Kunjungan', 'value' => Kunjungan::query()->tap($scopePoli)->count(),                                                                            'icon' => $iconPin,   'tone' => 'rose'],
                    ['label' => 'Bulan Ini',       'value' => Kunjungan::whereYear('tanggal_kunjungan', now()->year)->whereMonth('tanggal_kunjungan', now()->month)->tap($scopePoli)->count(), 'icon' => $iconCal,   'tone' => 'violet'],
                    ['label' => 'Tahun Ini',       'value' => Kunjungan::whereYear('tanggal_kunjungan', now()->year)->tap($scopePoli)->count(), 'icon' => $iconClock, 'tone' => 'sky'],
                    ['label' => 'PNPP Dilayani',   'value' => Kunjungan::query()->tap($scopePoli)->distinct()->count('pnpp_id'),                   'icon' => $iconUsers, 'tone' => 'emerald'],
                ],
                'columns' => [
                    ['label' => 'Tanggal', 'type' => 'strong',                     'value' => fn ($m) => $m->tanggal_kunjungan?->translatedFormat('d M Y')],
                    ['label' => 'Pasien',  'type' => 'profile', 'tone' => 'rose',  'value' => fn ($m) => [$m->pnpp?->nama ?? '—', $m->pnpp?->nip]],
                    ['label' => 'Satker',  'type' => 'text',                       'value' => fn ($m) => $m->pnpp?->satker?->nama],
                    ['label' => 'Poli',    'type' => 'badge',   'tone' => 'sky',    'value' => fn ($m) => $m->poli ? [$m->poli->nama, 'sky'] : null],
                    ['label' => 'Keluhan', 'type' => 'text',                       'value' => fn ($m) => $m->keluhan],
                    ['label' => 'Diagnosa', 'type' => 'text',                       'value' => fn ($m) => $m->diagnosa],
                ],
                'export' => [
                    'headers' => ['Tanggal', 'Pasien', 'NIP/NRP', 'Satker', 'Poli', 'Keluhan', 'Diagnosa'],
                    'toRow' => fn ($m) => [
                        $m->tanggal_kunjungan?->format('Y-m-d') ?? '',
                        $m->pnpp?->nama ?? '',
                        $m->pnpp?->nip ?? '',
                        $m->pnpp?->satker?->nama ?? '',
                        $m->poli?->nama ?? '',
                        $m->keluhan ?? '',
                        $m->diagnosa ?? '',
                    ],
                ],
            ],

            // Laporan penjadwalan kunjungan (reminders) — sumber modul
            // Digital Reminder; pesan outreach/follow up digenerate darinya.

            'digital-reminder' => [
                'label' => 'Digital Reminder',
                'group' => 'broadcasting',
                'permission' => 'manage digital-reminder',
                'description' => 'Laporan penjadwalan kunjungan pasien (digital reminder).',
                'icon' => $iconBell,
                'tone' => 'sky',
                'available' => true,
                'count' => fn () => Reminder::query()->tap($scopePoli)->count(),
                'model' => Reminder::class,
                'eager' => ['pnpp.satker', 'poli', 'dokter'],
                'withCount' => ['messageLogs'],
                'query' => fn (Builder $q) => $q,
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
                    ['label' => 'Total Jadwal', 'value' => Reminder::query()->tap($scopePoli)->count(),                                                                      'icon' => $iconBell,  'tone' => 'sky'],
                    ['label' => 'Terjadwal',    'value' => Reminder::where('status', 'terjadwal')->tap($scopePoli)->count(),                                                       'icon' => $iconClock, 'tone' => 'violet'],
                    ['label' => 'Mendatang',    'value' => Reminder::where('status', 'terjadwal')->whereDate('tanggal', '>=', today())->tap($scopePoli)->count(),               'icon' => $iconCheck, 'tone' => 'emerald'],
                    ['label' => 'Tidak Datang', 'value' => Reminder::where('status', 'tidak_datang')->tap($scopePoli)->count(),                                                     'icon' => $iconWarn,  'tone' => 'rose'],
                ],
                'columns' => [
                    ['label' => 'Jadwal',     'type' => 'strong',                   'value' => fn ($m) => $m->tanggal?->translatedFormat('d M Y').' · '.$m->jam?->format('H:i')],
                    ['label' => 'Pasien',     'type' => 'profile', 'tone' => 'sky', 'value' => fn ($m) => [$m->pnpp?->nama ?? '—', $m->pnpp?->nip]],
                    ['label' => 'Poli',       'type' => 'text',                      'value' => fn ($m) => $m->poli?->nama],
                    ['label' => 'Dokter',     'type' => 'text',                      'value' => fn ($m) => $m->dokter?->nama ?? '—'],
                    ['label' => 'Home Visit', 'type' => 'badge',                     'value' => fn ($m) => [$m->home_visit ? 'Ya' : 'Tidak', $m->home_visit ? 'teal' : 'slate']],
                    ['label' => 'Pesan',      'type' => 'stat',  'tone' => 'violet',  'value' => fn ($m) => [(string) $m->message_logs_count, 'pesan']],
                    ['label' => 'Status',     'type' => 'badge',                     'value' => fn ($m) => [$m->status, ['terjadwal' => 'sky', 'selesai' => 'emerald', 'tidak_datang' => 'rose', 'dibatalkan' => 'amber', 'jadwal_ulang' => 'indigo'][$m->status] ?? 'slate']],
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
            ],

            // Laporan pesan keluar (message_logs) per modul broadcasting —
            // pesan digenerate dari reminders sesuai broadcast_rules.

            'outreach' => $laporanPesan([
                'jenis' => 'outreach',
                'label' => 'Outreach',
                'description' => 'Laporan pesan undangan jadwal (H-7 & H-1) yang digenerate dari penjadwalan.',
                'icon' => $iconMega,
                'tone' => 'emerald',
                'filtersExtra' => [
                    [
                        'key' => 'rule',
                        'label' => 'Semua Aturan',
                        'type' => 'select',
                        'options' => fn () => [
                            'h-7' => 'H-7', 'h-1' => 'H-1', 'manual' => 'Manual',
                        ],
                        'apply' => fn (Builder $q, string $v) => $q->where('rule', $v),
                    ],
                    [
                        'key' => 'template',
                        'label' => 'Semua Template',
                        'type' => 'select',
                        'options' => fn () => MessageTemplate::query()
                            ->whereIn('id', MessageLog::query()->jenis('outreach')->pluck('message_template_id'))
                            ->orderBy('judul')
                            ->pluck('judul', 'id')
                            ->all(),
                        'apply' => fn (Builder $q, string $v) => $q->where('message_template_id', (int) $v),
                    ],
                ],
                'stats' => fn () => [
                    ['label' => 'Total Pesan',  'value' => MessageLog::jenis('outreach')->tap($scopePoliPesan)->count(),                                 'icon' => $iconMega,  'tone' => 'emerald'],
                    ['label' => 'Dalam Proses', 'value' => MessageLog::jenis('outreach')->tap($scopePoliPesan)->whereIn('status', ['menunggu', 'mengirim'])->count(), 'icon' => $iconClock, 'tone' => 'violet'],
                    ['label' => 'Terkirim',     'value' => MessageLog::jenis('outreach')->tap($scopePoliPesan)->status('terkirim')->count(),              'icon' => $iconCheck, 'tone' => 'sky'],
                    ['label' => 'Gagal',        'value' => MessageLog::jenis('outreach')->tap($scopePoliPesan)->status('gagal')->count(),                  'icon' => $iconWarn,  'tone' => 'rose'],
                ],
                'columns' => [
                    ['label' => 'Aturan',   'type' => 'badge',  'tone' => 'amber', 'value' => fn ($m) => $m->rule ? [$m->rule, 'amber'] : null],
                    ['label' => 'Isi Pesan', 'type' => 'text',                    'value' => fn ($m) => $m->konten],
                ],
                'exportHeaders' => ['Aturan'],
                'exportRow' => fn ($m) => [$m->rule ?? ''],
            ]),

            'respon' => [
                'label' => 'Respon',
                'group' => 'broadcasting',
                'permission' => 'manage respon',
                'description' => 'Laporan balasan pasien yang masuk via webhook WhatsApp.',
                'icon' => $iconInbox,
                'tone' => 'violet',
                'available' => true,
                'count' => fn () => MessageReply::count(),
                'model' => MessageReply::class,
                'eager' => ['pnpp.satker'],
                'withCount' => [],
                'searchHint' => 'Cari nama pengirim, nomor HP, isi balasan, atau nama pasien...',
                'search' => function (Builder $q, string $t) {
                    $tAtas = strtoupper($t);

                    return $q->where(fn ($w) => $w
                        ->whereRaw('UPPER(nama) LIKE ?', ["%{$tAtas}%"])
                        ->orWhere('no_hp', 'like', "%{$t}%")
                        ->orWhere('isi_pesan', 'like', "%{$t}%")
                        ->orWhereHas('pnpp', fn ($p) => $p
                            ->whereRaw('UPPER(nama) LIKE ?', ["%{$tAtas}%"])
                            ->orWhere('nip', 'like', "%{$t}%")));
                },
                'filters' => [
                    [
                        'key' => 'jenis',
                        'label' => 'Semua Jenis Balasan',
                        'type' => 'select',
                        'options' => fn () => ['tombol' => 'Pilihan Tombol', 'teks' => 'Teks Biasa'],
                        'apply' => fn (Builder $q, string $v) => $v === 'tombol'
                            ? $q->where(fn ($w) => $w
                                ->where('payload->pesan->type', 'button')
                                ->orWhere('payload->pesan->type', 'interactive'))
                            : $q->where(fn ($w) => $w
                                ->whereNull('payload')
                                ->orWhereNotIn('payload->pesan->type', ['button', 'interactive'])),
                    ],
                    [
                        'key' => 'pilihan',
                        'label' => 'Semua Pilihan',
                        'type' => 'select',
                        'options' => fn () => MessageReply::query()
                            ->whereIn('payload->pesan->type', ['button', 'interactive'])
                            ->whereNotNull('isi_pesan')
                            ->where('isi_pesan', '!=', '')
                            ->distinct()
                            ->orderBy('isi_pesan')
                            ->pluck('isi_pesan', 'isi_pesan')
                            ->all(),
                        'apply' => fn (Builder $q, string $v) => $q->where('isi_pesan', $v),
                    ],
                    [
                        'key' => 'from',
                        'label' => 'Dari Tanggal',
                        'type' => 'date',
                        'apply' => fn (Builder $q, string $v) => $q->where('waktu_masuk', '>=', $v),
                    ],
                    [
                        'key' => 'to',
                        'label' => 'Sampai Tanggal',
                        'type' => 'date',
                        'apply' => fn (Builder $q, string $v) => $q->where('waktu_masuk', '<=', $v.' 23:59:59'),
                    ],
                    [
                        'key' => 'terdaftar',
                        'label' => 'Semua Pengirim',
                        'type' => 'select',
                        'options' => fn () => ['yes' => 'Pasien Terdaftar', 'no' => 'Nomor Tak Dikenal'],
                        'apply' => fn (Builder $q, string $v) => $v === 'yes'
                            ? $q->whereNotNull('pnpp_id')
                            : $q->whereNull('pnpp_id'),
                    ],
                ],
                'sorts' => [
                    'terbaru' => ['label' => 'Terbaru', 'apply' => fn (Builder $q) => $q->orderByDesc('waktu_masuk')],
                    'terlama' => ['label' => 'Terlama', 'apply' => fn (Builder $q) => $q->orderBy('waktu_masuk')],
                ],
                'defaultSort' => 'terbaru',
                'stats' => fn () => [
                    ['label' => 'Total Balasan',     'value' => MessageReply::count(),                                                        'icon' => $iconInbox,  'tone' => 'violet'],
                    ['label' => 'Pilihan Tombol',    'value' => MessageReply::whereIn('payload->pesan->type', ['button', 'interactive'])->count(), 'icon' => $iconTap, 'tone' => 'sky'],
                    ['label' => 'Hari Ini',          'value' => MessageReply::where('waktu_masuk', '>=', now()->startOfDay())->count(),         'icon' => $iconClock,  'tone' => 'sky'],
                    ['label' => 'Pasien Terdaftar',  'value' => MessageReply::whereNotNull('pnpp_id')->count(),                                'icon' => $iconUsers,  'tone' => 'emerald'],
                ],
                'columns' => [
                    ['label' => 'Waktu Masuk', 'type' => 'strong',                       'value' => fn ($m) => $m->waktu_masuk?->translatedFormat('d M Y H:i')],
                    ['label' => 'Pengirim',    'type' => 'profile', 'tone' => 'violet',   'value' => fn ($m) => [$m->nama ?? $m->no_hp, $m->no_hp]],
                    ['label' => 'Pasien',      'type' => 'badge',                         'value' => fn ($m) => $m->pnpp ? [$m->pnpp->nama, 'emerald'] : ['Tidak Terdaftar', 'slate']],
                    ['label' => 'Sumber',      'type' => 'badge',                         'value' => fn ($m) => in_array($m->payload['pesan']['type'] ?? null, ['button', 'interactive'], true) ? ['Tombol', 'violet'] : ($m->payload === null ? null : ['Teks', 'slate'])],
                    ['label' => 'Isi Balasan', 'type' => 'text',                          'value' => fn ($m) => $m->isi_pesan],
                ],
                'export' => [
                    'headers' => ['Waktu Masuk', 'Nama Pengirim', 'No. HP', 'Pasien PNPP', 'NIP/NRP', 'Sumber', 'Isi Balasan'],
                    'toRow' => fn ($m) => [
                        $m->waktu_masuk?->format('Y-m-d H:i') ?? '',
                        $m->pnpp?->nama ?? $m->nama ?? '',
                        $m->no_hp ?? '',
                        $m->pnpp?->nama ?? '',
                        $m->pnpp?->nip ?? '',
                        in_array($m->payload['pesan']['type'] ?? null, ['button', 'interactive'], true) ? 'Tombol' : 'Teks',
                        $m->isi_pesan ?? '',
                    ],
                ],
            ],

            'follow-up' => $laporanPesan([
                'jenis' => 'follow_up',
                'label' => 'Follow Up',
                'description' => 'Laporan tindak lanjut jadwal (H-1, hari-H, dan tidak datang).',
                'icon' => $iconPhone,
                'tone' => 'amber',
                'filtersExtra' => [
                    [
                        'key' => 'rule',
                        'label' => 'Semua Aturan',
                        'type' => 'select',
                        'options' => fn () => [
                            'h-1' => 'H-1', 'hari_h' => 'Hari-H', 'tidak_datang' => 'Tidak Datang',
                            'manual' => 'Manual',
                        ],
                        'apply' => fn (Builder $q, string $v) => $q->where('rule', $v),
                    ],
                ],
                'stats' => fn () => [
                    ['label' => 'Total Follow Up', 'value' => MessageLog::jenis('follow_up')->tap($scopePoliPesan)->count(),                        'icon' => $iconPhone, 'tone' => 'amber'],
                    ['label' => 'Dalam Proses',    'value' => MessageLog::jenis('follow_up')->tap($scopePoliPesan)->whereIn('status', ['menunggu', 'mengirim'])->count(), 'icon' => $iconClock, 'tone' => 'violet'],
                    ['label' => 'Tidak Datang',    'value' => MessageLog::jenis('follow_up')->tap($scopePoliPesan)->rule('tidak_datang')->count(),    'icon' => $iconWarn,  'tone' => 'rose'],
                    ['label' => 'Gagal',           'value' => MessageLog::jenis('follow_up')->tap($scopePoliPesan)->status('gagal')->count(),          'icon' => $iconPin,   'tone' => 'sky'],
                ],
                'columns' => [
                    ['label' => 'Aturan',   'type' => 'badge',  'tone' => 'amber', 'value' => fn ($m) => $m->rule ? [$m->rule, 'amber'] : null],
                    ['label' => 'Isi Pesan', 'type' => 'text',                    'value' => fn ($m) => $m->konten],
                ],
                'exportHeaders' => ['Aturan'],
                'exportRow' => fn ($m) => [$m->rule ?? ''],
            ]),
        ];
    }
}

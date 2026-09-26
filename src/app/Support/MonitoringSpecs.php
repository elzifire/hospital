<?php

namespace App\Support;

use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Models\Pnpp;
use App\Models\Satker;
use Illuminate\Database\Eloquent\Builder;

/**
 * Factory spec laporan + scope/helper bersama untuk modul Monitoring.
 *
 * Kelas ini menjadi "service" yang dipakai berulang oleh beberapa fitur
 * laporan:
 *  - laporanPesan() : kerangka laporan pesan keluar (Outreach & Follow Up)
 *  - referenceList(): kerangka laporan referensi master berbentuk
 *    "nama + kode + jumlah PNPP" (Satker, Penyakit Kronis, Penyakit Menahun)
 *  - scope poli / pesan & statusTone: pembatas data & warna status pengiriman.
 */
class MonitoringSpecs
{
    /**
     * Pembatasan data per poli untuk user akun poli (role "poli"):
     * hanya baris polinya sendiri yang terlihat/dihitung/di-export.
     * Dipakai untuk laporan yang punya kolom poli_id langsung
     * (kunjungan & digital-reminder).
     */
    public static function poliScope(): \Closure
    {
        return function (Builder $query): void {
            $poliId = auth()->user()?->poliId();

            if ($poliId !== null) {
                $query->where('poli_id', $poliId);
            }
        };
    }

    /**
     * Pembatasan serupa untuk pesan keluar (MessageLog): pesan tidak
     * menyimpan poli_id — scope lewat reminder pembentuknya
     * (outreach & follow-up).
     */
    public static function poliPesanScope(): \Closure
    {
        return function (Builder $query): void {
            $poliId = auth()->user()?->poliId();

            if ($poliId !== null) {
                $query->whereHas('reminder', fn ($reminder) => $reminder->where('poli_id', $poliId));
            }
        };
    }

    /**
     * Follow Up tidak boleh memuat pesan yang dibentuk dari penjadwalan
     * Home Visit — kunjungan home visit tidak masuk poin follow up.
     * Pesan manual (tanpa reminder) tetap dihitung.
     */
    public static function tanpaHomeVisitScope(): \Closure
    {
        return function (Builder $query): void {
            $query->where(fn ($q) => $q->whereNull('reminder_id')
                ->orWhereHas('reminder', fn ($r) => $r->where('home_visit', false)));
        };
    }

    /**
     * Warna status pengiriman pesan (laporan broadcasting).
     */
    public static function statusTone(): array
    {
        return [
            'menunggu' => 'slate',
            'mengirim' => 'sky',
            'terkirim' => 'emerald',
            'gagal' => 'rose',
            'dibatalkan' => 'amber',
        ];
    }

    /**
     * Persentase jumlah PNPP dari total (dipakai sub-label stat referensi).
     */
    public static function percentPnpp(int $count): string
    {
        $total = (int) Pnpp::count();

        return $total > 0
            ? round($count * 100 / max(1, $total)).'% dari total PNPP'
            : '—';
    }

    /**
     * Kerangka laporan pesan keluar per modul (jenis pesan).
     * Outreach dan Follow Up berbagi bentuk tabel, filter, dan export —
     * variasi (kolom, statistik, export) disuntik per entitas lewat $o.
     *
     * @return array<string, mixed>
     */
    public static function laporanPesan(array $o): array
    {
        $scopePoliPesan = self::poliPesanScope();
        $statusTone = self::statusTone();

        return [
            'model' => MessageLog::class,
            'eager' => ['template', 'pnpp.satker'],
            'withCount' => $o['withCount'] ?? [],
            'query' => function (Builder $q) use ($o) {
                $q->where('jenis', $o['jenis']);

                if (isset($o['queryEkstra'])) {
                    ($o['queryEkstra'])($q);
                }
            },
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
                    ['label' => 'Pasien', 'type' => 'profile', 'tone' => $o['tone'], 'value' => fn ($m) => [$m->penerima_nama, $m->penerima_no_hp]],
                    ['label' => 'Satker', 'type' => 'text', 'value' => fn ($m) => $m->pnpp?->satker?->nama],
                    ['label' => 'Template', 'type' => 'badge', 'tone' => 'sky', 'value' => fn ($m) => $m->template ? [$m->template->judul, 'sky'] : null],
                ],
                $o['columns'],
                [
                    ['label' => 'Status', 'type' => 'badge', 'value' => fn ($m) => [$m->status, $statusTone[$m->status] ?? 'slate']],
                    ['label' => 'Waktu', 'type' => 'strong', 'value' => fn ($m) => ($m->sent_at ?? $m->created_at)?->translatedFormat('d M Y H:i')],
                ]
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
    }

    /**
     * Kerangka laporan referensi master berbentuk daftar kode + nama +
     * jumlah PNPP (Satker, Penyakit Kronis, Penyakit Menahun).
     *
     * @return array<string, mixed>
     */
    public static function referenceList(array $o): array
    {
        return [
            'model' => $o['model'],
            'eager' => [],
            'withCount' => ['pnpps'],
            'searchHint' => $o['searchHint'],
            'search' => function (Builder $q, string $t) {
                $tAtas = strtoupper($t);

                return $q->where(fn ($w) => $w
                    ->whereRaw('UPPER(nama) LIKE ?', ["%{$tAtas}%"])
                    ->orWhere('kode', 'like', "%{$t}%"));
            },
            'filters' => [
                [
                    'key' => 'pnpp',
                    'label' => 'Semua '.$o['labelReferensi'],
                    'type' => 'select',
                    'options' => fn () => ['yes' => 'Punya PNPP', 'no' => 'Tanpa PNPP'],
                    'apply' => fn (Builder $q, string $v) => $v === 'yes'
                        ? $q->whereHas('pnpps')
                        : $q->whereDoesntHave('pnpps'),
                ],
            ],
            'sorts' => [
                'az' => ['label' => 'Nama A–Z', 'apply' => fn (Builder $q) => $q->orderBy('nama')],
                'za' => ['label' => 'Nama Z–A', 'apply' => fn (Builder $q) => $q->orderByDesc('nama')],
                'pnpp_desc' => ['label' => 'PNPP Terbanyak', 'apply' => fn (Builder $q) => $q->orderByDesc('pnpps_count')],
            ],
            'defaultSort' => 'az',
            'stats' => $o['stats'],
            'columns' => [
                ['label' => $o['labelKolom'], 'type' => 'profile', 'tone' => $o['tone'], 'value' => fn ($m) => [$m->nama, $m->kode]],
                ['label' => 'Kode', 'type' => 'mono', 'value' => fn ($m) => $m->kode],
                ['label' => 'PNPP Terdampak', 'type' => 'stat', 'tone' => 'sky', 'value' => fn ($m) => [(string) $m->pnpps_count, self::percentPnpp($m->pnpps_count)]],
            ],
            'export' => [
                'headers' => ['Kode', 'Nama', 'Jumlah PNPP'],
                'toRow' => fn ($m) => [$m->kode ?? '', $m->nama, (string) $m->pnpps_count],
            ],
        ];
    }
}
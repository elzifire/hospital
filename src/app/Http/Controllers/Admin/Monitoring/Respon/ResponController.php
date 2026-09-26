<?php

namespace App\Http\Controllers\Admin\Monitoring\Respon;

use App\Http\Controllers\Admin\Monitoring\ReportController;
use App\Models\MessageReply;
use App\Support\MonitoringIcons;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Laporan Respon — fitur berdiri sendiri dengan layout khusus
 * (rekap pilihan tombol, tabel & info strip kustom) meski tetap
 * memakai alur basis ReportController.
 */
class ResponController extends ReportController
{
    public static function meta(): array
    {
        return [
            'label' => 'Respon',
            'group' => 'broadcasting',
            'permission' => 'manage respon',
            'description' => 'Laporan balasan pasien yang masuk via webhook WhatsApp.',
            'icon' => MonitoringIcons::INBOX,
            'tone' => 'violet',
            'available' => true,
            'count' => fn () => MessageReply::count(),
        ];
    }

    public static function spec(): array
    {
        return [
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
                ['label' => 'Total Balasan', 'value' => MessageReply::count(), 'icon' => MonitoringIcons::INBOX, 'tone' => 'violet'],
                ['label' => 'Pilihan Tombol', 'value' => MessageReply::whereIn('payload->pesan->type', ['button', 'interactive'])->count(), 'icon' => MonitoringIcons::TAP, 'tone' => 'sky'],
                ['label' => 'Hari Ini', 'value' => MessageReply::where('waktu_masuk', '>=', now()->startOfDay())->count(), 'icon' => MonitoringIcons::CLOCK, 'tone' => 'sky'],
                ['label' => 'Pasien Terdaftar', 'value' => MessageReply::whereNotNull('pnpp_id')->count(), 'icon' => MonitoringIcons::USERS, 'tone' => 'emerald'],
            ],
            'columns' => [
                ['label' => 'Waktu Masuk', 'type' => 'strong', 'value' => fn ($m) => $m->waktu_masuk?->translatedFormat('d M Y H:i')],
                ['label' => 'Pengirim', 'type' => 'profile', 'tone' => 'violet', 'value' => fn ($m) => [$m->nama ?? $m->no_hp, $m->no_hp]],
                ['label' => 'Pasien', 'type' => 'badge', 'value' => fn ($m) => $m->pnpp ? [$m->pnpp->nama, 'emerald'] : ['Tidak Terdaftar', 'slate']],
                ['label' => 'Sumber', 'type' => 'badge', 'value' => fn ($m) => in_array($m->payload['pesan']['type'] ?? null, ['button', 'interactive'], true) ? ['Tombol', 'violet'] : ($m->payload === null ? null : ['Teks', 'slate'])],
                ['label' => 'Isi Balasan', 'type' => 'text', 'value' => fn ($m) => $m->isi_pesan],
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
        ];
    }

    /**
     * Halaman laporan respon — layout khusus dengan rekap pilihan tombol.
     */
    public function index(Request $request)
    {
        $config = array_merge(static::spec(), static::meta());

        $rows = $this->reportQuery($config, $request)
            ->paginate($this->reportPerPage($request))
            ->withQueryString();

        $stats = empty($config['stats']) ? [] : ($config['stats'])();

        return view('admin.monitoring.respon.index', [
            'entity' => static::slug(),
            'config' => $config,
            'rows' => $rows,
            'stats' => $stats,
            'filterOptions' => $this->reportFilterOptions($config),
            'rekapPilihan' => $this->rekapPilihanTombol(),
        ]);
    }

    /**
     * Rekap pilihan tombol: hitung tiap label yang diklik user lewat
     * quick-reply / interactive, diurutkan dari yang paling banyak dipilih.
     *
     * @return array<int, array{label: string, kunci: string, count: int}>
     */
    protected function rekapPilihanTombol(): array
    {
        return MessageReply::query()
            ->whereIn('payload->pesan->type', ['button', 'interactive'])
            ->whereNotNull('isi_pesan')
            ->where('isi_pesan', '!=', '')
            ->select('isi_pesan')
            ->selectRaw('COUNT(*) as jumlah')
            ->groupBy('isi_pesan')
            ->orderByDesc('jumlah')
            ->limit(20)
            ->get()
            ->map(fn ($row) => ['label' => (string) $row->isi_pesan, 'kunci' => (string) $row->isi_pesan, 'count' => (int) $row->jumlah])
            ->all();
    }
}
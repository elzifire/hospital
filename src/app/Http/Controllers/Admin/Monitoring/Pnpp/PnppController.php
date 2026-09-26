<?php

namespace App\Http\Controllers\Admin\Monitoring\Pnpp;

use App\Http\Controllers\Admin\Monitoring\ReportController;
use App\Models\Pnpp;
use App\Models\Satker;
use App\Support\MonitoringIcons;
use Illuminate\Database\Eloquent\Builder;

class PnppController extends ReportController
{
    public static function meta(): array
    {
        return [
            'label' => 'PNPP',
            'group' => 'master',
            'permission' => 'manage pnpp',
            'description' => 'Data pegawai PNPP beserta profil, penyakit, dan riwayat kunjungannya.',
            'icon' => MonitoringIcons::USERS,
            'tone' => 'sky',
            'available' => true,
            'count' => fn () => Pnpp::count(),
        ];
    }

    public static function spec(): array
    {
        return [
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
                'az' => ['label' => 'Nama A–Z', 'apply' => fn (Builder $q) => $q->orderBy('nama')],
                'za' => ['label' => 'Nama Z–A', 'apply' => fn (Builder $q) => $q->orderByDesc('nama')],
                'newest' => ['label' => 'Terbaru', 'apply' => fn (Builder $q) => $q->orderByDesc('created_at')],
                'oldest' => ['label' => 'Terlama', 'apply' => fn (Builder $q) => $q->orderBy('created_at')],
                'kunjungan' => ['label' => 'Kunjungan Terbanyak', 'apply' => fn (Builder $q) => $q->orderByDesc('kunjungans_count')],
            ],
            'defaultSort' => 'az',
            'stats' => fn () => [
                ['label' => 'Total PNPP', 'value' => Pnpp::count(), 'icon' => MonitoringIcons::USERS, 'tone' => 'sky'],
                ['label' => 'Penyakit Kronis', 'value' => Pnpp::whereHas('penyakit')->count(), 'icon' => MonitoringIcons::HEART, 'tone' => 'rose'],
                ['label' => 'Penyakit Menahun', 'value' => Pnpp::whereHas('penyakitMenahun')->count(), 'icon' => MonitoringIcons::WARN, 'tone' => 'amber'],
                ['label' => 'Status Aktif', 'value' => Pnpp::where('status_aktif', 'aktif')->count(), 'icon' => MonitoringIcons::CHECK, 'tone' => 'emerald'],
            ],
            'columns' => [
                ['label' => 'PNPP', 'type' => 'profile', 'tone' => 'sky', 'value' => fn ($m) => [$m->nama, $m->nip]],
                ['label' => 'No. BPJS', 'type' => 'mono', 'value' => fn ($m) => $m->no_bpjs],
                ['label' => 'Satker', 'type' => 'text', 'value' => fn ($m) => $m->satker?->nama],
                ['label' => 'Usia', 'type' => 'strong', 'value' => fn ($m) => $m->usia !== null ? $m->usia.' th' : null],
                ['label' => 'JK', 'type' => 'badge', 'value' => fn ($m) => $m->jenis_kelamin ? [$m->jenis_kelamin, $m->jenis_kelamin === 'L' ? 'sky' : 'rose'] : null],
                ['label' => 'Penyakit Kronis', 'type' => 'tags', 'tone' => 'amber', 'value' => fn ($m) => $m->penyakit->pluck('nama')->all()],
                ['label' => 'Penyakit Menahun', 'type' => 'tags', 'tone' => 'emerald', 'value' => fn ($m) => $m->penyakitMenahun->pluck('nama')->all()],
                ['label' => 'Status', 'type' => 'badge', 'value' => fn ($m) => $m->status_aktif ? [ucfirst($m->status_aktif), $m->status_aktif === 'aktif' ? 'emerald' : 'slate'] : null],
                ['label' => 'Kunjungan', 'type' => 'stat', 'tone' => 'sky', 'value' => fn ($m) => [
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
        ];
    }
}
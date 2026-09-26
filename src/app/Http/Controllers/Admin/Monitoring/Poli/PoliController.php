<?php

namespace App\Http\Controllers\Admin\Monitoring\Poli;

use App\Http\Controllers\Admin\Monitoring\ReportController;
use App\Models\Dokter;
use App\Models\Jadwal;
use App\Models\Poli;
use App\Support\MonitoringIcons;
use Illuminate\Database\Eloquent\Builder;

class PoliController extends ReportController
{
    public static function meta(): array
    {
        return [
            'label' => 'Instalasi',
            'group' => 'master',
            'permission' => 'manage poli',
            'description' => 'Daftar instalasi/poli layanan beserta dokter yang bertugas.',
            'icon' => MonitoringIcons::CLIP,
            'tone' => 'violet',
            'available' => true,
            'count' => fn () => Poli::count(),
        ];
    }

    public static function spec(): array
    {
        return [
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
                'az' => ['label' => 'Nama A–Z', 'apply' => fn (Builder $q) => $q->orderBy('nama')],
                'za' => ['label' => 'Nama Z–A', 'apply' => fn (Builder $q) => $q->orderByDesc('nama')],
                'dokter_desc' => ['label' => 'Dokter Terbanyak', 'apply' => fn (Builder $q) => $q->orderByDesc('dokters_count')],
            ],
            'defaultSort' => 'az',
            'stats' => fn () => [
                ['label' => 'Total Instalasi', 'value' => Poli::count(), 'icon' => MonitoringIcons::CLIP, 'tone' => 'violet'],
                ['label' => 'Total Dokter', 'value' => Dokter::count(), 'icon' => MonitoringIcons::USER, 'tone' => 'emerald'],
                ['label' => 'Punya Dokter', 'value' => Poli::whereHas('dokters')->count(), 'icon' => MonitoringIcons::CHECK, 'tone' => 'sky'],
                ['label' => 'Jadwal Dibuat', 'value' => Jadwal::count(), 'icon' => MonitoringIcons::CAL, 'tone' => 'teal'],
            ],
            'columns' => [
                ['label' => 'Instalasi', 'type' => 'profile', 'tone' => 'violet', 'value' => fn ($m) => [$m->nama, $m->kode]],
                ['label' => 'Kode', 'type' => 'mono', 'value' => fn ($m) => $m->kode],
                ['label' => 'Dokter', 'type' => 'number', 'tone' => 'emerald', 'value' => fn ($m) => (string) $m->dokters_count],
            ],
            'export' => [
                'headers' => ['Kode', 'Nama', 'Jumlah Dokter'],
                'toRow' => fn ($m) => [$m->kode ?? '', $m->nama, (string) $m->dokters_count],
            ],
        ];
    }
}
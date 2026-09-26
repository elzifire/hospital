<?php

namespace App\Http\Controllers\Admin\Monitoring\Satker;

use App\Http\Controllers\Admin\Monitoring\ReportController;
use App\Models\Pnpp;
use App\Models\Satker;
use App\Support\MonitoringIcons;
use App\Support\MonitoringSpecs;

class SatkerController extends ReportController
{
    public static function meta(): array
    {
        return [
            'label' => 'Satker',
            'group' => 'master',
            'permission' => 'manage satker',
            'description' => 'Daftar satuan kerja asal PNPP beserta jumlah anggotanya.',
            'icon' => MonitoringIcons::BUILD,
            'tone' => 'indigo',
            'available' => true,
            'count' => fn () => Satker::count(),
        ];
    }

    public static function spec(): array
    {
        return MonitoringSpecs::referenceList([
            'model' => Satker::class,
            'searchHint' => 'Cari nama atau kode satker...',
            'labelReferensi' => 'Satker',
            'labelKolom' => 'Satker',
            'tone' => 'indigo',
            'stats' => fn () => [
                ['label' => 'Total Satker', 'value' => Satker::count(), 'icon' => MonitoringIcons::BUILD, 'tone' => 'indigo'],
                ['label' => 'Total PNPP', 'value' => Pnpp::count(), 'icon' => MonitoringIcons::USERS, 'tone' => 'sky'],
                ['label' => 'Punya PNPP', 'value' => Satker::whereHas('pnpps')->count(), 'icon' => MonitoringIcons::CHECK, 'tone' => 'emerald'],
                ['label' => 'Tanpa PNPP', 'value' => Satker::whereDoesntHave('pnpps')->count(), 'icon' => MonitoringIcons::WARN, 'tone' => 'amber'],
            ],
        ]);
    }
}
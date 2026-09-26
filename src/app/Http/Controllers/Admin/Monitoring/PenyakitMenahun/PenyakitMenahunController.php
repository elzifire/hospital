<?php

namespace App\Http\Controllers\Admin\Monitoring\PenyakitMenahun;

use App\Http\Controllers\Admin\Monitoring\ReportController;
use App\Models\PenyakitMenahun;
use App\Models\Pnpp;
use App\Support\MonitoringIcons;
use App\Support\MonitoringSpecs;

class PenyakitMenahunController extends ReportController
{
    public static function meta(): array
    {
        return [
            'label' => 'Penyakit Menahun',
            'group' => 'master',
            'permission' => 'manage penyakit',
            'description' => 'Daftar penyakit menahun dan jumlah PNPP yang terdampak.',
            'icon' => MonitoringIcons::WARN,
            'tone' => 'amber',
            'available' => true,
            'count' => fn () => PenyakitMenahun::count(),
        ];
    }

    public static function spec(): array
    {
        return MonitoringSpecs::referenceList([
            'model' => PenyakitMenahun::class,
            'searchHint' => 'Cari nama atau kode penyakit...',
            'labelReferensi' => 'Penyakit',
            'labelKolom' => 'Penyakit',
            'tone' => 'amber',
            'stats' => fn () => [
                ['label' => 'Total Penyakit', 'value' => PenyakitMenahun::count(), 'icon' => MonitoringIcons::WARN, 'tone' => 'amber'],
                ['label' => 'PNPP Terdampak', 'value' => Pnpp::whereHas('penyakitMenahun')->count(), 'icon' => MonitoringIcons::USERS, 'tone' => 'sky'],
                ['label' => 'Tanpa Pasien', 'value' => PenyakitMenahun::whereDoesntHave('pnpps')->count(), 'icon' => MonitoringIcons::CHECK, 'tone' => 'emerald'],
                ['label' => 'PNPP Terbanyak', 'value' => PenyakitMenahun::withCount('pnpps')->orderByDesc('pnpps_count')->value('nama') ?? '—', 'icon' => MonitoringIcons::HEART, 'tone' => 'rose'],
            ],
        ]);
    }
}
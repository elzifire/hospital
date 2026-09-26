<?php

namespace App\Http\Controllers\Admin\Monitoring\Penyakit;

use App\Http\Controllers\Admin\Monitoring\ReportController;
use App\Models\PenyakitKronis;
use App\Models\Pnpp;
use App\Support\MonitoringIcons;
use App\Support\MonitoringSpecs;

class PenyakitController extends ReportController
{
    public static function meta(): array
    {
        return [
            'label' => 'Penyakit Kronis',
            'group' => 'master',
            'permission' => 'manage penyakit',
            'description' => 'Daftar penyakit kronis dan jumlah PNPP yang terdampak.',
            'icon' => MonitoringIcons::HEART,
            'tone' => 'rose',
            'available' => true,
            'count' => fn () => PenyakitKronis::count(),
        ];
    }

    public static function spec(): array
    {
        return MonitoringSpecs::referenceList([
            'model' => PenyakitKronis::class,
            'searchHint' => 'Cari nama atau kode penyakit...',
            'labelReferensi' => 'Penyakit',
            'labelKolom' => 'Penyakit',
            'tone' => 'rose',
            'stats' => fn () => [
                ['label' => 'Total Penyakit', 'value' => PenyakitKronis::count(), 'icon' => MonitoringIcons::HEART, 'tone' => 'rose'],
                ['label' => 'PNPP Terdampak', 'value' => Pnpp::whereHas('penyakit')->count(), 'icon' => MonitoringIcons::USERS, 'tone' => 'sky'],
                ['label' => 'Tanpa Pasien', 'value' => PenyakitKronis::whereDoesntHave('pnpps')->count(), 'icon' => MonitoringIcons::WARN, 'tone' => 'amber'],
                ['label' => 'PNPP Terbanyak', 'value' => PenyakitKronis::withCount('pnpps')->orderByDesc('pnpps_count')->value('nama') ?? '—', 'icon' => MonitoringIcons::CHECK, 'tone' => 'emerald'],
            ],
        ]);
    }
}
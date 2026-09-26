<?php

namespace App\Http\Controllers\Admin\Monitoring\FollowUp;

use App\Http\Controllers\Admin\Monitoring\ReportController;
use App\Models\MessageLog;
use App\Support\MonitoringIcons;
use App\Support\MonitoringSpecs;
use Illuminate\Database\Eloquent\Builder;

class FollowUpController extends ReportController
{
    public static function meta(): array
    {
        return [
            'label' => 'Follow Up',
            'group' => 'broadcasting',
            'permission' => 'manage follow-up',
            'description' => 'Laporan tindak lanjut jadwal (H-1, hari-H, dan tidak datang).',
            'icon' => MonitoringIcons::PHONE,
            'tone' => 'amber',
            'available' => true,
            'count' => fn () => MessageLog::jenis('follow_up')
                ->tap(MonitoringSpecs::poliPesanScope())
                ->tap(MonitoringSpecs::tanpaHomeVisitScope())
                ->count(),
        ];
    }

    public static function spec(): array
    {
        return MonitoringSpecs::laporanPesan([
            'jenis' => 'follow_up',
            'label' => 'Follow Up',
            'description' => 'Laporan tindak lanjut jadwal (H-1, hari-H, dan tidak datang).',
            'icon' => MonitoringIcons::PHONE,
            'tone' => 'amber',
            'permission' => 'manage follow-up',
            'queryEkstra' => MonitoringSpecs::tanpaHomeVisitScope(),
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
                ['label' => 'Total Follow Up', 'value' => MessageLog::jenis('follow_up')->tap(MonitoringSpecs::poliPesanScope())->tap(MonitoringSpecs::tanpaHomeVisitScope())->count(), 'icon' => MonitoringIcons::PHONE, 'tone' => 'amber'],
                ['label' => 'Dalam Proses', 'value' => MessageLog::jenis('follow_up')->tap(MonitoringSpecs::poliPesanScope())->tap(MonitoringSpecs::tanpaHomeVisitScope())->whereIn('status', ['menunggu', 'mengirim'])->count(), 'icon' => MonitoringIcons::CLOCK, 'tone' => 'violet'],
                ['label' => 'Tidak Datang', 'value' => MessageLog::jenis('follow_up')->tap(MonitoringSpecs::poliPesanScope())->tap(MonitoringSpecs::tanpaHomeVisitScope())->rule('tidak_datang')->count(), 'icon' => MonitoringIcons::WARN, 'tone' => 'rose'],
                ['label' => 'Gagal', 'value' => MessageLog::jenis('follow_up')->tap(MonitoringSpecs::poliPesanScope())->tap(MonitoringSpecs::tanpaHomeVisitScope())->status('gagal')->count(), 'icon' => MonitoringIcons::PIN, 'tone' => 'sky'],
            ],
            'columns' => [
                ['label' => 'Aturan', 'type' => 'badge', 'tone' => 'amber', 'value' => fn ($m) => $m->rule ? [$m->rule, 'amber'] : null],
                ['label' => 'Isi Pesan', 'type' => 'text', 'value' => fn ($m) => $m->konten],
            ],
            'exportHeaders' => ['Aturan'],
            'exportRow' => fn ($m) => [$m->rule ?? ''],
        ]);
    }
}
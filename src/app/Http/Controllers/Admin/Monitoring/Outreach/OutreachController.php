<?php

namespace App\Http\Controllers\Admin\Monitoring\Outreach;

use App\Http\Controllers\Admin\Monitoring\ReportController;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Support\MonitoringIcons;
use App\Support\MonitoringSpecs;
use Illuminate\Database\Eloquent\Builder;

class OutreachController extends ReportController
{
    public static function meta(): array
    {
        return [
            'label' => 'Outreach',
            'group' => 'broadcasting',
            'permission' => 'manage outreach',
            'description' => 'Laporan pesan undangan jadwal (H-7 & H-1) yang digenerate dari penjadwalan.',
            'icon' => MonitoringIcons::MEGA,
            'tone' => 'emerald',
            'available' => true,
            'count' => fn () => MessageLog::jenis('outreach')->tap(MonitoringSpecs::poliPesanScope())->count(),
        ];
    }

    public static function spec(): array
    {
        return MonitoringSpecs::laporanPesan([
            'jenis' => 'outreach',
            'label' => 'Outreach',
            'description' => 'Laporan pesan undangan jadwal (H-7 & H-1) yang digenerate dari penjadwalan.',
            'icon' => MonitoringIcons::MEGA,
            'tone' => 'emerald',
            'permission' => 'manage outreach',
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
                ['label' => 'Total Pesan', 'value' => MessageLog::jenis('outreach')->tap(MonitoringSpecs::poliPesanScope())->count(), 'icon' => MonitoringIcons::MEGA, 'tone' => 'emerald'],
                ['label' => 'Dalam Proses', 'value' => MessageLog::jenis('outreach')->tap(MonitoringSpecs::poliPesanScope())->whereIn('status', ['menunggu', 'mengirim'])->count(), 'icon' => MonitoringIcons::CLOCK, 'tone' => 'violet'],
                ['label' => 'Terkirim', 'value' => MessageLog::jenis('outreach')->tap(MonitoringSpecs::poliPesanScope())->status('terkirim')->count(), 'icon' => MonitoringIcons::CHECK, 'tone' => 'sky'],
                ['label' => 'Gagal', 'value' => MessageLog::jenis('outreach')->tap(MonitoringSpecs::poliPesanScope())->status('gagal')->count(), 'icon' => MonitoringIcons::WARN, 'tone' => 'rose'],
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
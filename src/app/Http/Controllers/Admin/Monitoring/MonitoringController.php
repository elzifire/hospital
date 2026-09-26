<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;

class MonitoringController extends Controller
{
    /**
     * Hub Monitoring: kartu pilihan laporan yang dikelompokkan
     * seperti grouping sidebar (Data Master, Broadcasting, dst.).
     * Daftar fitur laporan diambil dari ReportController::features().
     */
    public function index()
    {
        $user = auth()->user();

        $groups = [];

        foreach (self::groups() as $key => $label) {
            $cards = collect(ReportController::features())
                ->map(fn ($class, $entity) => ['entity' => $entity] + $class::meta())
                ->where('group', $key)
                ->reject(fn ($c) => ! empty($c['permission']) && ! $user->can($c['permission']))
                ->map(fn ($c) => [
                    'entity' => $c['entity'],
                    'label' => $c['label'],
                    'description' => $c['description'],
                    'icon' => $c['icon'],
                    'tone' => $c['tone'],
                    'available' => $c['available'] ?? true,
                    'count' => ($c['count'])(),
                ])
                ->values();

            if ($cards->isNotEmpty()) {
                $groups[] = [
                    'key' => $key,
                    'label' => $label,
                    'cards' => $cards,
                ];
            }
        }

        $allCards = collect($groups)->pluck('cards')->flatten(1);

        $summary = [
            'available' => $allCards->where('available', true)->count(),
            'total' => $allCards->count(),
            'records' => (int) $allCards->where('available', true)->sum('count'),
        ];

        return view('admin.monitoring.index', compact('groups', 'summary'));
    }

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
}
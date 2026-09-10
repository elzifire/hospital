<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Support\MonitoringRegistry;

class MonitoringController extends Controller
{
    /**
     * Hub Monitoring: kartu pilihan laporan yang dikelompokkan
     * seperti grouping sidebar (Data Master, Broadcasting, dst.).
     */
    public function index()
    {
        $user = auth()->user();

        $groups = [];

        foreach (MonitoringRegistry::groups() as $key => $label) {
            $cards = collect(MonitoringRegistry::configs())
                ->filter(fn ($c) => ($c['group'] ?? null) === $key)
                ->reject(fn ($c) => $c['hidden'] ?? false)
                ->reject(fn ($c) => ! empty($c['permission']) && ! $user->can($c['permission']))
                ->map(fn ($c, $entity) => [
                    'entity' => $entity,
                    'label' => $c['label'],
                    'description' => $c['description'],
                    'icon' => $c['icon'],
                    'tone' => $c['tone'],
                    'available' => $c['available'] ?? true,
                    'count' => ! empty($c['count']) ? ($c['count'])() : null,
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
}

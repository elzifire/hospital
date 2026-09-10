<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Admin\Monitoring\Concerns\BuildsReportQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use BuildsReportQuery;

    /**
     * Halaman detail laporan per entitas:
     * read-only + pagination sisi server + pencarian + filter + sorting.
     */
    public function show(Request $request, string $entity)
    {
        $config = $this->resolveReport($entity);

        $rows = $this->reportQuery($config, $request)
            ->paginate($this->reportPerPage($request))
            ->withQueryString();

        $stats = ! empty($config['stats'])
            ? ($config['stats'])()
            : [];

        return view('admin.monitoring.report', [
            'entity' => $entity,
            'config' => $config,
            'rows' => $rows,
            'stats' => $stats,
            'filterOptions' => $this->reportFilterOptions($config),
        ]);
    }
}

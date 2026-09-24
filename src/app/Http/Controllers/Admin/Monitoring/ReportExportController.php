<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Admin\Monitoring\Concerns\BuildsReportQuery;
use App\Http\Controllers\Controller;
use App\Support\SheetHelper;
use Illuminate\Http\Request;

class ReportExportController extends Controller
{
    use BuildsReportQuery;

    /**
     * Unduh laporan sebagai Excel (.xlsx) atau CSV.
     * Export mengikuti pencarian & filter yang sedang aktif di halaman.
     */
    public function download(Request $request, string $entity)
    {
        $config = $this->resolveReport($entity);

        $format = $request->query('format') === 'csv' ? 'csv' : 'xlsx';

        // Entitas berbasis dataset (kunjungan): export memakai semua baris
        // grup yang dihasilkan sumber data modul asalnya.
        if (! empty($config['datasetAll'])) {
            $rows = ($config['datasetAll'])($request)
                ->map($config['export']['toRow'])
                ->all();
        } else {
            $rows = $this->reportQuery($config, $request)
                ->get()
                ->map($config['export']['toRow'])
                ->all();
        }

        $content = SheetHelper::content($rows, $config['export']['headers'], $format);
        $filename = 'laporan_'.$entity.'_'.date('Ymd_His');

        return $format === 'xlsx'
            ? response($content, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.xlsx"',
            ])
            : response($content, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
            ]);
    }
}

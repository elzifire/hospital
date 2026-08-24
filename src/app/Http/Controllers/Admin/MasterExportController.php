<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\MasterRegistry;
use App\Support\SheetHelper;
use Illuminate\Http\Request;

class MasterExportController extends Controller
{
    /**
     * Halaman export (info kolom + tombol unduh).
     */
    public function index(string $entity)
    {
        $this->resolve($entity);

        $config = MasterRegistry::config($entity);
        $count  = $config['model']::count();

        return view('admin.export.index', [
            'entity' => $entity,
            'config' => $config,
            'count'  => $count,
        ]);
    }

    /**
     * Unduh seluruh data sebagai Excel (.xlsx) atau CSV.
     */
    public function download(string $entity, Request $request)
    {
        $this->resolve($entity);

        $config = MasterRegistry::config($entity);
        $format = $this->format($request);

        $rows = $config['model']::with($config['eager'])
            ->orderBy('id')
            ->get()
            ->map($config['toRow'])
            ->all();

        return $this->response($entity, $config['headers'], $rows, $format, $entity . '_' . date('Ymd_His'));
    }

    /**
     * Unduh template (header + 1 baris contoh) sebagai Excel atau CSV.
     */
    public function template(string $entity, Request $request)
    {
        $this->resolve($entity);

        $config = MasterRegistry::config($entity);
        $format = $this->format($request);

        return $this->response($entity, $config['headers'], [$config['sample']], $format, 'template_' . $entity);
    }

    private function format(Request $request): string
    {
        return $request->query('format') === 'csv' ? 'csv' : 'xlsx';
    }

    private function response(string $entity, array $headers, array $rows, string $format, string $filename)
    {
        $content = SheetHelper::content($rows, $headers, $format);

        if ($format === 'xlsx') {
            return response($content, 200, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.xlsx"',
            ]);
        }

        return response($content, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ]);
    }

    private function resolve(string $entity): void
    {
        if (! MasterRegistry::has($entity)) {
            abort(404);
        }
    }
}

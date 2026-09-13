<?php

namespace App\Http\Controllers\Admin\Monitoring\Concerns;

use App\Support\MonitoringRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Shared antara ReportController & ReportExportController:
 * resolusi entitas + hak akses + pembentukan query (search, filter, sort).
 */
trait BuildsReportQuery
{
    /**
     * Resolve entitas laporan; abort 404/403 bila tidak valid.
     */
    protected function resolveReport(string $entity): array
    {
        abort_unless(MonitoringRegistry::has($entity), 404);

        $config = MonitoringRegistry::config($entity);

        abort_unless($config['available'] ?? true, 404);

        // Entitas yang disembunyikan sementara (belum dipakai) tetap tertutup.
        abort_if($config['hidden'] ?? false, 404);

        // Entitas berpermission: hanya pemegang permission fiturnya boleh masuk.
        if (! empty($config['permission'])
            && ! auth()->user()?->can($config['permission'])) {
            abort(403, 'Anda tidak memiliki akses ke laporan ini.');
        }

        return $config;
    }

    /**
     * Query dasar: eager loading + withCount + pencarian + filter + sort.
     */
    protected function reportQuery(array $config, Request $request): Builder
    {
        $query = $config['model']::query()->with($config['eager'] ?? []);

        foreach ($config['withCount'] ?? [] as $relation) {
            $query->withCount($relation);
        }

        // Pembatasan dasar query (mis. laporan pesan per jenis broadcast).
        if (! empty($config['query'])) {
            ($config['query'])($query);
        }

        // Pembatasan per poli untuk user akun poli — query laporan yang
        // punya kolom poli_id langsung (kunjungan & digital-reminder).
        if (! empty($config['poliScope'])) {
            ($config['poliScope'])($query);
        }

        if ($search = trim((string) $request->query('search'))) {
            ($config['search'])($query, $search);
        }

        foreach ($config['filters'] ?? [] as $filter) {
            $value = trim((string) $request->query($filter['key'], ''));

            if ($value !== '') {
                ($filter['apply'])($query, $value);
            }
        }

        $sorts = $config['sorts'] ?? [];
        $sortKey = (string) $request->query('sort', '');

        if (! isset($sorts[$sortKey])) {
            $sortKey = (string) ($config['defaultSort'] ?? array_key_first($sorts) ?? '');
        }

        if ($sortKey !== '' && isset($sorts[$sortKey])) {
            ($sorts[$sortKey]['apply'])($query);
        }

        return $query;
    }

    /**
     * Validasi opsi "per halaman" (pagination di sisi server).
     */
    protected function reportPerPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 10);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
    }

    /**
     * Opsi dropdown untuk filter bertipe select (dievaluasi sekali per request).
     */
    protected function reportFilterOptions(array $config): array
    {
        $options = [];

        foreach ($config['filters'] ?? [] as $filter) {
            if (($filter['type'] ?? 'select') === 'select') {
                $options[$filter['key']] = ($filter['options'])();
            }
        }

        return $options;
    }
}

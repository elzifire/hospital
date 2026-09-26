<?php

namespace App\Http\Controllers\Admin\Monitoring\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Shared antar halaman & export laporan (dipakai seluruh fitur lewat
 * ReportController): pembentukan query (search, filter, sort) dan
 * pagination sisi server dari spec milik masing-masing fitur.
 */
trait BuildsReportQuery
{
    /**
     * Query dasar yang terbangun dari pencarian + filter + pembatas scope
     * (tanpa eager load, withCount, maupun sort). Dipakai bersama halaman
     * (lalu ditambah eager/withCount/sort) dan data grafik supaya grafik
     * selalu sejalan dengan filter yang sedang aktif di layar.
     */
    protected function reportFilteredQuery(array $config, Request $request): Builder
    {
        $query = $config['model']::query();

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

        return $query;
    }

    /**
     * Query laporan lengkap: dasar + eager loading + withCount + sort.
     * Spec disuplai oleh fitur laporan masing-masing.
     */
    protected function reportQuery(array $config, Request $request): Builder
    {
        $query = $this->reportFilteredQuery($config, $request)->with($config['eager'] ?? []);

        foreach ($config['withCount'] ?? [] as $relation) {
            $query->withCount($relation);
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
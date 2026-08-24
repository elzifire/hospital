<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ImportMasterJob;
use App\Support\MasterRegistry;
use App\Support\SheetHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MasterImportController extends Controller
{
    /**
     * Halaman import (form upload + status + preview).
     */
    public function index(string $entity)
    {
        $this->resolve($entity);

        $status = null;
        $token  = session('import_token');

        if ($token && Cache::has(ImportMasterJob::cacheKey($token))) {
            $status = Cache::get(ImportMasterJob::cacheKey($token));
        }

        return view('admin.import.index', [
            'entity'  => $entity,
            'config'  => MasterRegistry::config($entity),
            'preview' => null,
            'token'   => null,
            'status'  => $status,
        ]);
    }

    /**
     * Upload file (xlsx/xls/csv), lalu tampilkan preview yang bisa diedit.
     */
    public function upload(string $entity, Request $request)
    {
        $this->resolve($entity);
        $config = MasterRegistry::config($entity);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:4096'],
        ]);

        $token = (string) Str::uuid();

        $parsed = SheetHelper::readToRows($request->file('file')->getRealPath());

        // Validasi header harus sama persis dengan template.
        $missing = array_diff($config['headers'], $parsed['headers']);
        $extra   = array_diff($parsed['headers'], $config['headers']);

        if ($missing !== [] || $extra !== []) {
            return redirect()
                ->route('admin.master.import', $entity)
                ->withErrors(['file' => 'Format kolom tidak sesuai. Unduh template untuk melihat susunan kolom yang benar.']);
        }

        $valid = 0;
        $invalid = 0;
        $previewRows = [];

        foreach ($parsed['rows'] as $i => $row) {
            $result = ($config['parse'])($row);
            $ok = empty($result['errors']);
            $ok ? $valid++ : $invalid++;

            $values = [];
            foreach ($config['headers'] as $header) {
                $values[$header] = $row[$header] ?? '';
            }

            $previewRows[] = [
                'values' => $values,
                'errors' => $result['errors'],
            ];
        }

        $preview = [
            'total'   => $valid + $invalid,
            'valid'   => $valid,
            'invalid' => $invalid,
            'rows'    => $previewRows,
        ];

        return view('admin.import.index', [
            'entity'  => $entity,
            'config'  => $config,
            'preview' => $preview,
            'token'   => $token,
            'status'  => null,
        ]);
    }

    /**
     * Konfirmasi import → simpan baris (yang sudah diedit) lalu dispatch ke queue (redis).
     */
    public function confirm(string $entity, Request $request)
    {
        $this->resolve($entity);

        $token = $request->input('token');
        $rows  = json_decode((string) $request->input('rows'), true);

        if (! $token || ! is_array($rows) || $rows === []) {
            return redirect()
                ->route('admin.master.import', $entity)
                ->withErrors(['file' => 'Data preview kosong. Silakan upload ulang.']);
        }

        Storage::disk('local')->put("imports/{$token}.rows.json", json_encode($rows));

        Cache::put(ImportMasterJob::cacheKey($token), ['status' => 'pending'], now()->addHours(2));
        ImportMasterJob::dispatch($entity, $token);

        session(['import_token' => $token]);

        return redirect()
            ->route('admin.master.import', $entity)
            ->with('success', 'Import diproses di background (queue). Refresh halaman ini untuk melihat hasilnya.');
    }

    /**
     * Batalkan preview.
     */
    public function cancel(string $entity, Request $request)
    {
        $this->resolve($entity);

        $token = $request->input('token');
        if ($token) {
            Storage::disk('local')->delete("imports/{$token}.rows.json");
        }

        session()->forget('import_token');

        return redirect()->route('admin.master.import', $entity);
    }

    private function resolve(string $entity): void
    {
        if (! MasterRegistry::has($entity)) {
            abort(404);
        }
    }
}

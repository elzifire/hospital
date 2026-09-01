<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ImportMasterJob;
use App\Jobs\PreviewImportJob;
use App\Support\MasterRegistry;
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

        $status  = null;
        $preview = null;
        $token   = session('import_token');

        if ($token && Cache::has(ImportMasterJob::cacheKey($token))) {
            $status = Cache::get(ImportMasterJob::cacheKey($token));
        }

        if ($token
            && ($status['status'] ?? null) === 'preview_ready'
            && Storage::disk('local')->exists("imports/{$token}.preview.json")) {
            $preview = json_decode(Storage::disk('local')->get("imports/{$token}.preview.json"), true);
        }

        return view('admin.import.index', [
            'entity'  => $entity,
            'config'  => MasterRegistry::config($entity),
            'preview' => $preview,
            'token'   => $preview ? $token : null,
            'status'  => $status,
        ]);
    }

    /**
     * Upload file (xlsx/xls/csv) → parsing/pratinjau diproses di background (queue).
     */
    public function upload(string $entity, Request $request)
    {
        $this->resolve($entity);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:4096'],
        ]);

        $token = (string) Str::uuid();
        $ext   = strtolower($request->file('file')->getClientOriginalExtension()) ?: 'xlsx';

        $request->file('file')->storeAs('imports', "{$token}.{$ext}", 'local');

        Cache::put(ImportMasterJob::cacheKey($token), ['status' => 'preview_pending'], now()->addHours(2));
        PreviewImportJob::dispatch($entity, $token, $ext);

        session(['import_token' => $token]);

        return redirect()
            ->route('admin.master.import', $entity)
            ->with('success', 'Pratinjau sedang diproses di background (queue). Halaman ini akan diperbarui otomatis.');
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
            foreach (Storage::disk('local')->files('imports') as $file) {
                if (str_starts_with(basename($file), $token . '.')) {
                    Storage::disk('local')->delete($file);
                }
            }
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

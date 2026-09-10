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

        $status = null;
        $preview = null;
        $token = session('import_token');

        if ($token && Cache::has(ImportMasterJob::cacheKey($token))) {
            $status = Cache::get(ImportMasterJob::cacheKey($token));
        }

        if ($token
            && ($status['status'] ?? null) === 'preview_ready'
            && Storage::disk('local')->exists("imports/{$token}.preview.json")) {
            $preview = json_decode(Storage::disk('local')->get("imports/{$token}.preview.json"), true);
        }

        return view('admin.import.index', [
            'entity' => $entity,
            'config' => MasterRegistry::config($entity),
            'preview' => $preview,
            'token' => $preview ? $token : null,
            'status' => $status,
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
        $ext = strtolower($request->file('file')->getClientOriginalExtension()) ?: 'xlsx';

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
        $status = $token ? Cache::get(ImportMasterJob::cacheKey($token)) : null;
        $total = (int) ($status['total'] ?? 0);
        $totalChunks = (int) ($status['total_chunks'] ?? 0);

        if (! $token || ($status['status'] ?? null) !== 'preview_ready' || $total === 0 || $totalChunks === 0) {
            return redirect()
                ->route('admin.master.import', $entity)
                ->withErrors(['file' => 'Data preview kosong. Silakan upload ulang.']);
        }

        $editedRows = json_decode((string) $request->input('rows'), true);
        if (is_array($editedRows) && $editedRows !== [] && count($editedRows) <= PreviewImportJob::PREVIEW_LIMIT) {
            $total = count($editedRows);
            $totalChunks = (int) ceil($total / ImportMasterJob::BATCH_SIZE);
            foreach (array_chunk($editedRows, ImportMasterJob::BATCH_SIZE) as $chunk => $rows) {
                Storage::disk('local')->put("imports/{$token}.rows.{$chunk}.json", json_encode($rows, JSON_UNESCAPED_UNICODE));
            }
        }

        Cache::put(ImportMasterJob::cacheKey($token), [
            'status' => 'pending',
            'total' => $total,
            'total_chunks' => $totalChunks,
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => [],
        ], now()->addHours(2));

        ImportMasterJob::dispatch($entity, $token, 0, $totalChunks);

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
                if (str_starts_with(basename($file), $token.'.')) {
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

        // Import terkunci permission fitur entitas terkait.
        $permission = MasterRegistry::config($entity)['permission'] ?? null;

        if ($permission && ! auth()->user()?->can($permission)) {
            abort(403, 'Anda tidak memiliki akses ke fitur ini.');
        }
    }
}

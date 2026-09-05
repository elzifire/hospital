<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Jobs\ImportTemplateJob;
use App\Jobs\PreviewTemplateImportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TemplateImportController extends Controller
{
    /**
     * Tampilan halaman import template dengan status & preview data.
     */
    public function index()
    {
        $token   = session('template_import_token');
        $status  = null;
        $preview = null;

        if ($token && Cache::has(PreviewTemplateImportJob::cacheKey($token))) {
            $status = Cache::get(PreviewTemplateImportJob::cacheKey($token));
        }

        if ($token && Storage::disk('local')->exists("imports/template_{$token}.preview.json")) {
            $preview = json_decode(Storage::disk('local')->get("imports/template_{$token}.preview.json"), true);
        }

        return view('admin.setting.import', [
            'token'   => $preview ? $token : null,
            'status'  => $status,
            'preview' => $preview,
        ]);
    }

    /**
     * Upload file excel/csv lalu dispatch ke queue (Redis) untuk pratinjau.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ]);

        $token = (string) Str::uuid();
        $ext = strtolower($request->file('file')->getClientOriginalExtension()) ?: 'xlsx';

        $request->file('file')->storeAs('imports', "template_{$token}.{$ext}", 'local');

        Cache::put(PreviewTemplateImportJob::cacheKey($token), ['status' => 'preview_pending'], now()->addHours(2));

        try {
            // Dispatch ke queue (Redis)
            PreviewTemplateImportJob::dispatch($token, $ext);
        } catch (\Throwable $e) {
            // Jika worker redis belum aktif, proses sinkron agar pengguna tidak macet
            (new PreviewTemplateImportJob($token, $ext))->handle();
        }

        session(['template_import_token' => $token]);

        return redirect()->route('admin.setting.import.index')
            ->with('success', 'File berhasil diunggah. Pratinjau data sedang diproses.');
    }

    /**
     * Konfirmasi import setelah melihat pratinjau dan mengedit baris.
     */
    public function confirm(Request $request)
    {
        $token = $request->input('token');
        $rows = json_decode((string) $request->input('rows'), true);

        if (! $token || ! is_array($rows) || $rows === []) {
            return redirect()->route('admin.setting.import.index')
                ->withErrors(['file' => 'Data pratinjau tidak ditemukan atau kosong. Silakan upload ulang file.']);
        }

        Storage::disk('local')->put("imports/template_{$token}.rows.json", json_encode($rows));

        Cache::put(ImportTemplateJob::cacheKey($token), ['status' => 'pending'], now()->addHours(2));

        try {
            // Dispatch ke queue (Redis)
            ImportTemplateJob::dispatch($token);
        } catch (\Throwable $e) {
            (new ImportTemplateJob($token))->handle();
        }

        session(['template_import_token' => $token]);

        return redirect()->route('admin.setting.import.index')
            ->with('success', 'Data sedang diimport ke sistem.');
    }

    /**
     * Batalkan sesi pratinjau import.
     */
    public function cancel(Request $request)
    {
        $token = $request->input('token') ?: session('template_import_token');

        if ($token) {
            Cache::forget(PreviewTemplateImportJob::cacheKey($token));
            Storage::disk('local')->delete([
                "imports/template_{$token}.xlsx",
                "imports/template_{$token}.xls",
                "imports/template_{$token}.csv",
                "imports/template_{$token}.preview.json",
                "imports/template_{$token}.rows.json",
            ]);
            session()->forget('template_import_token');
        }

        return redirect()->route('admin.setting.import.index')
            ->with('info', 'Sesi import dibatalkan.');
    }
}

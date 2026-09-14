<?php

namespace App\Http\Controllers\Admin;

use App\Broadcasting\PhoneFormat;
use App\Http\Controllers\Controller;
use App\Jobs\ImportMasterJob;
use App\Jobs\PreviewImportJob;
use App\Models\MessageLog;
use App\Models\MessageReply;
use App\Models\Pnpp;
use App\Models\ResponManual;
use App\Support\MasterRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ResponController extends Controller
{
    /**
     * Fitur Respon dibagi dalam tab:
     *  - balasan : balasan WhatsApp pasien yang masuk otomatis via webhook.
     *  - data    : index terpisah untuk balasan yang dicatat manual/diimpor
     *              (tabel respon_manuals — beda format, tidak tercampur).
     *  - manual  : input balasan secara manual.
     *  - import  : import balasan dari file Excel/CSV (diproses per batch di queue).
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'balasan');
        if (! in_array($tab, ['balasan', 'data', 'manual', 'import'], true)) {
            $tab = 'balasan';
        }

        $data = ['tab' => $tab];

        if ($tab === 'balasan') {
            $q = (string) $request->query('q', '');

            $balasan = MessageReply::query()
                ->with('pnpp:id,nama')
                ->when($q, fn ($query) => $query->where(
                    fn ($sub) => $sub->where('nama', 'like', "%{$q}%")
                        ->orWhere('no_hp', 'like', "%{$q}%")
                        ->orWhere('isi_pesan', 'like', "%{$q}%")
                ))
                ->orderByDesc('waktu_masuk')
                ->paginate(10)
                ->withQueryString();

            $data += [
                'balasan' => $balasan,
                'total' => MessageReply::count(),
                'hariIni' => MessageReply::whereBetween('waktu_masuk', [now()->startOfDay(), now()])->count(),
                'pasienUnik' => MessageReply::whereNotNull('pnpp_id')->distinct()->count('pnpp_id'),
                'takTerdaftar' => MessageReply::whereNull('pnpp_id')->count(),
                'filters' => ['q' => $q],
            ];
        }

        if ($tab === 'data') {
            $q = (string) $request->query('q', '');

            $dataRespon = ResponManual::query()
                ->when($q, fn ($query) => $query->where(
                    fn ($sub) => $sub->where('nama', 'like', "%{$q}%")
                        ->orWhere('nrp_nip', 'like', "%{$q}%")
                        ->orWhere('no_hp', 'like', "%{$q}%")
                        ->orWhere('satker', 'like', "%{$q}%")
                        ->orWhere('isi', 'like', "%{$q}%")
                ))
                ->orderByDesc('waktu')
                ->paginate(15)
                ->withQueryString();

            $data += [
                'dataRespon' => $dataRespon,
                'totalRespon' => ResponManual::count(),
                'totalManual' => ResponManual::where('sumber', ResponManual::SUMBER_MANUAL)->count(),
                'totalImport' => ResponManual::where('sumber', ResponManual::SUMBER_IMPORT)->count(),
                'filters' => ['q' => $q],
            ];
        }

        if ($tab === 'import') {
            [$status, $preview, $token] = $this->importState();

            $data += [
                'importStatus' => $status,
                'preview' => $preview,
                'importToken' => $preview ? $token : null,
            ];
        }

        return view('admin.respon.index', $data);
    }

    /**
     * Percakapan satu nomor: pesan keluar (broadcast) + balasan masuk,
     * digabung dalam satu garis waktu.
     */
    public function show(string $nomor)
    {
        $noHp = PhoneFormat::toWa($nomor) ?? $nomor;

        $keluar = MessageLog::query()
            ->where('penerima_no_hp', $noHp)
            ->orderBy('created_at')
            ->get()
            ->map(fn (MessageLog $log) => [
                'arah' => 'keluar',
                'isi' => $log->konten,
                'waktu' => $log->sent_at ?? $log->created_at,
                'status' => $log->status,
                'jenis' => $log->jenis,
            ]);

        $masuk = MessageReply::query()
            ->where('no_hp', $noHp)
            ->orderBy('waktu_masuk')
            ->get()
            ->map(fn (MessageReply $b) => [
                'arah' => 'masuk',
                'isi' => $b->isi_pesan,
                'waktu' => $b->waktu_masuk,
                'nama' => $b->nama,
            ]);

        $timeline = $keluar->merge($masuk)->sortBy(fn ($item) => $item['waktu'])->values();

        $pnpp = Pnpp::query()
            ->whereNotNull('no_hp')
            ->get()
            ->first(fn (Pnpp $p) => PhoneFormat::toWa($p->no_hp) === $noHp);

        return view('admin.respon.show', [
            'noHp' => $noHp,
            'pnpp' => $pnpp,
            'timeline' => $timeline,
        ]);
    }

    /**
     * Simpan balasan dari input manual (form di tab "Input Manual").
     * Formatnya berbeda dari balasan webhook: nama, nrp/nip, no_hp,
     * satker, isi — disimpan ke tabel respon_manuals yang terpisah.
     */
    public function storeManual(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['nullable', 'string', 'max:255'],
            'nrp_nip' => ['nullable', 'string', 'max:64'],
            'no_hp' => ['required', 'string', 'max:32'],
            'satker' => ['nullable', 'string', 'max:255'],
            'isi' => ['required', 'string', 'max:2000'],
            'waktu' => ['nullable', 'date'],
        ]);

        $digits = MasterRegistry::normalizeDigits($validated['no_hp']);
        $noHp = $digits !== null ? PhoneFormat::toWa($digits) : null;

        if ($noHp === null) {
            return back()
                ->withInput()
                ->withErrors(['no_hp' => 'Nomor HP tidak valid.']);
        }

        ResponManual::create([
            'nama' => filled($validated['nama'] ?? null) ? $validated['nama'] : null,
            'nrp_nip' => filled($validated['nrp_nip'] ?? null) ? $validated['nrp_nip'] : null,
            'no_hp' => $noHp,
            'satker' => filled($validated['satker'] ?? null) ? $validated['satker'] : null,
            'isi' => $validated['isi'],
            'waktu' => $validated['waktu'] ?? now(),
            'sumber' => ResponManual::SUMBER_MANUAL,
        ]);

        return redirect()
            ->route('admin.respon.index', ['tab' => 'data'])
            ->with('success', 'Balasan tersimpan sebagai input manual.');
    }

    /**
     * Hapus satu baris data respon manual/import dari tab "Data Respon".
     */
    public function destroy(ResponManual $responManual)
    {
        $responManual->delete();

        return redirect()
            ->route('admin.respon.index', ['tab' => 'data'])
            ->with('success', 'Data respon dihapus.');
    }

    /**
     * Upload file (xlsx/xls/csv) → parsing/pratinjau di background (queue),
     * lalu proses import memakai batch/chunk yang sama dengan data master.
     */
    public function importUpload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:4096'],
        ]);

        $token = (string) Str::uuid();
        $ext = strtolower($request->file('file')->getClientOriginalExtension()) ?: 'xlsx';

        $request->file('file')->storeAs('imports', "{$token}.{$ext}", 'local');

        Cache::put(ImportMasterJob::cacheKey($token), ['status' => 'preview_pending'], now()->addHours(2));
        PreviewImportJob::dispatch('respon', $token, $ext);

        session(['respon_import_token' => $token]);

        return redirect()
            ->route('admin.respon.index', ['tab' => 'import'])
            ->with('success', 'Pratinjau sedang diproses di background (queue). Halaman ini akan diperbarui otomatis.');
    }

    /**
     * Konfirmasi import → simpan baris (yang sudah diedit) lalu dispatch
     * satu job per batch ke queue (redis) agar tidak memberatkan proses.
     */
    public function importConfirm(Request $request)
    {
        $token = $request->input('token');
        $status = $token ? Cache::get(ImportMasterJob::cacheKey($token)) : null;
        $total = (int) ($status['total'] ?? 0);
        $totalChunks = (int) ($status['total_chunks'] ?? 0);

        if (! $token || ($status['status'] ?? null) !== 'preview_ready' || $total === 0 || $totalChunks === 0) {
            return redirect()
                ->route('admin.respon.index', ['tab' => 'import'])
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

        ImportMasterJob::dispatch('respon', $token, 0, $totalChunks);

        session(['respon_import_token' => $token]);

        return redirect()
            ->route('admin.respon.index', ['tab' => 'import'])
            ->with('success', 'Import diproses di background (queue). Refresh halaman ini untuk melihat hasilnya.');
    }

    /**
     * Batalkan preview/import yang belum selesai (hapus artefak file).
     */
    public function importCancel(Request $request)
    {
        $token = $request->input('token');
        if ($token) {
            foreach (Storage::disk('local')->files('imports') as $file) {
                if (str_starts_with(basename($file), $token.'.')) {
                    Storage::disk('local')->delete($file);
                }
            }
        }

        session()->forget('respon_import_token');

        return redirect()->route('admin.respon.index', ['tab' => 'import']);
    }

    private function importState(): array
    {
        $status = null;
        $preview = null;
        $token = session('respon_import_token');

        if ($token && Cache::has(ImportMasterJob::cacheKey($token))) {
            $status = Cache::get(ImportMasterJob::cacheKey($token));
        }

        if ($token
            && ($status['status'] ?? null) === 'preview_ready'
            && Storage::disk('local')->exists("imports/{$token}.preview.json")) {
            $preview = json_decode(Storage::disk('local')->get("imports/{$token}.preview.json"), true);
        }

        return [$status, $preview, $token];
    }
}

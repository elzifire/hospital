<?php

namespace App\Http\Controllers\Admin;

use App\Broadcasting\PhoneFormat;
use App\Broadcasting\WhatsApp\AntreanKirim;
use App\Http\Controllers\Controller;
use App\Jobs\ImportMasterJob;
use App\Jobs\PreviewImportJob;
use App\Models\MessageLog;
use App\Models\MessageReply;
use App\Models\Pnpp;
use App\Models\ResponManual;
use App\Support\MasterRegistry;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ResponController extends Controller
{
    /**
     * Daftar percakapan WhatsApp (mirip chat list WhatsApp).
     */
    public function index(Request $request)
    {
        [$konversasi, $stats, $filters] = $this->daftarKonversasi($request);

        return view('admin.respon.balasan', [
            'konversasi' => $konversasi,
            'total' => $stats['total'],
            'belumDibaca' => $stats['belumDibaca'],
            'hariIni' => $stats['hariIni'],
            'pasienUnik' => $stats['pasienUnik'],
            'takTerdaftar' => $stats['takTerdaftar'],
            'poliId' => $stats['poliId'],
            'filters' => $filters,
            'signature' => $this->signaturePercakapan($stats, $konversasi),
        ]);
    }

    /**
     * Polling JS (tanpa websocket): kembalikan signature + statistik +
     * HTML daftar percakapan. Frontend mengganti isi list bila berubah.
     */
    public function poll(Request $request)
    {
        [$konversasi, $stats, $filters] = $this->daftarKonversasi($request);

        return response()->json([
            'signature' => $this->signaturePercakapan($stats, $konversasi),
            'stats' => $stats,
            'html' => view('admin.respon._chatlist', [
                'konversasi' => $konversasi,
            ])->render(),
        ]);
    }

    /**
     * Data chat list + statistik, dipakai bersama oleh halaman index
     * dan endpoint polling.
     *
     * @return array{0: LengthAwarePaginator, 1: array<string, int|null>, 2: array<string, string>}
     */
    protected function daftarKonversasi(Request $request, int $perPage = 15): array
    {
        $q = (string) $request->query('q', '');
        $poliId = $request->user()?->poliId();

        $scopePoli = fn ($query) => $query->when(
            $poliId !== null,
            fn ($sub) => $sub->whereHas(
                'pnpp',
                fn ($pnpp) => $pnpp->whereHas('kunjungans', fn ($kunjungan) => $kunjungan->where('poli_id', $poliId))
            ),
        );

        $konversasi = MessageReply::query()
            ->tap($scopePoli)
            ->when($q, fn ($query) => $query->where(
                fn ($sub) => $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('no_hp', 'like', "%{$q}%")
                    ->orWhere('isi_pesan', 'like', "%{$q}%")
            ))
            ->select('no_hp')
            ->selectRaw('MAX("waktu_masuk") as waktu_terakhir')
            ->selectRaw('COUNT(*) as total_pesan')
            ->selectRaw('COALESCE(SUM(CASE WHEN "read_at" IS NULL THEN 1 ELSE 0 END), 0) as belum_dibaca')
            ->groupBy('no_hp')
            ->orderByDesc('waktu_terakhir')
            ->paginate($perPage)
            ->withQueryString();

        $nomors = $konversasi->pluck('no_hp')->all();
        $pesanTerakhir = $nomors === []
            ? collect()
            : MessageReply::query()
                ->whereIn('no_hp', $nomors)
                ->orderBy('waktu_masuk')
                ->get()
                ->groupBy('no_hp')
                ->map->last();

        $pnppByNomor = Pnpp::query()
            ->whereNotNull('no_hp')
            ->get(['id', 'nama', 'no_hp'])
            ->reduce(function (array $carry, Pnpp $p) {
                $wa = PhoneFormat::toWa($p->no_hp);
                $wa !== null && ($carry[$wa] = $p);

                return $carry;
            }, []);

        $konversasi->getCollection()->transform(function ($row) use ($pesanTerakhir, $pnppByNomor) {
            $terakhir = $pesanTerakhir[$row->no_hp] ?? null;
            $row->waktu_terakhir = $terakhir?->waktu_masuk;
            $row->isi_terakhir = $terakhir?->isi_pesan;
            $row->nama_pengirim = $terakhir?->nama;
            $row->pnpp = $row->pnpp ?? ($pnppByNomor[$row->no_hp] ?? null);

            return $row;
        });

        $stats = [
            'total' => MessageReply::query()->tap($scopePoli)->count(),
            'belumDibaca' => MessageReply::query()->tap($scopePoli)->belumDibaca()->count(),
            'hariIni' => MessageReply::query()->tap($scopePoli)->whereBetween('waktu_masuk', [now()->startOfDay(), now()])->count(),
            'pasienUnik' => MessageReply::query()->tap($scopePoli)->whereNotNull('pnpp_id')->distinct()->count('pnpp_id'),
            'takTerdaftar' => MessageReply::query()->tap($scopePoli)->whereNull('pnpp_id')->count(),
            'poliId' => $poliId,
        ];

        return [$konversasi, $stats, ['q' => $q]];
    }

    /**
     * Tanda tangan perubahan chat list: kombinasi jumlah + pesan terakhir.
     * Berubah hanya bila ada data baru → frontend memicu refresh.
     *
     * @param  array<string, int|null>  $stats
     */
    protected function signaturePercakapan(array $stats, $konversasi): string
    {
        $pertama = $konversasi->getCollection()->first();
        $waktu = $pertama?->waktu_terakhir ? optional($pertama->waktu_terakhir)->toIso8601String() : null;

        return implode(':', [
            $stats['total'],
            $stats['belumDibaca'],
            $stats['hariIni'],
            $stats['pasienUnik'],
            $stats['takTerdaftar'],
            $waktu ?? '0',
        ]);
    }

    /**
     * Index data respon manual & import (tabel respon_manuals).
     */
    public function indexData(Request $request)
    {
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

        return view('admin.respon.data', [
            'dataRespon' => $dataRespon,
            'totalRespon' => ResponManual::count(),
            'totalManual' => ResponManual::where('sumber', ResponManual::SUMBER_MANUAL)->count(),
            'totalImport' => ResponManual::where('sumber', ResponManual::SUMBER_IMPORT)->count(),
            'filters' => ['q' => $q],
        ]);
    }

    /**
     * Form input manual.
     */
    public function indexManual()
    {
        return view('admin.respon.manual');
    }

    /**
     * Import Excel/CSV.
     */
    public function indexImport(Request $request)
    {
        [$status, $preview, $token] = $this->importState();

        return view('admin.respon.import', [
            'importStatus' => $status,
            'preview' => $preview,
            'importToken' => $preview ? $token : null,
        ]);
    }

    /**
     * Percakapan satu nomor: pesan keluar (broadcast/balasan) + balasan
     * masuk, digabung dalam satu garis waktu.
     */
    public function show(Request $request, string $nomor)
    {
        $noHp = PhoneFormat::toWa($nomor) ?? $nomor;
        $this->pastikanAksesNomor($request, $noHp);

        // Membuka percakapan = membaca balasan masuk (badge merah hilang).
        MessageReply::tandaiDibaca($noHp);

        $pnpp = $this->pnppUntukNomor($noHp);

        return view('admin.respon.show', [
            'noHp' => $noHp,
            'pnpp' => $pnpp,
            'timeline' => $this->timelineData($noHp),
        ]);
    }

    /**
     * Kirim balasan langsung ke pasien (teks bebas). Dipanggil frontend
     * lewat fetch (JSON event) — tanpa websocket; hasil pengiriman sinkron.
     */
    public function balas(Request $request, string $nomor)
    {
        $validated = $request->validate([
            'isi' => ['required', 'string', 'max:5000'],
        ]);

        $noHp = PhoneFormat::toWa($nomor) ?? $nomor;
        $this->pastikanAksesNomor($request, $noHp);

        $pnpp = $this->pnppUntukNomor($noHp);

        $log = MessageLog::create([
            'jenis' => 'respon',
            'rule' => 'balasan',
            'pnpp_id' => $pnpp?->id,
            'created_by' => $request->user()?->id,
            'penerima_nama' => (string) ($pnpp?->nama ?? 'Nomor Tak Dikenal'),
            'penerima_no_hp' => $noHp,
            'konten' => $validated['isi'],
            'status' => 'menunggu',
            'provider' => (string) config('whatsapp.driver'),
            // Tanpa meta_template_name → MetaSender mengirim teks bebas
            // (bukan template), aman dari galat parameter template.
            'meta_template_name' => null,
            'template_params' => [],
        ]);

        $hasil = app(AntreanKirim::class)->kirimSinkron([$log]);
        $ok = ($hasil['terkirim'] ?? 0) > 0;
        $pesan = $ok
            ? 'Balasan terkirim ke '.$log->penerima_nama.'.'
            : 'Balasan gagal terkirim: '.($log->refresh()->error ?? 'tidak diketahui.');

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => $ok,
                'message' => $pesan,
                'log' => [
                    'id' => $log->id,
                    'arah' => 'keluar',
                    'isi' => $log->konten,
                    'status' => $log->status,
                    'waktu' => ($log->sent_at ?? $log->created_at)?->toIso8601String(),
                ],
            ]);
        }

        return back()->with($ok ? 'success' : 'error', $pesan);
    }

    /**
     * Timeline percakapan versi JSON untuk polling JS (event, tanpa
     * websocket). Signature berubah bila ada pesan baru → frontend
     * mengganti isi percakapan & memicu event 'respon:baru'.
     */
    public function timeline(Request $request, string $nomor)
    {
        $noHp = PhoneFormat::toWa($nomor) ?? $nomor;
        $this->pastikanAksesNomor($request, $noHp);

        // Percakapan sedang dibuka → balasan baru ikut ditandai dibaca
        // agar badge merah di chat list tetap akurat.
        MessageReply::tandaiDibaca($noHp);

        $timeline = $this->timelineData($noHp);

        return response()->json([
            'signature' => $timeline->count().':'.($timeline->last()['waktu']?->toIso8601String() ?? '0'),
            'html' => view('admin.respon._timeline', ['timeline' => $timeline])->render(),
        ]);
    }

    /**
     * @return Collection<int, array{arah: string, isi: string, waktu: Carbon, status?: string, jenis?: string, nama?: string}>
     */
    protected function timelineData(string $noHp): Collection
    {
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

        return $keluar->toBase()
            ->merge($masuk->toBase())
            ->sortBy(fn ($item) => $item['waktu']?->getTimestamp() ?? 0)
            ->values();
    }

    protected function pnppUntukNomor(string $noHp): ?Pnpp
    {
        return Pnpp::query()
            ->whereNotNull('no_hp')
            ->get()
            ->first(fn (Pnpp $p) => PhoneFormat::toWa($p->no_hp) === $noHp);
    }

    /**
     * Akun poli hanya boleh melihat & membalas nomor pasien polinya.
     */
    protected function pastikanAksesNomor(Request $request, string $noHp): void
    {
        $poliId = $request->user()?->poliId();

        if ($poliId === null) {
            return;
        }

        $punya = MessageReply::query()
            ->where('no_hp', $noHp)
            ->whereHas('pnpp', fn ($pnpp) => $pnpp->whereHas('kunjungans', fn ($kunjungan) => $kunjungan->where('poli_id', $poliId)))
            ->exists();

        abort_unless($punya, 403, 'Nomor ini bukan pasien poli Anda.');
    }

    /**
     * Simpan balasan dari input manual (form di halaman "Input Manual").
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
            ->route('admin.respon.data')
            ->with('success', 'Balasan tersimpan sebagai input manual.');
    }

    /**
     * Hapus satu baris data respon manual/import dari halaman "Data Respon".
     */
    public function destroy(ResponManual $responManual)
    {
        $responManual->delete();

        return redirect()
            ->route('admin.respon.data')
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
            ->route('admin.respon.import')
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
                ->route('admin.respon.import')
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
            ->route('admin.respon.import')
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

        return redirect()->route('admin.respon.import');
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

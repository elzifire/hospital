<?php

namespace App\Http\Controllers;

use App\Models\Poli;
use App\Models\RegisterPnpp;
use App\Models\Satker;
use App\Models\TujuanKunjungan;
use App\Support\MasterRegistry;
use App\Support\TextSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisterPnppController extends Controller
{
    /**
     * Form pendaftaran publik (bisa diakses tanpa login).
     */
    public function create(): View
    {
        return view('register-pnpp.create', [
            'satkers' => Satker::orderBy('nama')->get(),
            'polis' => Poli::orderBy('nama')->get(),
            'tujuanKunjungans' => TujuanKunjungan::orderBy('nama')->get(),
        ]);
    }

    /**
     * Simpan pendaftaran publik dengan status awal "belum disetujui".
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['nullable', 'string', 'max:16'],
            'nip' => ['nullable', 'string', 'max:50'],
            'jabatan' => ['required', 'string', 'max:255'],
            'satker_id' => ['nullable', 'integer', 'exists:satkers,id'],
            'satker_baru' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:255'],
            'ttl' => ['required', 'string', 'max:255'],
            'alamat' => ['required', 'string', 'max:1000'],
            'no_hp' => ['required', 'string', 'max:20'],
            'poli_dituju' => ['required', 'array', 'min:1'],
            'poli_dituju.*' => ['integer', 'exists:polis,id'],
            'tujuan_kunjungan' => ['nullable', 'array'],
            'tujuan_kunjungan.*' => ['integer', 'exists:tujuan_kunjungans,id'],
            'tujuan_lainnya' => ['nullable', 'string', 'max:255'],
            'rencana_tanggal_kunjungan' => ['required', 'date', 'after_or_equal:today'],
            'rencana_jam_kunjungan' => ['required', 'date_format:H:i'],
        ]);

        // Bersihkan teks bebas dari karakter yang tidak aman untuk database
        // ber-encoding Windows-1252 (emotikon, simbol di luar Latin1).
        $teks = ['nama', 'jabatan', 'satker_baru', 'unit', 'ttl', 'alamat', 'tujuan_lainnya'];
        foreach ($teks as $kolom) {
            $data[$kolom] = isset($data[$kolom]) && is_string($data[$kolom])
                ? TextSanitizer::win1252($data[$kolom])
                : null;
        }

        // Nama diubah ke huruf besar agar konsisten dengan pencarian
        // PostgreSQL yang case-sensitive.
        $data['nama'] = strtoupper($data['nama']);

        // Satker wajib: pilih dari daftar ATAU ketik manual.
        if (blank($data['satker_id'] ?? null) && blank($data['satker_baru'] ?? null)) {
            throw ValidationException::withMessages([
                'satker_id' => 'Pilih satker dari daftar atau ketik satker baru pada kolom di bawah.',
            ]);
        }

        // Tujuan kunjungan wajib minimal satu: pilih opsi ATAU isi "Yang lain".
        if (empty($data['tujuan_kunjungan'] ?? []) && blank($data['tujuan_lainnya'] ?? null)) {
            throw ValidationException::withMessages([
                'tujuan_kunjungan' => 'Pilih minimal satu tujuan kunjungan atau isi kolom "Yang lain".',
            ]);
        }

        // Jam kunjungan harus berada dalam jam layanan setiap poli tujuan
        // (poli tanpa jam buka/tutup dianggap buka 24 jam).
        $jamKunjungan = $data['rencana_jam_kunjungan'];
        $diLuarJam = [];

        foreach (Poli::whereIn('id', $data['poli_dituju'])->get() as $poli) {
            if ($poli->buka24Jam()) {
                continue;
            }

            $buka = $poli->jam_buka->format('H:i');
            $tutup = $poli->jam_tutup->format('H:i');

            if ($jamKunjungan < $buka || $jamKunjungan > $tutup) {
                $diLuarJam[] = "{$poli->nama} ({$buka}–{$tutup})";
            }
        }

        if ($diLuarJam !== []) {
            throw ValidationException::withMessages([
                'rencana_jam_kunjungan' => "Jam {$jamKunjungan} berada di luar jam layanan: ".implode(', ', $diLuarJam).'.',
            ]);
        }

        // Satker baru tidak ada di daftar → dibuatkan otomatis di tabel satkers.
        $satkerId = $data['satker_id'] ?? null;
        $satkerBaru = trim((string) ($data['satker_baru'] ?? ''));
        if ($satkerId === null && $satkerBaru !== '') {
            $satkerId = Satker::firstOrCreate(
                ['nama' => $satkerBaru],
                ['kode' => null],
            )->id;
        }

        // PENTING: closure DB::transaction() punya scope sendiri — variabel yang
        // dibuat di dalamnya (mis. $register) tidak otomatis tersedia di luar.
        // Jadi model yang dibuat di dalam harus di-return, lalu ditangkap di sini.
        $register = DB::transaction(function () use ($data, $satkerId): RegisterPnpp {
            $register = RegisterPnpp::create([
                'nama' => $data['nama'],
                'nik' => ! blank($data['nik'] ?? null) ? MasterRegistry::normalizeDigits($data['nik']) : null,
                'nip' => ! blank($data['nip'] ?? null) ? MasterRegistry::normalizeDigits($data['nip']) : null,
                'jabatan' => $data['jabatan'],
                'satker_id' => $satkerId,
                'unit' => $data['unit'] ?? null,
                'ttl' => $data['ttl'],
                'alamat' => $data['alamat'],
                'no_hp' => MasterRegistry::normalizePhone($data['no_hp']),
                'rencana_tanggal_kunjungan' => $data['rencana_tanggal_kunjungan'],
                'rencana_jam_kunjungan' => $data['rencana_jam_kunjungan'],
                'tujuan_lainnya' => blank($data['tujuan_lainnya'] ?? null) ? null : trim($data['tujuan_lainnya']),
                'status' => RegisterPnpp::STATUS_BELUM_DISETUJUI,
            ]);

            $register->polis()->sync($data['poli_dituju']);

            if (! empty($data['tujuan_kunjungan'])) {
                $register->tujuanKunjungans()->sync($data['tujuan_kunjungan']);
            }

            return $register;
        });

        return redirect()
            ->route('register-pnpp.success')
            ->with('success_register_id', $register->id);
    }

    /**
     * Halaman sukses yang berdiri sendiri — ditampilkan setelah pendaftaran
     * terkirim. Menampilkan ringkasan data yang tadi dikirim (diambil ulang
     * dari database lewat id yang disimpan ke session, bukan dari input form).
     */
    public function success(): View|RedirectResponse
    {
        $register = RegisterPnpp::with(['satker', 'polis', 'tujuanKunjungans'])
            ->find((int) session('success_register_id', 0));

        if (! $register) {
            return redirect()->route('register-pnpp.create');
        }

        return view('register-pnpp.index', ['register' => $register]);
    }
}
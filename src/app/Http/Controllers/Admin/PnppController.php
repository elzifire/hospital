<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pnpp;
use App\Models\PenyakitKronis;
use App\Models\Satker;
use App\Support\MasterRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PnppController extends Controller
{
    /**
     * Daftar data PNPP.
     */
    public function index(Request $request)
    {
        $query = Pnpp::with('satker', 'penyakit', 'latestKunjungan')
            ->withCount('kunjungans');

        $counts = [
            'total'     => Pnpp::count(),
            'laki'      => Pnpp::where('jenis_kelamin', 'L')->count(),
            'perempuan' => Pnpp::where('jenis_kelamin', 'P')->count(),
            'kronis'    => Pnpp::whereHas('penyakit')->count(),
        ];

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('no_bpjs', 'like', "%{$search}%");
            });
        }

        if ($satker = $request->query('satker')) {
            $query->where('satker_id', $satker);
        }

        if ($jk = $request->query('jk')) {
            $query->where('jenis_kelamin', $jk);
        }

        if ($request->query('kronis') === 'yes') {
            $query->whereHas('penyakit');
        } elseif ($request->query('kronis') === 'no') {
            $query->whereDoesntHave('penyakit');
        }

        match ($request->query('sort', 'az')) {
            'za'     => $query->orderByDesc('nama'),
            'newest' => $query->orderByDesc('created_at'),
            'oldest' => $query->orderBy('created_at'),
            default  => $query->orderBy('nama'),
        };

        $pnpps = $query->paginate((int) $request->query('per_page', 10))->withQueryString();

        $satkers = Satker::orderBy('nama')->get();

        return view('admin.pnpp.index', compact('pnpps', 'counts', 'satkers'));
    }

    /**
     * Form tambah data PNPP.
     */
    public function create()
    {
        $satkers   = Satker::orderBy('nama')->get();
        $penyakits = PenyakitKronis::orderBy('nama')->get();

        return view('admin.pnpp.create', compact('satkers', 'penyakits'));
    }

    /**
     * Simpan data PNPP baru.
     */
    public function store(Request $request)
    {
        $data = $this->validated($request);

        $pnpp = DB::transaction(function () use ($request, $data) {
            $pnpp = Pnpp::create($data);
            $pnpp->penyakit()->sync($request->input('penyakit', []));

            return $pnpp;
        });

        return redirect()->route('admin.pnpp.index')
            ->with('success', "Data PNPP \"{$pnpp->nama}\" berhasil ditambahkan.");
    }

    /**
     * Form edit data PNPP.
     */
    public function edit(Pnpp $pnpp)
    {
        $pnpp->load('penyakit');

        $satkers   = Satker::orderBy('nama')->get();
        $penyakits = PenyakitKronis::orderBy('nama')->get();

        return view('admin.pnpp.edit', compact('pnpp', 'satkers', 'penyakits'));
    }

    /**
     * Perbarui data PNPP.
     */
    public function update(Request $request, Pnpp $pnpp)
    {
        $data = $this->validated($request, $pnpp);

        DB::transaction(function () use ($request, $pnpp, $data) {
            $pnpp->update($data);
            $pnpp->penyakit()->sync($request->input('penyakit', []));
        });

        return redirect()->route('admin.pnpp.index')
            ->with('success', "Data PNPP \"{$pnpp->nama}\" berhasil diperbarui.");
    }

    /**
     * Hapus data PNPP (kunjungan ikut terhapus via cascade).
     */
    public function destroy(Pnpp $pnpp)
    {
        $nama = $pnpp->nama;
        $pnpp->delete();

        return redirect()->route('admin.pnpp.index')
            ->with('success', "Data PNPP \"{$nama}\" berhasil dihapus.");
    }

    /**
     * Halaman riwayat kunjungan untuk satu PNPP.
     */
    public function kunjungan(Pnpp $pnpp)
    {
        $pnpp->load(['satker', 'penyakit', 'kunjungans' => fn ($q) => $q->orderByDesc('tanggal_kunjungan')]);

        return view('admin.pnpp.kunjungan', compact('pnpp'));
    }

    private function validated(Request $request, ?Pnpp $pnpp = null): array
    {
        $request->merge([
            'no_bpjs' => MasterRegistry::normalizeDigits($request->input('no_bpjs')),
            'no_hp'   => MasterRegistry::normalizePhone($request->input('no_hp')),
        ]);

        return $request->validate([
            'nama'               => ['required', 'string', 'max:255'],
            'nip'                => ['nullable', 'string', 'max:50', 'unique:pnpps,nip' . ($pnpp ? ',' . $pnpp->id : '')],
            'status_kepegawaian' => ['nullable', 'string', 'max:100'],
            'pangkat'            => ['nullable', 'string', 'max:100'],
            'jabatan'            => ['nullable', 'string', 'max:100'],
            'satuan_kerja'       => ['nullable', 'string', 'max:255'],
            'bagian'             => ['nullable', 'string', 'max:100'],
            'email'              => ['nullable', 'string', 'email', 'max:255'],
            'alamat'             => ['nullable', 'string', 'max:500'],
            'no_bpjs'            => ['nullable', 'string', 'max:50', 'unique:pnpps,no_bpjs' . ($pnpp ? ',' . $pnpp->id : '')],
            'satker_id'          => ['nullable', 'integer', 'exists:satkers,id'],
            'no_hp'              => ['nullable', 'string', 'max:20'],
            'tanggal_lahir'      => ['nullable', 'date'],
            'jenis_kelamin'      => ['nullable', 'in:L,P'],
            'status_aktif'       => ['nullable', 'in:aktif,nonaktif'],
            'penyakit'           => ['nullable', 'array'],
            'penyakit.*'         => ['integer', 'exists:penyakit_kronis,id'],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pnpp;
use App\Models\PenyakitKronis;
use App\Models\Satker;
use Illuminate\Http\Request;

class PnppController extends Controller
{
    /**
     * Daftar data PNPP.
     */
    public function index()
    {
        $pnpps = Pnpp::with('satker', 'penyakit', 'latestKunjungan')
            ->withCount('kunjungans')
            ->orderBy('nama')
            ->get();

        $counts = [
            'total'     => $pnpps->count(),
            'laki'      => $pnpps->where('jenis_kelamin', 'L')->count(),
            'perempuan' => $pnpps->where('jenis_kelamin', 'P')->count(),
            'kronis'    => $pnpps->filter(fn (Pnpp $p) => $p->penyakit->isNotEmpty())->count(),
        ];

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

        $pnpp = Pnpp::create($data);
        $pnpp->penyakit()->sync($request->input('penyakit', []));

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

        $pnpp->update($data);
        $pnpp->penyakit()->sync($request->input('penyakit', []));

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
        return $request->validate([
            'nama'          => ['required', 'string', 'max:255'],
            'nip'           => ['nullable', 'string', 'max:50', 'unique:pnpps,nip' . ($pnpp ? ',' . $pnpp->id : '')],
            'no_bpjs'       => ['nullable', 'string', 'max:50', 'unique:pnpps,no_bpjs' . ($pnpp ? ',' . $pnpp->id : '')],
            'satker_id'     => ['nullable', 'integer', 'exists:satkers,id'],
            'no_hp'         => ['nullable', 'string', 'max:20'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'penyakit'      => ['nullable', 'array'],
            'penyakit.*'    => ['integer', 'exists:penyakit_kronis,id'],
        ]);
    }
}

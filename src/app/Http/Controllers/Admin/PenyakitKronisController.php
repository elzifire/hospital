<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PenyakitKronis;
use Illuminate\Http\Request;

class PenyakitKronisController extends Controller
{
    public function index()
    {
        $penyakits = PenyakitKronis::withCount('pnpps')->orderBy('nama')->get();

        return view('admin.penyakit.index', compact('penyakits'));
    }

    public function create()
    {
        return view('admin.penyakit.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode' => ['nullable', 'string', 'max:50', 'unique:penyakit_kronis,kode'],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $penyakit = PenyakitKronis::create($data);

        return redirect()->route('admin.penyakit.index')
            ->with('success', "Penyakit \"{$penyakit->nama}\" berhasil ditambahkan.");
    }

    public function edit(PenyakitKronis $penyakit)
    {
        return view('admin.penyakit.edit', compact('penyakit'));
    }

    public function update(Request $request, PenyakitKronis $penyakit)
    {
        $data = $request->validate([
            'kode' => ['nullable', 'string', 'max:50', 'unique:penyakit_kronis,kode,' . $penyakit->id],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $penyakit->update($data);

        return redirect()->route('admin.penyakit.index')
            ->with('success', "Penyakit \"{$penyakit->nama}\" berhasil diperbarui.");
    }

    public function destroy(PenyakitKronis $penyakit)
    {
        if ($penyakit->pnpps()->exists()) {
            return redirect()->route('admin.penyakit.index')
                ->with('error', "Penyakit \"{$penyakit->nama}\" masih terhubung ke data PNPP dan tidak dapat dihapus.");
        }

        $nama = $penyakit->nama;
        $penyakit->delete();

        return redirect()->route('admin.penyakit.index')
            ->with('success', "Penyakit \"{$nama}\" berhasil dihapus.");
    }
}

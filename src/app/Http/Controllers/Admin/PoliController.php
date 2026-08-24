<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poli;
use Illuminate\Http\Request;

class PoliController extends Controller
{
    public function index()
    {
        $polis = Poli::withCount('dokters')->orderBy('nama')->get();

        return view('admin.poli.index', compact('polis'));
    }

    public function create()
    {
        return view('admin.poli.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode' => ['nullable', 'string', 'max:50', 'unique:polis,kode'],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $poli = Poli::create($data);

        return redirect()->route('admin.poli.index')
            ->with('success', "Poli \"{$poli->nama}\" berhasil ditambahkan.");
    }

    public function edit(Poli $poli)
    {
        return view('admin.poli.edit', compact('poli'));
    }

    public function update(Request $request, Poli $poli)
    {
        $data = $request->validate([
            'kode' => ['nullable', 'string', 'max:50', 'unique:polis,kode,' . $poli->id],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $poli->update($data);

        return redirect()->route('admin.poli.index')
            ->with('success', "Poli \"{$poli->nama}\" berhasil diperbarui.");
    }

    public function destroy(Poli $poli)
    {
        if ($poli->dokters()->exists()) {
            return redirect()->route('admin.poli.index')
                ->with('error', "Poli \"{$poli->nama}\" masih memiliki dokter dan tidak dapat dihapus.");
        }

        $nama = $poli->nama;
        $poli->delete();

        return redirect()->route('admin.poli.index')
            ->with('success', "Poli \"{$nama}\" berhasil dihapus.");
    }
}

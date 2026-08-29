<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dokter;
use App\Models\Poli;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DokterController extends Controller
{
    public function index()
    {
        $dokters = Dokter::with('poli')->withCount('jadwals')->orderBy('nama')->get();
        $polis   = Poli::orderBy('nama')->get();

        return view('admin.dokter.index', compact('dokters', 'polis'));
    }

    public function create()
    {
        $polis = Poli::orderBy('nama')->get();

        return view('admin.dokter.create', compact('polis'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $dokter = DB::transaction(fn () => Dokter::create($data));

        return redirect()->route('admin.dokter.index')
            ->with('success', "Dokter \"{$dokter->nama}\" berhasil ditambahkan.");
    }

    public function edit(Dokter $dokter)
    {
        $polis = Poli::orderBy('nama')->get();

        return view('admin.dokter.edit', compact('dokter', 'polis'));
    }

    public function update(Request $request, Dokter $dokter)
    {
        DB::transaction(fn () => $dokter->update($this->validated($request)));

        return redirect()->route('admin.dokter.index')
            ->with('success', "Dokter \"{$dokter->nama}\" berhasil diperbarui.");
    }

    public function destroy(Dokter $dokter)
    {
        $nama = $dokter->nama;
        $dokter->delete();

        return redirect()->route('admin.dokter.index')
            ->with('success', "Dokter \"{$nama}\" beserta jadwalnya berhasil dihapus.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'poli_id'      => ['required', 'integer', 'exists:polis,id'],
            'nama'         => ['required', 'string', 'max:255'],
            'spesialisasi' => ['nullable', 'string', 'max:255'],
        ]);
    }
}

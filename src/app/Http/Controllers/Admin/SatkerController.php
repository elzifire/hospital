<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Satker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SatkerController extends Controller
{
    public function index()
    {
        $satkers = Satker::withCount('pnpps')->orderBy('nama')->get();

        return view('admin.satker.index', compact('satkers'));
    }

    public function create()
    {
        return view('admin.satker.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode' => ['nullable', 'string', 'max:50', 'unique:satkers,kode'],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $satker = DB::transaction(fn () => Satker::create($data));

        return redirect()->route('admin.satker.index')
            ->with('success', "Satker \"{$satker->nama}\" berhasil ditambahkan.");
    }

    public function edit(Satker $satker)
    {
        return view('admin.satker.edit', compact('satker'));
    }

    public function update(Request $request, Satker $satker)
    {
        $data = $request->validate([
            'kode' => ['nullable', 'string', 'max:50', 'unique:satkers,kode,' . $satker->id],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(fn () => $satker->update($data));

        return redirect()->route('admin.satker.index')
            ->with('success', "Satker \"{$satker->nama}\" berhasil diperbarui.");
    }

    public function destroy(Satker $satker)
    {
        if ($satker->pnpps()->exists()) {
            return redirect()->route('admin.satker.index')
                ->with('error', "Satker \"{$satker->nama}\" masih dipakai oleh data PNPP dan tidak dapat dihapus.");
        }

        $nama = $satker->nama;
        $satker->delete();

        return redirect()->route('admin.satker.index')
            ->with('success', "Satker \"{$nama}\" berhasil dihapus.");
    }
}

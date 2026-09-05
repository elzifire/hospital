<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PenyakitMenahun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenyakitMenahunController extends Controller
{
    private array $jenis = [
        'label' => 'Penyakit Menahun',
        'route' => 'admin.penyakit-menahun',
        'import' => 'penyakit-menahun',
    ];

    public function index()
    {
        $penyakits = PenyakitMenahun::withCount('pnpps')->orderBy('nama')->get();

        return view('admin.penyakit.index', ['penyakits' => $penyakits, 'jenis' => $this->jenis]);
    }

    public function create()
    {
        return view('admin.penyakit.create', ['jenis' => $this->jenis]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode' => ['nullable', 'string', 'max:50', 'unique:penyakit_menahuns,kode'],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $penyakit = DB::transaction(fn () => PenyakitMenahun::create($data));

        return redirect()->route($this->jenis['route'] . '.index')
            ->with('success', "Penyakit menahun \"{$penyakit->nama}\" berhasil ditambahkan.");
    }

    public function edit(PenyakitMenahun $penyakitMenahun)
    {
        return view('admin.penyakit.edit', ['penyakit' => $penyakitMenahun, 'jenis' => $this->jenis]);
    }

    public function update(Request $request, PenyakitMenahun $penyakitMenahun)
    {
        $data = $request->validate([
            'kode' => ['nullable', 'string', 'max:50', 'unique:penyakit_menahuns,kode,' . $penyakitMenahun->id],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(fn () => $penyakitMenahun->update($data));

        return redirect()->route($this->jenis['route'] . '.index')
            ->with('success', "Penyakit menahun \"{$penyakitMenahun->nama}\" berhasil diperbarui.");
    }

    public function destroy(PenyakitMenahun $penyakitMenahun)
    {
        if ($penyakitMenahun->pnpps()->exists()) {
            return redirect()->route($this->jenis['route'] . '.index')
                ->with('error', "Penyakit menahun \"{$penyakitMenahun->nama}\" masih terhubung ke data PNPP dan tidak dapat dihapus.");
        }

        $nama = $penyakitMenahun->nama;
        $penyakitMenahun->delete();

        return redirect()->route($this->jenis['route'] . '.index')
            ->with('success', "Penyakit menahun \"{$nama}\" berhasil dihapus.");
    }
}
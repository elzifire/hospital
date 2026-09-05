<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Models\DynamicVariable;
use Illuminate\Http\Request;

class DynamicVariableController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'kode'        => ['required', 'string', 'max:50', 'unique:dynamic_variables,kode'],
            'nama'        => ['required', 'string', 'max:150'],
            'sumber_data' => ['nullable', 'string', 'max:100'],
            'contoh'      => ['nullable', 'string', 'max:255'],
            'deskripsi'   => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->has('is_active');

        $variable = DynamicVariable::create($data);

        return redirect()->route('admin.setting.index', ['tab' => 'variabel'])
            ->with('success', "Variabel dinamis \"{$variable->kode}\" berhasil ditambahkan.");
    }

    public function update(Request $request, DynamicVariable $variabel)
    {
        $data = $request->validate([
            'kode'        => ['required', 'string', 'max:50', 'unique:dynamic_variables,kode,' . $variabel->id],
            'nama'        => ['required', 'string', 'max:150'],
            'sumber_data' => ['nullable', 'string', 'max:100'],
            'contoh'      => ['nullable', 'string', 'max:255'],
            'deskripsi'   => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->has('is_active');

        $variabel->update($data);

        return redirect()->route('admin.setting.index', ['tab' => 'variabel'])
            ->with('success', "Variabel dinamis \"{$variabel->kode}\" berhasil diperbarui.");
    }

    public function destroy(DynamicVariable $variabel)
    {
        $kode = $variabel->kode;
        $variabel->delete();

        return redirect()->route('admin.setting.index', ['tab' => 'variabel'])
            ->with('success', "Variabel dinamis \"{$kode}\" berhasil dihapus.");
    }
}

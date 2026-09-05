<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Models\TemplateCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TemplateCategoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'nama'      => ['required', 'string', 'max:100'],
            'warna'     => ['nullable', 'string', 'in:emerald,sky,amber,rose,purple,indigo,teal'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
        ]);

        $data['slug'] = Str::slug($data['nama']);
        $base = $data['slug'];
        $i = 1;
        while (TemplateCategory::where('slug', $data['slug'])->exists()) {
            $data['slug'] = "{$base}-" . $i++;
        }

        $data['warna'] = $data['warna'] ?? 'sky';
        $data['is_active'] = $request->has('is_active');

        $cat = TemplateCategory::create($data);

        return redirect()->route('admin.setting.index', ['tab' => 'kategori'])
            ->with('success', "Kategori template \"{$cat->nama}\" berhasil ditambahkan.");
    }

    public function update(Request $request, TemplateCategory $kategori)
    {
        $data = $request->validate([
            'nama'      => ['required', 'string', 'max:100'],
            'warna'     => ['nullable', 'string', 'in:emerald,sky,amber,rose,purple,indigo,teal'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
        ]);

        $data['warna'] = $data['warna'] ?? $kategori->warna;
        $data['is_active'] = $request->has('is_active');

        $kategori->update($data);

        return redirect()->route('admin.setting.index', ['tab' => 'kategori'])
            ->with('success', "Kategori template \"{$kategori->nama}\" berhasil diperbarui.");
    }

    public function destroy(TemplateCategory $kategori)
    {
        $nama = $kategori->nama;
        $count = $kategori->templates()->count();

        if ($count > 0) {
            // Relink templates to null or prevent delete
            $kategori->templates()->update(['template_category_id' => null]);
        }

        $kategori->delete();

        return redirect()->route('admin.setting.index', ['tab' => 'kategori'])
            ->with('success', "Kategori \"{$nama}\" berhasil dihapus.");
    }
}

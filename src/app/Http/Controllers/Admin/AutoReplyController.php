<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutoReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AutoReplyController extends Controller
{
    public function index(Request $request)
    {
        $kategori = $request->input('kategori');

        $aturans = AutoReply::query()
            ->byKategori($kategori)
            ->urut()
            ->get();

        return view('admin.auto-reply.index', compact('aturans', 'kategori'));
    }

    public function create()
    {
        return view('admin.auto-reply.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kategori' => ['required', 'string', 'max:60', 'in:'.implode(',', array_keys(AutoReply::KATEGORI))],
            'nama' => ['required', 'string', 'max:120'],
            'cara_cocok' => ['required', 'string', 'in:'.implode(',', AutoReply::CARA_COCOK)],
            'pola' => ['nullable', 'string', 'max:255'],
            'isi' => ['required', 'string'],
            'prioritas' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $data['prioritas'] = (int) ($data['prioritas'] ?? 0);

        DB::transaction(fn () => AutoReply::create($data));

        return redirect()->route('admin.auto-reply.index')
            ->with('success', "Aturan auto-reply \"{$data['nama']}\" berhasil ditambahkan.");
    }

    public function edit(AutoReply $autoReply)
    {
        return view('admin.auto-reply.edit', ['aturan' => $autoReply]);
    }

    public function update(Request $request, AutoReply $autoReply)
    {
        $data = $request->validate([
            'kategori' => ['required', 'string', 'max:60', 'in:'.implode(',', array_keys(AutoReply::KATEGORI))],
            'nama' => ['required', 'string', 'max:120'],
            'cara_cocok' => ['required', 'string', 'in:'.implode(',', AutoReply::CARA_COCOK)],
            'pola' => ['nullable', 'string', 'max:255'],
            'isi' => ['required', 'string'],
            'prioritas' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $data['prioritas'] = (int) ($data['prioritas'] ?? 0);

        DB::transaction(fn () => $autoReply->update($data));

        return redirect()->route('admin.auto-reply.index')
            ->with('success', "Aturan auto-reply \"{$data['nama']}\" berhasil diperbarui.");
    }

    public function destroy(AutoReply $autoReply)
    {
        $nama = $autoReply->nama;
        $autoReply->delete();

        return redirect()->route('admin.auto-reply.index')
            ->with('success', "Aturan auto-reply \"{$nama}\" berhasil dihapus.");
    }

    public function toggle(AutoReply $autoReply)
    {
        $autoReply->update(['aktif' => ! $autoReply->aktif]);

        $status = $autoReply->aktif ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.auto-reply.index')
            ->with('success', "Aturan \"{$autoReply->nama}\" berhasil {$status}.");
    }
}

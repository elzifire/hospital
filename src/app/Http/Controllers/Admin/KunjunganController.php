<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kunjungan;
use App\Models\Pnpp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KunjunganController extends Controller
{
    /**
     * Tambah catatan kunjungan untuk satu PNPP.
     */
    public function store(Request $request, Pnpp $pnpp)
    {
        $data = $request->validate([
            'tanggal_kunjungan' => ['required', 'date'],
            'keluhan'           => ['nullable', 'string', 'max:1000'],
            'diagnosa'          => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(fn () => $pnpp->kunjungans()->create($data));

        return redirect()->route('admin.pnpp.kunjungan', $pnpp)
            ->with('success', 'Riwayat kunjungan berhasil ditambahkan.');
    }

    /**
     * Hapus catatan kunjungan (ter-scope ke PNPP terkait).
     */
    public function destroy(Pnpp $pnpp, Kunjungan $kunjungan)
    {
        $pnpp->kunjungans()->whereKey($kunjungan->id)->delete();

        return redirect()->route('admin.pnpp.kunjungan', $pnpp)
            ->with('success', 'Riwayat kunjungan berhasil dihapus.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dokter;
use App\Models\Jadwal;
use App\Models\Poli;
use Illuminate\Http\Request;

class JadwalController extends Controller
{
    public function index()
    {
        $jadwals = Jadwal::with('dokter.poli')
            ->get()
            ->sortBy([
                fn (Jadwal $j) => array_search($j->hari, Jadwal::HARI),
                fn (Jadwal $j) => $j->jam_mulai?->format('H:i'),
            ])
            ->values();

        $polis   = Poli::orderBy('nama')->get();
        $dokters = Dokter::with('poli')->orderBy('nama')->get();

        return view('admin.jadwal.index', compact('jadwals', 'polis', 'dokters'));
    }

    public function create()
    {
        $dokters = Dokter::with('poli')->orderBy('nama')->get();

        return view('admin.jadwal.create', compact('dokters'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $jadwal = Jadwal::create($data);

        return redirect()->route('admin.jadwal.index')
            ->with('success', "Jadwal berhasil ditambahkan.");
    }

    public function edit(Jadwal $jadwal)
    {
        $dokters = Dokter::with('poli')->orderBy('nama')->get();

        return view('admin.jadwal.edit', compact('jadwal', 'dokters'));
    }

    public function update(Request $request, Jadwal $jadwal)
    {
        $jadwal->update($this->validated($request));

        return redirect()->route('admin.jadwal.index')
            ->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Jadwal $jadwal)
    {
        $jadwal->delete();

        return redirect()->route('admin.jadwal.index')
            ->with('success', 'Jadwal berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'dokter_id'   => ['required', 'integer', 'exists:dokters,id'],
            'hari'        => ['required', 'in:' . implode(',', Jadwal::HARI)],
            'jam_mulai'   => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poli;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        $data = $request->validate($this->aturanValidasi());

        $this->pastikanJamValid($request);
        $data = array_merge($data, $this->hariDiceklis($request));

        $poli = DB::transaction(fn () => Poli::create($data));

        return redirect()->route('admin.poli.index')
            ->with('success', "Poli \"{$poli->nama}\" berhasil ditambahkan.");
    }

    public function edit(Poli $poli)
    {
        return view('admin.poli.edit', compact('poli'));
    }

    public function update(Request $request, Poli $poli)
    {
        $aturan = $this->aturanValidasi();
        $aturan['kode'] = ['nullable', 'string', 'max:50', 'unique:polis,kode,'.$poli->id];

        $data = $request->validate($aturan);

        $this->pastikanJamValid($request);
        $data = array_merge($data, $this->hariDiceklis($request));

        DB::transaction(fn () => $poli->update($data));

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

    /**
     * Aturan validasi umum (kode/unique ditimpa tersendiri di update).
     */
    protected function aturanValidasi(): array
    {
        $aturan = [
            'kode' => ['nullable', 'string', 'max:50', 'unique:polis,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'jam_buka' => ['nullable', 'date_format:H:i', 'required_with:jam_tutup'],
            'jam_tutup' => ['nullable', 'date_format:H:i', 'required_with:jam_buka'],
        ];

        // Checklist hari buka: semua default false bila tidak diceklis.
        foreach (Poli::KOLOM_HARI as $nama => $kolom) {
            $aturan[$kolom] = ['nullable', 'boolean'];
        }

        return $aturan;
    }

    /**
     * Normalisasi checkbox hari buka: hanya yang terkirim yang true.
     *
     * @return array<string, bool>
     */
    protected function hariDiceklis(Request $request): array
    {
        $data = [];

        foreach (Poli::KOLOM_HARI as $nama => $kolom) {
            $data[$kolom] = $request->boolean($kolom);
        }

        return $data;
    }

    /**
     * Jam buka harus lebih awal dari jam tutup (bila keduanya diisi).
     */
    protected function pastikanJamValid(Request $request): void
    {
        $buka = $request->input('jam_buka');
        $tutup = $request->input('jam_tutup');

        if ($buka && $tutup && $tutup <= $buka) {
            throw ValidationException::withMessages([
                'jam_tutup' => 'Jam tutup harus setelah jam buka.',
            ]);
        }
    }
}

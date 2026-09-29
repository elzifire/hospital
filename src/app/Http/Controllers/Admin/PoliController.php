<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poli;
use App\Models\PoliJadwalHari;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PoliController extends Controller
{
    public function index()
    {
        $polis = Poli::withCount('dokters')->with('jadwalHari')->orderBy('nama')->get();

        return view('admin.poli.index', compact('polis'));
    }

    public function create()
    {
        return view('admin.poli.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->aturanValidasi());

        $jadwalHari = $this->validasiJadwalPerHari($request);

        $poli = DB::transaction(function () use ($data, $jadwalHari): Poli {
            $poli = Poli::create($data);
            $this->simpanJadwalPerHari($poli, $jadwalHari);

            return $poli;
        });

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
        $jadwalHari = $this->validasiJadwalPerHari($request);

        DB::transaction(function () use ($poli, $data, $jadwalHari): void {
            $poli->update($data);
            $this->simpanJadwalPerHari($poli, $jadwalHari);
        });

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
        return [
            'kode' => ['nullable', 'string', 'max:50', 'unique:polis,kode'],
            'nama' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Validasi input jadwal per hari (jadwal_hari[Senin][buka/jam_buka/jam_tutup]).
     * Minimal satu hari harus buka.
     *
     * @return array<string, array{buka: bool, jam_buka: ?string, jam_tutup: ?string}>
     */
    protected function validasiJadwalPerHari(Request $request): array
    {
        $data = $request->validate([
            'jadwal_hari' => ['required', 'array'],
            'jadwal_hari.*.buka' => ['nullable', 'boolean'],
            'jadwal_hari.*.jam_buka' => ['nullable', 'date_format:H:i'],
            'jadwal_hari.*.jam_tutup' => ['nullable', 'date_format:H:i'],
        ]);

        $hasil = [];
        $adaHariBuka = false;

        foreach (Poli::DAFTAR_HARI as $hari) {
            $buka = (bool) ($data['jadwal_hari'][$hari]['buka'] ?? false);
            $jamBuka = blank($data['jadwal_hari'][$hari]['jam_buka'] ?? null) ? null : $data['jadwal_hari'][$hari]['jam_buka'];
            $jamTutup = blank($data['jadwal_hari'][$hari]['jam_tutup'] ?? null) ? null : $data['jadwal_hari'][$hari]['jam_tutup'];

            if ($buka && $jamBuka !== null && $jamTutup !== null && $jamTutup <= $jamBuka) {
                throw ValidationException::withMessages([
                    "jadwal_hari.{$hari}.jam_tutup" => "Jam tutup hari {$hari} harus setelah jam buka.",
                ]);
            }

            $hasil[$hari] = [
                'buka' => $buka,
                'jam_buka' => $jamBuka,
                'jam_tutup' => $jamTutup,
            ];

            if ($buka) {
                $adaHariBuka = true;
            }
        }

        if (! $adaHariBuka) {
            throw ValidationException::withMessages([
                'jadwal_hari' => 'Minimal satu hari harus dibuka.',
            ]);
        }

        return $hasil;
    }

    /**
     * Simpan jadwal per hari (hapus-tulis) sekaligus selaraskan kolom pola
     * lama (jam_buka/jam_tutup + hari_*) agar konsumen peninggalan tetap
     * membaca data yang masuk akal. Tabel poli_jadwal_hari tetap sumber
     * kebenaran yang dipakai validasi & tampilan.
     *
     * @param  array<string, array{buka: bool, jam_buka: ?string, jam_tutup: ?string}>  $jadwalHari
     */
    protected function simpanJadwalPerHari(Poli $poli, array $jadwalHari): void
    {
        $hariPertamaBuka = null;

        foreach (Poli::DAFTAR_HARI as $hari) {
            $entri = $jadwalHari[$hari];

            PoliJadwalHari::updateOrCreate(
                ['poli_id' => $poli->id, 'hari' => $hari],
                [
                    'buka' => $entri['buka'],
                    'jam_buka' => $entri['jam_buka'],
                    'jam_tutup' => $entri['jam_tutup'],
                ]
            );

            if ($entri['buka'] && $hariPertamaBuka === null) {
                $hariPertamaBuka = $hari;
            }
        }

        // Selaraskan kolom lama agar tetap koheren dengan jadwal per hari.
        $jamBuka = $hariPertamaBuka !== null ? $jadwalHari[$hariPertamaBuka]['jam_buka'] : null;
        $jamTutup = $hariPertamaBuka !== null ? $jadwalHari[$hariPertamaBuka]['jam_tutup'] : null;

        $legacy = ['jam_buka' => $jamBuka, 'jam_tutup' => $jamTutup];
        foreach (Poli::KOLOM_HARI as $nama => $kolom) {
            $legacy[$kolom] = $jadwalHari[$nama]['buka'];
        }

        $poli->update($legacy);
    }
}

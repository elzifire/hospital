<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kunjungan;
use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\Satker;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Modul Kunjungan — riwayat berobat pasien PNPP. Satu pasien bisa
 * mengunjungi beberapa poli dalam satu tanggal (1 baris per poli);
 * modul mengelompokkan baris-baris itu menjadi satu "kunjungan".
 */
class KunjunganController extends Controller
{
    /**
     * Daftar kunjungan — cari nama/NIP, filter poli, periode & rentang
     * tanggal; dikelompokkan per pasien + tanggal (satu pasien bisa
     * beberapa poli dalam satu tanggal).
     */
    public function index(Request $request)
    {
        $q = (string) $request->query('q', '');
        $poliId = (string) $request->query('poli', '');
        $dari = (string) $request->query('dari', '');
        $sampai = (string) $request->query('sampai', '');
        $periode = (string) $request->query('periode', '');

        $query = Kunjungan::query()
            ->with('pnpp.satker:id,nama', 'poli:id,nama')
            ->when($q, fn ($t) => $t->whereHas('pnpp', fn ($p) => $p
                ->where('nama', 'like', "%{$q}%")
                ->orWhere('nip', 'like', "%{$q}%")
                ->orWhere('no_bpjs', 'like', "%{$q}%")))
            ->when($dari, fn ($t) => $t->where('tanggal_kunjungan', '>=', $dari))
            ->when($sampai, fn ($t) => $t->where('tanggal_kunjungan', '<=', $sampai))
            ->when(match ($periode) {
                'hari-ini' => today()->toDateString(),
                '7-hari' => today()->subDays(6)->toDateString(),
                '30-hari' => today()->subDays(29)->toDateString(),
                default => null,
            }, fn ($t, $mulai) => $t->where('tanggal_kunjungan', '>=', $mulai))
            ->when($poliId, fn ($t) => $t->where('poli_id', $poliId))
            ->orderByDesc('tanggal_kunjungan')
            ->orderBy('pnpp_id')
            ->orderBy('poli_id')
            ->get();

        // Grup = satu kunjungan (pasien + tanggal); paginate grupnya.
        $grup = $query->groupBy(fn ($k) => $k->pnpp_id.'|'.$k->tanggal_kunjungan->format('Y-m-d'))->values();
        $perPage = 10;
        $halaman = max(1, (int) $request->query('page', 1));

        $kunjungan = new LengthAwarePaginator(
            $grup->forPage($halaman, $perPage),
            $grup->count(),
            $perPage,
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('admin.kunjungan.index', [
            'kunjungan' => $kunjungan,
            'polis' => Poli::orderBy('nama')->get(['id', 'nama']),
            'filters' => ['q' => $q, 'poli' => $poliId, 'dari' => $dari, 'sampai' => $sampai, 'periode' => $periode],
            'total' => Kunjungan::count(),
            'hariIni' => Kunjungan::whereDate('tanggal_kunjungan', today())->count(),
            'bulanIni' => Kunjungan::whereYear('tanggal_kunjungan', now()->year)->whereMonth('tanggal_kunjungan', now()->month)->count(),
            'realisasi' => Kunjungan::whereNotNull('reminder_id')->count(),
            'pasien' => Kunjungan::distinct()->count('pnpp_id'),
        ]);
    }

    /**
     * Form tambah kunjungan mandiri: cari pasien lalu catat beberapa
     * poli sekaligus untuk satu tanggal.
     */
    public function create(Request $request)
    {
        $q = (string) $request->query('q', '');
        $satkerId = (string) $request->query('satker', '');

        $pnpps = Pnpp::query()
            ->with('satker:id,nama')
            ->when($q, fn ($t) => $t->where(
                fn ($sub) => $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('nip', 'like', "%{$q}%")
                    ->orWhere('no_hp', 'like', "%{$q}%")
            ))
            ->when($satkerId, fn ($t) => $t->where('satker_id', $satkerId))
            ->orderBy('nama')
            ->get(['id', 'nama', 'nip', 'no_hp', 'satker_id']);

        return view('admin.kunjungan.create', [
            'pnpps' => $pnpps,
            'satkers' => Satker::orderBy('nama')->get(['id', 'nama']),
            'polis' => Poli::orderBy('nama')->get(['id', 'nama']),
            'filters' => ['q' => $q, 'satker' => $satkerId],
        ]);
    }

    /**
     * Simpan kunjungan dari form mandiri (pasien dipilih lewat form).
     */
    public function store(Request $request)
    {
        $data = $request->validate(['pnpp_id' => ['required', Rule::exists('pnpps', 'id')]] + $this->aturanPolis());

        $pnpp = Pnpp::findOrFail($data['pnpp_id']);
        $jumlah = $this->simpanBaris($pnpp, $data['tanggal_kunjungan'], $data['polis']);

        return redirect()
            ->route('admin.kunjungan.index')
            ->with('success', "{$jumlah} catatan poli untuk \"{$pnpp->nama}\" berhasil ditambahkan.");
    }

    /**
     * Simpan kunjungan dari halaman riwayat satu pasien.
     */
    public function storeUntukPasien(Request $request, Pnpp $pnpp)
    {
        $data = $request->validate($this->aturanPolis());

        $jumlah = $this->simpanBaris($pnpp, $data['tanggal_kunjungan'], $data['polis']);

        return redirect()
            ->route('admin.pnpp.kunjungan', $pnpp)
            ->with('success', "{$jumlah} catatan poli berhasil ditambahkan.");
    }

    /**
     * Form edit satu baris poli (tanggal, poli, keluhan, diagnosa) —
     * koreksi fleksibel tanpa harus hapus lalu tambah ulang.
     */
    public function edit(Pnpp $pnpp, Kunjungan $kunjungan)
    {
        abort_unless($kunjungan->pnpp_id === $pnpp->id, 404);

        $kunjungan->load('poli:id,nama', 'reminder.poli:id,nama');

        return view('admin.kunjungan.edit', [
            'pnpp' => $pnpp->load('satker:id,nama'),
            'kunjungan' => $kunjungan,
            'polis' => Poli::orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    /**
     * Simpan koreksi baris poli. Baris yang terhubung ke penjadwalan
     * tetap terhubung (relasi reminder tidak diubah).
     */
    public function update(Request $request, Pnpp $pnpp, Kunjungan $kunjungan)
    {
        abort_unless($kunjungan->pnpp_id === $pnpp->id, 404);

        $data = $request->validate([
            'tanggal_kunjungan' => ['required', 'date'],
            'poli_id' => ['required', Rule::exists('polis', 'id')],
            'keluhan' => ['nullable', 'string', 'max:1000'],
            'diagnosa' => ['nullable', 'string', 'max:1000'],
        ]);

        $kunjungan->update($data);

        return redirect()
            ->route('admin.pnpp.kunjungan', $pnpp)
            ->with('success', 'Catatan poli berhasil diperbarui.');
    }

    /**
     * Hapus satu baris poli (ter-scope ke PNPP terkait).
     */
    public function destroy(Pnpp $pnpp, Kunjungan $kunjungan)
    {
        $pnpp->kunjungans()->whereKey($kunjungan->id)->delete();

        return redirect()
            ->route('admin.pnpp.kunjungan', $pnpp)
            ->with('success', 'Catatan poli berhasil dihapus.');
    }

    /**
     * Validasi form multi-poli: minimal satu poli, keluhan/diagnosa
     * opsional per poli.
     */
    protected function aturanPolis(): array
    {
        return [
            'tanggal_kunjungan' => ['required', 'date'],
            'polis' => ['required', 'array', 'min:1'],
            'polis.*.poli_id' => ['required', Rule::exists('polis', 'id')],
            'polis.*.keluhan' => ['nullable', 'string', 'max:1000'],
            'polis.*.diagnosa' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Buat satu baris kunjungan per poli dalam satu transaksi.
     */
    protected function simpanBaris(Pnpp $pnpp, string $tanggal, array $polis): int
    {
        return DB::transaction(function () use ($pnpp, $tanggal, $polis): int {
            foreach ($polis as $baris) {
                $pnpp->kunjungans()->create([
                    'poli_id' => $baris['poli_id'],
                    'tanggal_kunjungan' => $tanggal,
                    'keluhan' => $baris['keluhan'] ?? null,
                    'diagnosa' => $baris['diagnosa'] ?? null,
                ]);
            }

            return count($polis);
        });
    }
}

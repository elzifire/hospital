<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kunjungan;
use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\Satker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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
     * beberapa poli dalam satu tanggal). User role poli hanya melihat
     * polinya sendiri.
     */
    public function index(Request $request)
    {
        $q = (string) $request->query('q', '');
        $poliId = (string) $request->query('poli', '');
        $dari = (string) $request->query('dari', '');
        $sampai = (string) $request->query('sampai', '');
        $periode = (string) $request->query('periode', '');

        $dasar = fn (Builder $t) => $t->when($this->batasiPoli(), fn ($u) => $u->where('poli_id', $this->poliAktif()));

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
            ->when($poliId && ! $this->batasiPoli(), fn ($t) => $t->where('poli_id', $poliId))
            ->tap($dasar)
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

        $dasarCount = fn (Builder $t) => $t->when($this->batasiPoli(), fn ($u) => $u->where('poli_id', $this->poliAktif()));

        return view('admin.kunjungan.index', [
            'kunjungan' => $kunjungan,
            'polis' => $this->daftarPoliAktif(),
            'batasiPoli' => $this->batasiPoli(),
            'filters' => ['q' => $q, 'poli' => $poliId, 'dari' => $dari, 'sampai' => $sampai, 'periode' => $periode],
            'total' => Kunjungan::query()->tap($dasarCount)->count(),
            'hariIni' => Kunjungan::whereDate('tanggal_kunjungan', today())->tap($dasarCount)->count(),
            'bulanIni' => Kunjungan::whereYear('tanggal_kunjungan', now()->year)->whereMonth('tanggal_kunjungan', now()->month)->tap($dasarCount)->count(),
            'realisasi' => Kunjungan::whereNotNull('reminder_id')->tap($dasarCount)->count(),
            'pasien' => Kunjungan::query()->tap($dasarCount)->distinct()->count('pnpp_id'),
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
            'polis' => $this->daftarPoliAktif(),
            'poliTerkunci' => $this->batasiPoli(),
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
        $this->pastikanPoli($kunjungan);

        $kunjungan->load('poli:id,nama', 'reminder.poli:id,nama');

        return view('admin.kunjungan.edit', [
            'pnpp' => $pnpp->load('satker:id,nama'),
            'kunjungan' => $kunjungan,
            'polis' => $this->daftarPoliAktif(),
        ]);
    }

    /**
     * Simpan koreksi baris poli. Baris yang terhubung ke penjadwalan
     * tetap terhubung (relasi reminder tidak diubah).
     */
    public function update(Request $request, Pnpp $pnpp, Kunjungan $kunjungan)
    {
        abort_unless($kunjungan->pnpp_id === $pnpp->id, 404);
        $this->pastikanPoli($kunjungan);

        $data = $request->validate([
            'tanggal_kunjungan' => ['required', 'date'],
            'poli_id' => array_merge(['required', Rule::exists('polis', 'id')], $this->pembatasanPoli()),
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
        $this->pastikanPoli($kunjungan);

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
            'polis.*.poli_id' => array_merge(['required', Rule::exists('polis', 'id')], $this->pembatasanPoli()),
            'polis.*.keluhan' => ['nullable', 'string', 'max:1000'],
            'polis.*.diagnosa' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * ID poli pemilik akun login (role poli), null untuk admin/superadmin.
     */
    protected function poliAktif(): ?int
    {
        return auth()->user()?->poliId();
    }

    /**
     * Apakah user login adalah akun poli (terikat satu poli)?
     */
    protected function batasiPoli(): bool
    {
        return $this->poliAktif() !== null;
    }

    /**
     * Batasan validasi beban / item poli untuk user akun poli —
     * hanya polinya sendiri yang sah. Kosong untuk admin/superadmin.
     */
    protected function pembatasanPoli(): array
    {
        return $this->batasiPoli() ? [Rule::in([$this->poliAktif()])] : [];
    }

    /**
     * Cegah akses ke data poli lain (403).
     */
    protected function pastikanPoli(Kunjungan $kunjungan): void
    {
        abort_unless($this->bolehAkses($kunjungan), 403, 'Anda hanya dapat mengelola data poli Anda sendiri.');
    }

    protected function bolehAkses(Kunjungan $kunjungan): bool
    {
        return ! $this->batasiPoli() || ($kunjungan->poli_id === null || (int) $kunjungan->poli_id === $this->poliAktif());
    }

    /**
     * Daftar poli yang boleh dilihat — polinya sendiri untuk akun poli.
     */
    protected function daftarPoliAktif(): Collection
    {
        return Poli::query()
            ->when($this->batasiPoli(), fn ($q) => $q->whereKey($this->poliAktif()))
            ->orderBy('nama')
            ->get(['id', 'nama']);
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

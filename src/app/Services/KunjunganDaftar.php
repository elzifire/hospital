<?php

namespace App\Services;

use App\Models\Kunjungan;
use App\Models\Poli;
use App\Models\Reminder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Dataset index gabungan "Digital Reminder & Kunjungan": satu tabel berisi
 * baris penjadwalan (reminder) + grup kunjungan manual (tanpa jadwal).
 * Dipakai bersama oleh DigitalReminderController dan KunjunganController
 * sehingga kedua menu menampilkan data yang sama.
 */
class KunjunganDaftar
{
    /**
     * @param  bool  $batasiPoli  akun role poli (hanya polinya sendiri)
     * @param  int|null  $poliAktif  ID poli pemilik akun (null untuk admin/superadmin)
     */
    public function data(Request $request, bool $batasiPoli, ?int $poliAktif): array
    {
        $q = (string) $request->query('q', '');
        $qAtas = strtoupper($q);
        $status = (string) $request->query('status', '');
        $poliId = (string) $request->query('poli', '');
        $dari = (string) $request->query('dari', '');
        $sampai = (string) $request->query('sampai', '');
        $periode = (string) $request->query('periode', '');
        $home = (string) $request->query('home', '');
        $mulai = match ($periode) {
            'hari-ini' => today()->toDateString(),
            '7-hari' => today()->subDays(6)->toDateString(),
            '30-hari' => today()->subDays(29)->toDateString(),
            default => null,
        };
        // Batas atas periode: "Hari Ini" hanya menampilkan tanggal hari ini,
        // "7/30 Hari" hanya rentang terpilih (bukan masa depan).
        $sampaiPeriode = $periode !== '' ? today()->toDateString() : null;

        $scopePoli = fn (Builder $t) => $t->when($batasiPoli, fn ($u) => $u->where('poli_id', $poliAktif));

        // ---- Baris penjadwalan (reminder) ----
        $reminders = Reminder::query()
            ->tap($scopePoli)
            ->with(['pnpp' => fn ($q) => $q->withTrashed()->with('satker:id,nama')], 'poli:id,nama', 'dokter:id,nama')
            ->withExists('kunjungan as sudah_kunjungan')
            ->when($q, fn ($query) => $query->where(
                fn ($sub) => $sub
                    ->where('catatan', 'like', "%{$q}%")
                    ->orWhereHas('pnpp', fn ($p) => $p
                        ->whereRaw('UPPER(nama) LIKE ?', ["%{$qAtas}%"])
                        ->orWhere('nip', 'like', "%{$q}%"))
            ))
            ->when($status && $status !== 'tercatat', fn ($query) => $query->where('status', $status))
            ->when($status === 'tercatat', fn ($query) => $query->whereRaw('1 = 0'))
            ->when($poliId && ! $batasiPoli, fn ($query) => $query->where('poli_id', $poliId))
            ->when($dari, fn ($query) => $query->whereDate('tanggal', '>=', $dari))
            ->when($sampai, fn ($query) => $query->whereDate('tanggal', '<=', $sampai))
            ->when($mulai, fn ($query) => $query->whereDate('tanggal', '>=', $mulai))
            ->when($sampaiPeriode, fn ($query) => $query->whereDate('tanggal', '<=', $sampaiPeriode))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Reminder $r) => $this->barisReminder($r));

        // ---- Grup kunjungan manual (tanpa jadwal) ----
        $grupManual = Kunjungan::query()
            ->whereNull('reminder_id')
            ->tap($scopePoli)
            ->with(['pnpp' => fn ($q) => $q->withTrashed()->with('satker:id,nama')], 'poli:id,nama')
            ->when($q, fn ($query) => $query->whereHas('pnpp', fn ($p) => $p
                ->whereRaw('UPPER(nama) LIKE ?', ["%{$qAtas}%"])
                ->orWhere('nip', 'like', "%{$q}%")))
            ->when($status && $status !== 'tercatat', fn ($query) => $query->whereRaw('1 = 0'))
            ->when($dari, fn ($query) => $query->whereDate('tanggal_kunjungan', '>=', $dari))
            ->when($sampai, fn ($query) => $query->whereDate('tanggal_kunjungan', '<=', $sampai))
            ->when($mulai, fn ($query) => $query->whereDate('tanggal_kunjungan', '>=', $mulai))
            ->when($sampaiPeriode, fn ($query) => $query->whereDate('tanggal_kunjungan', '<=', $sampaiPeriode))
            ->orderByDesc('tanggal_kunjungan')
            ->get()
            ->groupBy(fn ($k) => $k->pnpp_id.'|'.$k->tanggal_kunjungan->format('Y-m-d'))
            // Filter poli diterapkan per grup (satu pasien bisa beberapa poli).
            ->when(! $batasiPoli && $poliId !== '', fn (Collection $grup) => $grup
                ->filter(fn (Collection $baris) => $baris->contains(fn ($k) => (int) $k->poli_id === (int) $poliId)))
            ->map(fn (Collection $grup) => $this->barisManual($grup));

        // ---- Gabung, urutkan by tanggal menurun, lalu paginate ----
        $semua = $reminders->concat($grupManual)
            ->sortByDesc(fn ($baris) => $baris['tanggal']?->timestamp ?? 0)
            ->values();

        // Filter home visit: '1' → hanya home visit, '0' → kunjungan RS,
        // kosong (nilai default) → semuanya.
        if ($home === '1' || $home === '0') {
            $cariHome = $home === '1';
            $semua = $semua->filter(
                fn ($baris) => ((bool) ($baris['homeVisit'] ?? false)) === $cariHome,
            );
        }

        $semua = $semua->all();

        $perPage = 10;
        $halaman = max(1, (int) $request->query('page', 1));
        $items = new LengthAwarePaginator(
            array_slice($semua, ($halaman - 1) * $perPage, $perPage),
            count($semua),
            $perPage,
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        // Statistik dihitung dari data yang sudah ter-filter (q/status/poli/tanggal),
        // jadi kartu ikut "hanya menampilkan data yang terpilih".
        $semuaCol = collect($semua);
        $perStatus = $semuaCol->where('tipe', 'reminder')->pluck('status')->countBy();

        return [
            'items' => $items,
            'perStatus' => $perStatus,
            'total' => $semuaCol->count(),
            'mendatang' => $semuaCol
                ->where('tipe', 'reminder')
                ->filter(fn ($b) => $b['status'] === 'terjadwal' && $b['tanggal']?->gte(today()))
                ->count(),
            'realisasi' => $semuaCol
                ->where('tipe', 'reminder')
                ->where('sudahKunjungan', true)
                ->count(),
            'manual' => $grupManual->count(),
            'polis' => $this->daftarPoliAktif($batasiPoli, $poliAktif),
            'batasiPoli' => $batasiPoli,
            'filters' => ['q' => $q, 'status' => $status, 'poli' => $poliId, 'dari' => $dari, 'sampai' => $sampai, 'periode' => $periode, 'home' => $home],
        ];
    }

    /**
     * Grup kunjungan (1 grup = 1 pasien + 1 tanggal, bisa beberapa poli) —
     * sumber data bersama untuk menu Kunjungan dan laporan Monitoring
     * "kunjungan" agar angkanya sama. Filter q/satker/rentang dikenakan di
     * level baris; poli & home visit disaring per grup.
     *
     * @return Collection<int, array>
     */
    public function kunjunganBerkelompok(Request $request, bool $batasiPoli, ?int $poliAktif, string $urutan = 'desc'): Collection
    {
        $q = (string) $request->query('q', '');
        $qAtas = strtoupper($q);
        $satkerId = (string) $request->query('satker', '');
        $poliId = (string) $request->query('poli', '');
        $dari = (string) $request->query('dari', '');
        $sampai = (string) $request->query('sampai', '');
        $periode = (string) $request->query('periode', '');
        $home = (string) $request->query('home', '');
        $mulai = match ($periode) {
            'hari-ini' => today()->toDateString(),
            '7-hari' => today()->subDays(6)->toDateString(),
            '30-hari' => today()->subDays(29)->toDateString(),
            default => null,
        };

        $scopePoli = fn (Builder $t) => $t->when($batasiPoli, fn ($u) => $u->where('poli_id', $poliAktif));

        $grup = Kunjungan::query()
            ->with(['pnpp' => fn ($q) => $q->withTrashed()->with('satker:id,nama')], 'poli:id,nama')
            ->tap($scopePoli)
            ->when($q, fn ($query) => $query->whereHas('pnpp', fn ($p) => $p
                ->whereRaw('UPPER(nama) LIKE ?', ["%{$qAtas}%"])
                ->orWhere('nip', 'like', "%{$q}%")))
            ->when($satkerId, fn ($query) => $query->whereHas('pnpp', fn ($p) => $p->where('satker_id', (int) $satkerId)))
            ->when($dari, fn ($query) => $query->whereDate('tanggal_kunjungan', '>=', $dari))
            ->when($sampai, fn ($query) => $query->whereDate('tanggal_kunjungan', '<=', $sampai))
            ->when($mulai, fn ($query) => $query->whereDate('tanggal_kunjungan', '>=', $mulai))
            ->orderByDesc('tanggal_kunjungan')
            ->get()
            ->groupBy(fn ($k) => $k->pnpp_id.'|'.$k->tanggal_kunjungan->format('Y-m-d'));

        // Filter poli diterapkan per grup (satu pasien bisa beberapa poli).
        if (! $batasiPoli && $poliId !== '') {
            $grup = $grup->filter(fn (Collection $baris) => $baris->contains(fn ($k) => (int) $k->poli_id === (int) $poliId));
        }

        // Filter home visit per grup: '1' → hanya home visit, '0' → kunjungan RS.
        if ($home === '1' || $home === '0') {
            $cariHome = $home === '1';
            $grup = $grup->filter(fn (Collection $baris) => $baris->contains(
                fn ($k) => (bool) $k->home_visit,
            ) === $cariHome);
        }

        return $grup
            ->when(
                $urutan === 'asc',
                fn (Collection $g) => $g->sortBy(fn (Collection $b) => $b->first()->tanggal_kunjungan?->timestamp ?? 0),
                fn (Collection $g) => $g->sortByDesc(fn (Collection $b) => $b->first()->tanggal_kunjungan?->timestamp ?? 0),
            )
            ->values()
            ->map(fn (Collection $blok) => $this->barisKunjungan($blok));
    }

    /**
     * Jumlah "kunjungan" versi grup (pasien + tanggal unik) — bukan jumlah
     * baris poli. Dipakai kartu monitoring agar sama dengan Total Kunjungan
     * di menu Kunjungan.
     */
    public function jumlahKunjungan(?int $poliAktif): int
    {
        $sub = Kunjungan::query()
            ->when($poliAktif, fn ($q) => $q->where('poli_id', $poliAktif))
            ->toBase()
            ->selectRaw('pnpp_id, tanggal_kunjungan')
            ->distinct();

        return DB::table(DB::raw('('.$sub->toSql().') as k'))
            ->mergeBindings($sub)
            ->count();
    }

    /**
     * Dataset index Kunjungan (halaman tersendiri): hanya kunjungan yang
     * benar-benar tercatat — manual maupun realisasi dari penjadwalan —
     * dikelompokkan per pasien + tanggal. Berbeda dari data() gabungan
     * dengan Digital Reminder agar tujuan tiap modul tidak tercampur.
     */
    public function dataKunjungan(Request $request, bool $batasiPoli, ?int $poliAktif, string $urutan = 'desc'): array
    {
        $baris = $this->kunjunganBerkelompok($request, $batasiPoli, $poliAktif, $urutan);

        $perPage = (int) $request->query('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $halaman = max(1, (int) $request->query('page', 1));

        $items = new LengthAwarePaginator(
            $baris->forPage($halaman, $perPage)->values()->all(),
            $baris->count(),
            $perPage,
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return [
            'items' => $items,
            'total' => $baris->count(),
            'pasien' => $baris->pluck('pnpp_id')->unique()->count(),
            'barisPoli' => $baris->sum('jumlahBaris'),
            'realisasi' => $baris->where('sumber', 'realisasi')->count(),
            'manual' => $baris->where('sumber', 'manual')->count(),
            'polis' => $this->daftarPoliAktif($batasiPoli, $poliAktif),
            'batasiPoli' => $batasiPoli,
            'filters' => [
                'q' => (string) $request->query('q', ''),
                'satker' => (string) $request->query('satker', ''),
                'poli' => (string) $request->query('poli', ''),
                'dari' => (string) $request->query('dari', ''),
                'sampai' => (string) $request->query('sampai', ''),
                'periode' => (string) $request->query('periode', ''),
                'home' => (string) $request->query('home', ''),
            ],
        ];
    }

    /**
     * @param  Collection<int, Kunjungan>  $grup
     */
    private function barisKunjungan(Collection $grup): array
    {
        $pertama = $grup->first();
        $homeVisit = $grup->contains(fn ($k) => $k->home_visit);

        return [
            'tipe' => 'kunjungan',
            'tanggal' => $pertama->tanggal_kunjungan,
            'jam' => null,
            'pnpp_id' => $pertama->pnpp_id,
            'pasien' => $pertama->pnpp,
            'poliBadges' => $grup->filter(fn ($k) => $k->poli)->pluck('poli.nama')->unique()->values()->all(),
            'homeVisit' => $homeVisit,
            'jumlahBaris' => $grup->count(),
            'keluhan' => $grup->filter(fn ($k) => $k->keluhan)->pluck('keluhan')->implode(' · '),
            'diagnosa' => $grup->filter(fn ($k) => $k->diagnosa)->pluck('diagnosa')->implode(' · '),
            'jumlahPoli' => $grup->pluck('poli_id')->unique()->filter()->count(),
            'sumber' => $grup->contains(fn ($k) => filled($k->reminder_id)) ? 'realisasi' : 'manual',
            'sumberLabel' => $grup->contains(fn ($k) => filled($k->reminder_id)) ? 'Realisasi Reminder' : 'Manual',
            'detailUrl' => route('admin.pnpp.kunjungan', $pertama->pnpp_id),
        ];
    }

    private function barisReminder(Reminder $r): array
    {
        return [
            'tipe' => 'reminder',
            'tanggal' => $r->tanggal,
            'jam' => $r->jam,
            'pasien' => $r->pnpp,
            'poliNama' => $r->poli?->nama ?? ($r->home_visit ? 'Home Visit' : '—'),
            'dokter' => $r->dokter?->nama,
            'homeVisit' => (bool) $r->home_visit,
            'sudahKunjungan' => (bool) $r->sudah_kunjungan,
            'poliBadges' => [],
            'diagnosa' => null,
            'sumber' => 'jadwal',
            'sumberLabel' => $r->sudah_kunjungan ? 'Realisasi Reminder' : 'Reminder',
            'status' => $r->status,
            'catatUrl' => route('admin.digital-reminder.edit', $r).'#kunjungan',
            'editUrl' => route('admin.digital-reminder.edit', $r),
            'jadwalUlangUrl' => route('admin.digital-reminder.jadwal-ulang', $r),
            'hapusUrl' => route('admin.digital-reminder.destroy', $r),
            'forceHapusUrl' => route('admin.digital-reminder.force-destroy', $r),
            'detailUrl' => route('admin.pnpp.kunjungan', $r->pnpp_id),
        ];
    }

    /**
     * @param  Collection<int, Kunjungan>  $grup
     */
    private function barisManual(Collection $grup): array
    {
        $pertama = $grup->first();

        return [
            'tipe' => 'manual',
            'tanggal' => $pertama->tanggal_kunjungan,
            'jam' => null,
            'pasien' => $pertama->pnpp,
            'poliNama' => null,
            'dokter' => null,
            'homeVisit' => $grup->contains(fn ($k) => $k->home_visit),
            'sudahKunjungan' => true,
            'poliBadges' => $grup->filter(fn ($k) => $k->poli)->pluck('poli.nama')->unique()->values()->all(),
            'diagnosa' => $grup->filter(fn ($k) => $k->diagnosa)->pluck('diagnosa')->implode(' · '),
            'sumber' => 'manual',
            'sumberLabel' => 'Manual',
            'status' => 'tercatat',
            'catatUrl' => null,
            'editUrl' => null,
            'hapusUrl' => null,
            'detailUrl' => route('admin.pnpp.kunjungan', $pertama->pnpp_id),
        ];
    }

    /**
     * Daftar poli yang boleh dilihat — polinya sendiri untuk akun poli.
     */
    private function daftarPoliAktif(bool $batasiPoli, ?int $poliAktif): Collection
    {
        return Poli::query()
            ->when($batasiPoli, fn ($q) => $q->whereKey($poliAktif))
            ->orderBy('nama')
            ->get(['id', 'nama']);
    }
}

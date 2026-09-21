<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kunjungan;
use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\Reminder;
use App\Models\Satker;
use App\Services\KunjunganDaftar;
use App\Services\PencatatKunjungan;
use Illuminate\Http\Request;
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
     * Daftar kunjungan — riwayat berobat yang benar-benar tercatat
     * (manual maupun realisasi dari penjadwalan), lihat KunjunganDaftar.
     */
    public function index(Request $request)
    {
        return view('admin.kunjungan.index', app(KunjunganDaftar::class)->dataKunjungan(
            $request,
            $this->batasiPoli(),
            $this->poliAktif(),
        ));
    }

    /**
     * Form tambah kunjungan — dua cara dalam satu halaman:
     *  "Dari Jadwal": pilih penjadwalan Digital Reminder (poli terjadwal
     *      terkunci, poli lain opsional) → catat & tandai jadwal selesai.
     *  "Manual": pilih pasien lalu catat beberapa poli sekaligus.
     */
    public function create(Request $request)
    {
        $q = (string) $request->query('q', '');
        $qAtas = strtoupper($q);
        $satkerId = (string) $request->query('satker', '');

        $pnpps = Pnpp::query()
            ->with('satker:id,nama')
            ->when($q, fn ($t) => $t->where(
                fn ($sub) => $sub->whereRaw('UPPER(nama) LIKE ?', ["%{$qAtas}%"])
                    ->orWhere('nip', 'like', "%{$q}%")
                    ->orWhere('no_hp', 'like', "%{$q}%")
            ))
            ->when($satkerId, fn ($t) => $t->where('satker_id', $satkerId))
            ->orderBy('nama')
            ->get(['id', 'nama', 'nip', 'no_hp', 'satker_id']);

        // Kandidat jadwal mode "Dari Jadwal": status terjadwal & belum
        // dicatat. User akun poli hanya melihat jadwal polinya sendiri.
        $reminders = Reminder::query()
            ->when($this->batasiPoli(), fn ($query) => $query->where('poli_id', $this->poliAktif()))
            ->with('pnpp.satker:id,nama', 'poli:id,nama')
            ->when($q || $satkerId, fn ($query) => $query->whereHas('pnpp', fn ($p) => $p
                ->when($q, fn ($sub) => $sub->where(
                    fn ($s) => $s->whereRaw('UPPER(nama) LIKE ?', ["%{$qAtas}%"])
                        ->orWhere('nip', 'like', "%{$q}%")
                        ->orWhere('no_hp', 'like', "%{$q}%")
                ))
                ->when($satkerId, fn ($sub) => $sub->where('satker_id', $satkerId))))
            ->where('status', 'terjadwal')
            ->whereDoesntHave('kunjungan')
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->limit(50)
            ->get();

        return view('admin.kunjungan.create', [
            'pnpps' => $pnpps,
            'satkers' => Satker::orderBy('nama')->get(['id', 'nama']),
            'reminders' => $reminders,
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
        $data = $request->validate(['pnpp_id' => ['required', Rule::exists('pnpps', 'id')]] + $this->aturanPolis($request));

        $pnpp = Pnpp::findOrFail($data['pnpp_id']);
        $jumlah = $this->simpanBaris($pnpp, $data['tanggal_kunjungan'], $data);

        $pesan = ! empty($data['home_visit'])
            ? "Kunjungan home visit untuk \"{$pnpp->nama}\" berhasil dicatat."
            : "{$jumlah} catatan poli untuk \"{$pnpp->nama}\" berhasil ditambahkan.";

        return redirect()
            ->route('admin.kunjungan.index')
            ->with('success', $pesan);
    }

    /**
     * Catat kunjungan dari sebuah penjadwalan (mode "Dari Jadwal" di
     * form tambah kunjungan). Poli terjadwal terkunci & selalu dicatat;
     * poli lain yang dicentang ikut menjadi baris mandiri.
     */
    public function catatDariReminder(Request $request)
    {
        $data = $request->validate([
            'reminder_id' => ['required', 'integer', Rule::exists('reminders', 'id')],
            'tanggal_kunjungan' => ['required', 'date'],
            'poli_pilih' => ['nullable', 'array'],
            'poli_pilih.*' => array_merge(['integer', Rule::exists('polis', 'id')], $this->pembatasanPoli()),
            'polis' => ['nullable', 'array'],
            'polis.*.keluhan' => ['nullable', 'string', 'max:1000'],
            'polis.*.diagnosa' => ['nullable', 'string', 'max:1000'],
        ]);

        $reminder = Reminder::findOrFail($data['reminder_id']);
        $this->pastikanPoliReminder($reminder);

        if ($reminder->kunjungan()->exists()) {
            return back()->with('error', 'Penjadwalan ini sudah memiliki kunjungan tercatat.');
        }

        $jumlah = app(PencatatKunjungan::class)->dariReminder($reminder, $data);

        return redirect()
            ->route('admin.kunjungan.index')
            ->with('success', "Kunjungan untuk \"{$reminder->pnpp->nama}\" tercatat ({$jumlah} poli) — penjadwalan selesai.");
    }

    /**
     * Simpan kunjungan dari halaman riwayat satu pasien.
     */
    public function storeUntukPasien(Request $request, Pnpp $pnpp)
    {
        $data = $request->validate($this->aturanPolis($request));

        $jumlah = $this->simpanBaris($pnpp, $data['tanggal_kunjungan'], $data);

        $pesan = ! empty($data['home_visit'])
            ? 'Kunjungan home visit berhasil dicatat.'
            : "{$jumlah} catatan poli berhasil ditambahkan.";

        return redirect()
            ->route('admin.pnpp.kunjungan', $pnpp)
            ->with('success', $pesan);
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

        $homeVisit = $request->boolean('home_visit', $kunjungan->home_visit);

        $data = $request->validate([
            'tanggal_kunjungan' => ['required', 'date'],
            'home_visit' => ['sometimes', 'boolean'],
            'poli_id' => array_merge(
                [Rule::exists('polis', 'id')],
                $homeVisit ? ['nullable'] : ['required'],
                $this->pembatasanPoli(),
            ),
            'keluhan' => ['nullable', 'string', 'max:1000'],
            'diagnosa' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($homeVisit) {
            $data['poli_id'] = null;
        }

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
     * Validasi form multi-poli: polis (keluhan/diagnosa per poli) JIKA
     * bukan home visit. Home visit tanpa poli → wajib flag home_visit.
     * Bila home_visit dicentang, polis boleh kosong (minimal 1 diabaikan).
     */
    protected function aturanPolis(Request $request): array
    {
        $homeVisit = $request->boolean('home_visit');

        return [
            'tanggal_kunjungan' => ['required', 'date'],
            'home_visit' => ['sometimes', 'boolean'],
            'keluhan' => ['nullable', 'string', 'max:1000'],
            'diagnosa' => ['nullable', 'string', 'max:1000'],
            'polis' => ['array', Rule::when(! $homeVisit, ['required', 'min:1'])],
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
     * Cegah akun poli mencatat/mengubah jadwal poli lain (403).
     */
    protected function pastikanPoliReminder(Reminder $reminder): void
    {
        abort_unless(! $this->batasiPoli() || (int) $reminder->poli_id === $this->poliAktif(), 403, 'Anda hanya dapat mengelola data poli Anda sendiri.');
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
     * Buat baris kunjungan — satu baris per poli, atau satu baris tanpa
     * poli (home_visit) bila flag tercentang.
     */
    protected function simpanBaris(Pnpp $pnpp, string $tanggal, array $data): int
    {
        return DB::transaction(function () use ($pnpp, $tanggal, $data): int {
            if (! empty($data['home_visit'])) {
                $pnpp->kunjungans()->create([
                    'poli_id' => null,
                    'home_visit' => true,
                    'tanggal_kunjungan' => $tanggal,
                    'keluhan' => $data['keluhan'] ?? null,
                    'diagnosa' => $data['diagnosa'] ?? null,
                ]);

                return 1;
            }

            foreach ($data['polis'] as $baris) {
                $pnpp->kunjungans()->create([
                    'poli_id' => $baris['poli_id'],
                    'tanggal_kunjungan' => $tanggal,
                    'keluhan' => $baris['keluhan'] ?? null,
                    'diagnosa' => $baris['diagnosa'] ?? null,
                ]);
            }

            return count($data['polis']);
        });
    }
}

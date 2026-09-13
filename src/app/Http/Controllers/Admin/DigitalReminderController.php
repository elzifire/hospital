<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dokter;
use App\Models\Kunjungan;
use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\Reminder;
use App\Models\Satker;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Modul Digital Reminder — penjadwalan kunjungan pasien (murni jadwal,
 * tanpa pengiriman pesan). Pesan undangan/tindak lanjut digenerate dari
 * jadwal-jadwal ini lewat modul Outreach & Follow Up.
 */
class DigitalReminderController extends Controller
{
    /**
     * Daftar penjadwalan — cari nama/NIP, filter status & poli.
     * User role poli hanya melihat polinya sendiri.
     */
    public function index(Request $request)
    {
        $q = (string) $request->query('q', '');
        $status = (string) $request->query('status', '');
        $poliId = (string) $request->query('poli', '');

        $reminders = Reminder::query()
            ->when($this->batasiPoli(), fn ($query) => $query->where('poli_id', $this->poliAktif()))
            ->with('pnpp.satker:id,nama', 'poli:id,nama', 'dokter:id,nama')
            ->withExists('kunjungan as sudah_kunjungan')
            ->when($q, fn ($query) => $query->where(
                fn ($sub) => $sub
                    ->where('catatan', 'like', "%{$q}%")
                    ->orWhereHas('pnpp', fn ($p) => $p
                        ->where('nama', 'like', "%{$q}%")
                        ->orWhere('nip', 'like', "%{$q}%"))
            ))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($poliId && ! $this->batasiPoli(), fn ($query) => $query->where('poli_id', $poliId))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $perStatus = Reminder::query()
            ->when($this->batasiPoli(), fn ($query) => $query->where('poli_id', $this->poliAktif()))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.digital-reminder.index', [
            'reminders' => $reminders,
            'perStatus' => $perStatus,
            'total' => (int) $perStatus->sum(),
            'mendatang' => Reminder::query()
                ->when($this->batasiPoli(), fn ($query) => $query->where('poli_id', $this->poliAktif()))
                ->where('status', 'terjadwal')
                ->whereDate('tanggal', '>=', today())->count(),
            'polis' => $this->daftarPoliAktif(),
            'batasiPoli' => $this->batasiPoli(),
            'filters' => ['q' => $q, 'status' => $status, 'poli' => $poliId],
        ]);
    }

    /**
     * Form buat penjadwalan: cari pasien (nama/NIP, filter satker) lalu
     * pilih beberapa poli sekaligus (chips) + pengaturan shared.
     */
    public function create(Request $request)
    {
        return view('admin.digital-reminder.create', $this->dataTarget($request));
    }

    /**
     * Simpan penjadwalan — pasien terpilih × poli terpilih (chips).
     * Pengaturan (tanggal/jam/home visit/catatan) dibagi semua kombinasi;
     * dokter dibiarkan kosong karena beda-beda per poli — isi lewat edit.
     */
    public function store(Request $request)
    {
        $data = $request->validate($this->aturanValidasi($request));

        $dibuat = DB::transaction(function () use ($data, $request): int {
            $jumlah = 0;

            foreach ($data['pnpp_ids'] as $pnppId) {
                foreach ($data['poli_ids'] as $poliId) {
                    Reminder::create([
                        'pnpp_id' => $pnppId,
                        'poli_id' => $poliId,
                        'dokter_id' => null,
                        'tanggal' => $data['tanggal'],
                        'jam' => $data['jam'],
                        'home_visit' => (bool) ($data['home_visit'] ?? false),
                        'status' => 'terjadwal',
                        'created_by' => $request->user()?->id,
                        'catatan' => $data['catatan'] ?? null,
                    ]);
                    $jumlah++;
                }
            }

            return $jumlah;
        });

        return redirect()
            ->route('admin.digital-reminder.index')
            ->with('success', "{$dibuat} penjadwalan kunjungan berhasil dibuat.");
    }

    /**
     * Ubah penjadwalan — termasuk status manual (jaga-jaga petugas
     * lupa mencatat kunjungan sehingga status otomatis meleset).
     */
    public function edit(Reminder $reminder)
    {
        $this->pastikanPoli($reminder);

        $reminder->load('pnpp.satker:id,nama', 'poli:id,nama', 'dokter:id,nama', 'kunjungan');

        // Baris poli lain yang dikunjungi pasien pada tanggal realisasi
        // (realisasi multi-poli — hanya baris poli terjadwal yang
        // terhubung ke reminder ini).
        $sehari = $reminder->kunjungan
            ? Kunjungan::with('poli:id,nama')
                ->where('pnpp_id', $reminder->pnpp_id)
                ->whereDate('tanggal_kunjungan', $reminder->kunjungan->tanggal_kunjungan)
                ->when($this->batasiPoli(), fn ($q) => $q->where('poli_id', $reminder->poli_id))
                ->orderBy('poli_id')
                ->get()
            : collect();

        return view('admin.digital-reminder.edit', [
            'reminder' => $reminder,
            'polis' => $this->daftarPoliAktif(),
            'dokters' => Dokter::query()
                ->when($this->batasiPoli(), fn ($q) => $q->where('poli_id', $this->poliAktif()))
                ->orderBy('nama')
                ->get(['id', 'nama', 'poli_id']),
            'kunjunganSehari' => $sehari,
        ]);
    }

    public function update(Request $request, Reminder $reminder)
    {
        $this->pastikanPoli($reminder);

        $data = $request->validate($this->aturanValidasi($request, $reminder) + [
            'status' => ['required', Rule::in(Reminder::STATUS)],
        ]);

        $reminder->update([
            'poli_id' => $data['poli_id'],
            'dokter_id' => $data['dokter_id'] ?? null,
            'tanggal' => $data['tanggal'],
            'jam' => $data['jam'],
            'home_visit' => (bool) ($data['home_visit'] ?? false),
            'status' => $data['status'],
            'catatan' => $data['catatan'] ?? null,
        ]);

        return redirect()
            ->route('admin.digital-reminder.index')
            ->with('success', 'Penjadwalan untuk "'.$reminder->pnpp->nama.'" berhasil diperbarui.');
    }

    public function destroy(Reminder $reminder)
    {
        $this->pastikanPoli($reminder);

        $nama = $reminder->pnpp?->nama;
        $reminder->delete();

        return redirect()
            ->route('admin.digital-reminder.index')
            ->with('success', 'Penjadwalan untuk "'.$nama.'" berhasil dihapus.');
    }

    /**
     * Catat kunjungan nyata dari sebuah penjadwalan → status selesai.
     * Poli terjadwal selalu tercatat (checklist-nya terkunci); poli
     * lain yang juga dikunjungi pasien hari itu dicentang opsional —
     * tiap poli menjadi satu baris kunjungan.
     */
    public function catatKunjungan(Request $request, Reminder $reminder)
    {
        $this->pastikanPoli($reminder);

        $data = $request->validate([
            'tanggal_kunjungan' => ['required', 'date'],
            'poli_pilih' => ['nullable', 'array'],
            'poli_pilih.*' => array_merge(['integer', Rule::exists('polis', 'id')], $this->pembatasanPoli()),
            'polis' => ['nullable', 'array'],
            'polis.*.keluhan' => ['nullable', 'string', 'max:1000'],
            'polis.*.diagnosa' => ['nullable', 'string', 'max:1000'],
            'keluhan' => ['nullable', 'string', 'max:1000'],
            'diagnosa' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($reminder->kunjungan()->exists()) {
            return back()->with('error', 'Penjadwalan ini sudah memiliki kunjungan tercatat.');
        }

        $detail = (array) ($data['polis'] ?? []);
        $utama = (int) $reminder->poli_id;

        // Poli terjadwal diutamakan; poli tercentang lain menyusul.
        $terpilih = array_values(array_unique(array_merge([(string) $utama], array_map('strval', $data['poli_pilih'] ?? []))));
        $terpilih = array_map('intval', $terpilih);

        DB::transaction(function () use ($data, $detail, $utama, $terpilih, $reminder): void {
            foreach ($terpilih as $poliId) {
                $keluhan = $detail[$poliId]['keluhan'] ?? null;
                $diagnosa = $detail[$poliId]['diagnosa'] ?? null;

                if ($poliId === $utama) {
                    $keluhan = $keluhan ?? ($data['keluhan'] ?? null);
                    $diagnosa = $diagnosa ?? ($data['diagnosa'] ?? null);
                }

                // Hanya baris poli terjadwal yang terhubung ke reminder
                // (reminder_id unik); poli lain menjadi baris mandiri.
                $baris = [
                    'poli_id' => $poliId,
                    'tanggal_kunjungan' => $data['tanggal_kunjungan'],
                    'keluhan' => $keluhan,
                    'diagnosa' => $diagnosa,
                ];

                if ($poliId === $utama) {
                    $reminder->kunjungan()->create($baris + ['pnpp_id' => $reminder->pnpp_id]);
                } else {
                    $reminder->pnpp->kunjungans()->create($baris);
                }
            }

            $reminder->update(['status' => 'selesai']);
        });

        return redirect()
            ->route('admin.pnpp.kunjungan', $reminder->pnpp_id)
            ->with('success', 'Kunjungan untuk "'.$reminder->pnpp->nama.'" tercatat ('.count($terpilih).' poli) — penjadwalan selesai.');
    }

    /**
     * Aturan validasi form. Create: pasien + chips poli (pengaturan
     * shared, tanpa dokter). Update: satu blok poli + dokter.
     */
    protected function aturanValidasi(Request $request, ?Reminder $reminder = null): array
    {
        if ($reminder) {
            return [
                'poli_id' => array_merge(['required', Rule::exists('polis', 'id')], $this->pembatasanPoli()),
                'dokter_id' => [
                    'nullable',
                    Rule::exists('dokters', 'id')->where('poli_id', $request->integer('poli_id')),
                ],
                'tanggal' => ['required', 'date'],
                'jam' => ['required', 'date_format:H:i'],
                'home_visit' => ['nullable', 'boolean'],
                'catatan' => ['nullable', 'string', 'max:500'],
            ];
        }

        return [
            'pnpp_ids' => ['required', 'array', 'min:1'],
            'pnpp_ids.*' => ['integer', Rule::exists('pnpps', 'id')],
            'poli_ids' => ['required', 'array', 'min:1'],
            'poli_ids.*' => array_merge(['integer', Rule::exists('polis', 'id')], $this->pembatasanPoli()),
            'tanggal' => ['required', 'date', 'after_or_equal:today'],
            'jam' => ['required', 'date_format:H:i'],
            'home_visit' => ['nullable', 'boolean'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Data form: daftar pasien (filter satker + cari nama/NIP/HP) & master.
     * User role poli: poli dibatasi ke polinya sendiri (dan otomatis
     * terpilih di form).
     */
    protected function dataTarget(Request $request): array
    {
        $q = (string) $request->query('q', '');
        $satkerId = (string) $request->query('satker', '');

        $pnpps = Pnpp::query()
            ->with('satker:id,nama')
            ->when($q, fn ($query) => $query->where(
                fn ($sub) => $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('nip', 'like', "%{$q}%")
                    ->orWhere('no_hp', 'like', "%{$q}%")
            ))
            ->when($satkerId, fn ($query) => $query->where('satker_id', $satkerId))
            ->orderBy('nama')
            ->get(['id', 'nama', 'nip', 'no_hp', 'satker_id']);

        return [
            'pnpps' => $pnpps,
            'satkers' => Satker::orderBy('nama')->get(['id', 'nama']),
            'polis' => $this->daftarPoliAktif(),
            'poliAwal' => $this->batasiPoli() ? [(string) $this->poliAktif()] : [],
            'filters' => ['q' => $q, 'satker' => $satkerId],
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
    protected function pastikanPoli(Reminder $reminder): void
    {
        abort_unless($this->bolehAkses($reminder), 403, 'Anda hanya dapat mengelola data poli Anda sendiri.');
    }

    protected function bolehAkses(Reminder $reminder): bool
    {
        return ! $this->batasiPoli() || (int) $reminder->poli_id === $this->poliAktif();
    }

    /**
     * Daftar poli yang boleh dilihat — polinya sendiri untuk akun poli.
     */
    protected function daftarPoliAktif(): Collection
    {
        return Poli::query()
            ->when($this->batasiPoli(), fn ($q) => $q->where('id', $this->poliAktif()))
            ->orderBy('nama')
            ->get(['id', 'nama']);
    }
}

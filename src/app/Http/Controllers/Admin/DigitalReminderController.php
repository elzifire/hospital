<?php

namespace App\Http\Controllers\Admin;

use App\Broadcasting\BroadcastService;
use App\Http\Controllers\Controller;
use App\Models\Dokter;
use App\Models\Kunjungan;
use App\Models\MessageTemplate;
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
 * Modul Digital Reminder — penjadwalan kunjungan pasien (murni jadwal,
 * tanpa pengiriman pesan). Pesan undangan/tindak lanjut digenerate dari
 * jadwal-jadwal ini lewat modul Outreach & Follow Up.
 */
class DigitalReminderController extends Controller
{
    /**
     * Daftar penjadwalan — index gabungan Digital Reminder & Kunjungan
     * (satu tabel: penjadwalan + kunjungan manual), lihat KunjunganDaftar.
     */
    public function index(Request $request)
    {
        $data = app(KunjunganDaftar::class)->data(
            $request,
            $this->batasiPoli(),
            $this->poliAktif(),
        );

        $data['totalTrashed'] = Reminder::query()
            ->onlyTrashed()
            ->when($this->batasiPoli(), fn ($q) => $q->where('poli_id', $this->poliAktif()))
            ->count();

        return view('admin.digital-reminder.index', $data);
    }

    /**
     * Tong sampah — penjadwalan yang sudah dihapus (soft delete). Baris
     * yang terhapus tetap tersimpan dan bisa dipulihkan ke daftar utama.
     */
    public function trash(Request $request)
    {
        $q = (string) $request->query('q', '');
        $qAtas = strtoupper($q);
        $status = (string) $request->query('status', '');
        $poliId = (string) $request->query('poli', '');
        $batasiPoli = $this->batasiPoli();

        $items = Reminder::query()
            ->onlyTrashed()
            ->when($batasiPoli, fn ($query) => $query->where('poli_id', $this->poliAktif()))
            ->with(['pnpp' => fn ($q2) => $q2->withTrashed()->with('satker:id,nama')], 'poli:id,nama', 'dokter:id,nama')
            ->when($q, fn ($query) => $query->where(
                fn ($sub) => $sub
                    ->where('catatan', 'like', "%{$q}%")
                    ->orWhereHas('pnpp', fn ($p) => $p
                        ->whereRaw('UPPER(nama) LIKE ?', ["%{$qAtas}%"])
                        ->orWhere('nip', 'like', "%{$q}%"))
            ))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($poliId && ! $batasiPoli, fn ($query) => $query->where('poli_id', $poliId))
            ->orderByDesc('deleted_at')
            ->paginate(10)
            ->withQueryString();

        $queryTrashed = fn ($sistem) => $sistem
            ->when($batasiPoli, fn ($q2) => $q2->where('poli_id', $this->poliAktif()));

        return view('admin.digital-reminder.trash', [
            'items' => $items,
            'totalTrashed' => Reminder::query()->tap($queryTrashed)->onlyTrashed()->count(),
            'totalPasien' => Reminder::query()->tap($queryTrashed)->onlyTrashed()->distinct('pnpp_id')->count('pnpp_id'),
            'polis' => $this->daftarPoliAktif(),
            'batasiPoli' => $batasiPoli,
            'filters' => ['q' => $q, 'status' => $status, 'poli' => $poliId],
        ]);
    }

    /**
     * Pulihkan penjadwalan yang dihapus (soft delete) ke daftar utama.
     */
    public function restore(Reminder $reminder)
    {
        $this->pastikanPoli($reminder);

        $nama = $reminder->pnpp?->nama;

        if ($reminder->trashed()) {
            $reminder->restore();
        }

        return redirect()
            ->route('admin.digital-reminder.trash')
            ->with('success', 'Penjadwalan untuk "'.$nama.'" berhasil dipulihkan ke daftar sesi.');
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
                foreach ($this->poliIdsUntukSimpan($data['poli_ids'] ?? []) as $poliId) {
                    Reminder::create([
                        'pnpp_id' => $pnppId,
                        'poli_id' => $poliId,
                        'dokter_id' => null,
                        'message_template_id' => $data['message_template_id'] ?? null,
                        'tanggal' => $data['tanggal'],
                        'jam' => $data['jam'],
                        'home_visit' => (bool) ($data['home_visit'] ?? false),
                        'status' => 'terjadwal',
                        'created_by' => $request->user()?->id,
                        'catatan' => $data['catatan'] ?? null,
                        'vars_kustom' => $this->sanitizeVarsKustom($data['vars_kustom'] ?? null),
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
            'templates' => $this->daftarTemplate(),
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
            'poli_id' => $this->poliIdUntukUpdate($data['poli_id'] ?? null),
            'dokter_id' => $data['dokter_id'] ?? null,
            'message_template_id' => $data['message_template_id'] ?? null,
            'tanggal' => $data['tanggal'],
            'jam' => $data['jam'],
            'home_visit' => (bool) ($data['home_visit'] ?? false),
            'status' => $data['status'],
            'catatan' => $data['catatan'] ?? null,
            'vars_kustom' => $this->sanitizeVarsKustom($data['vars_kustom'] ?? null),
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
            ->with('success', 'Penjadwalan untuk "'.$nama.'" berhasil dihapus (soft delete).');
    }

    /**
     * Hapus permanen — khusus role superadmin. Soft-deleted jadwal tetap
     * bisa dibuka lewat ikatan route withTrashed().
     */
    public function forceDestroy(Request $request, Reminder $reminder)
    {
        abort_unless($request->user()?->hasRole('superadmin'), 403, 'Hanya superadmin yang dapat menghapus permanen.');

        $this->pastikanPoli($reminder);

        $nama = $reminder->pnpp?->nama;
        $reminder->forceDelete();

        return redirect()
            ->route('admin.digital-reminder.index')
            ->with('success', 'Penjadwalan untuk "'.$nama.'" berhasil dihapus permanen.');
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

        $jumlah = app(PencatatKunjungan::class)->dariReminder($reminder, $data);

        return redirect()
            ->route('admin.pnpp.kunjungan', $reminder->pnpp_id)
            ->with('success', 'Kunjungan untuk "'.$reminder->pnpp->nama.'" tercatat ('.$jumlah.' poli) — penjadwalan selesai.');
    }

    /**
     * Form jadwal ulang — hanya untuk jadwal yang masih bisa diganti
     * (terjadwal / tidak_datang tanpa kunjungan tercatat).
     */
    public function formJadwalUlang(Reminder $reminder)
    {
        $this->pastikanPoli($reminder);

        if (! $this->bolehJadwalUlang($reminder)) {
            return back()
                ->with('error', 'Jadwal ini tidak bisa dijadwalkan ulang (status: '.$reminder->status.($reminder->kunjungan()->exists() ? ' — sudah ada kunjungan' : '').').');
        }

        $reminder->load('pnpp.satker:id,nama', 'poli:id,nama', 'dokter:id,nama', 'messageTemplate:id,judul');

        return view('admin.digital-reminder.jadwal-ulang', ['reminder' => $reminder]);
    }

    /**
     * Proses jadwal ulang: pesan menunggu milik jadwal lama dibatalkan,
     * jadwal lama ditandai jadwal_ulang (tidak dipakai follow up lagi),
     * lalu dibuat baris baru berstatus terjadwal dengan tanggal/jam baru.
     * Baris baru nantinya menyusul jadi "selesai" saat kunjungan dicatat.
     */
    public function jadwalUlang(Request $request, Reminder $reminder)
    {
        $this->pastikanPoli($reminder);

        if (! $this->bolehJadwalUlang($reminder)) {
            return back()->with('error', 'Jadwal ini tidak bisa dijadwalkan ulang.');
        }

        $data = $request->validate([
            'tanggal' => ['required', 'date', 'after_or_equal:today'],
            'jam' => ['required', 'date_format:H:i'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        if ($reminder->tanggal?->format('Y-m-d') === $data['tanggal']
            && $reminder->jam?->format('H:i') === $data['jam']) {
            return back()
                ->with('error', 'Tanggal/jam baru sama dengan jadwal lama — tidak ada yang dijadwalkan ulang.')
                ->withInput();
        }

        $baru = DB::transaction(function () use ($request, $reminder, $data): Reminder {
            app(BroadcastService::class)->batalkan(
                $reminder->messageLogs()->where('status', 'menunggu')->pluck('id'),
            );

            $lamaTanggal = $reminder->tanggal?->format('d/m/Y') ?? '—';
            $catatanBaruLama = trim((string) $data['catatan']);
            $catatanBaruLama = $catatanBaruLama !== '' ? ' ('.$catatanBaruLama.')' : '';
            $catatanLama = 'dijadwal ulang ke '.$data['tanggal'].' '.$data['jam'].$catatanBaruLama;
            $catatanLama = mb_substr(
                trim((string) $reminder->catatan).(trim((string) $reminder->catatan) !== '' ? ' · ' : '').$catatanLama,
                0,
                500,
            );

            $reminder->update([
                'status' => 'jadwal_ulang',
                'catatan' => $catatanLama !== '' ? $catatanLama : null,
            ]);

            return Reminder::create([
                'pnpp_id' => $reminder->pnpp_id,
                'poli_id' => $reminder->poli_id,
                'dokter_id' => $reminder->dokter_id,
                'message_template_id' => $reminder->message_template_id,
                'tanggal' => $data['tanggal'],
                'jam' => $data['jam'],
                'home_visit' => (bool) $reminder->home_visit,
                'status' => 'terjadwal',
                'created_by' => $request->user()?->id,
                'catatan' => 'Dijadwal ulang dari '.$lamaTanggal.$catatanBaruLama,
                'vars_kustom' => $reminder->vars_kustom,
            ]);
        });

        return redirect()
            ->route('admin.digital-reminder.index')
            ->with('success', 'Penjadwalan untuk "'.($reminder->pnpp?->nama ?? 'pasien').'" dijadwalkan ulang ke '
                .$baru->tanggal?->format('d/m/Y').' '.$baru->jam?->format('H:i').'.');
    }

    /**
     * Aturan validasi form. Create: pasien + chips poli (pengaturan
     * shared, tanpa dokter). Update: satu blok poli + dokter.
     * Home visit: poli tidak wajib (boleh kosong).
     */
    protected function aturanValidasi(Request $request, ?Reminder $reminder = null): array
    {
        if ($reminder) {
            return [
                'poli_id' => array_merge([
                    Rule::requiredIf(! $request->boolean('home_visit')),
                    'nullable',
                    Rule::exists('polis', 'id'),
                ], $this->pembatasanPoli()),
                'dokter_id' => [
                    'nullable',
                    Rule::exists('dokters', 'id')->where('poli_id', $request->input('poli_id')),
                ],
                'message_template_id' => $this->aturanTemplate(),
                'tanggal' => ['required', 'date'],
                'jam' => ['required', 'date_format:H:i'],
                'home_visit' => ['nullable', 'boolean'],
                'catatan' => ['nullable', 'string', 'max:500'],
                'vars_kustom' => ['nullable', 'array'],
                'vars_kustom.*' => ['nullable', 'string', 'max:200'],
            ];
        }

        return [
            'pnpp_ids' => ['required', 'array', 'min:1'],
            'pnpp_ids.*' => ['integer', Rule::exists('pnpps', 'id')],
            'poli_ids' => ['nullable', 'array', Rule::requiredIf(! $request->boolean('home_visit'))],
            'poli_ids.*' => array_merge(['integer', Rule::exists('polis', 'id')], $this->pembatasanPoli()),
            'message_template_id' => $this->aturanTemplate(),
            // 'tanggal' => ['required', 'date', 'after_or_equal:today'],
            'tanggal' => ['required', 'date'],
            'jam' => ['required', 'date_format:H:i'],
            'home_visit' => ['nullable', 'boolean'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'vars_kustom' => ['nullable', 'array'],
            'vars_kustom.*' => ['nullable', 'string', 'max:200'],
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
        $qAtas = strtoupper($q);
        $satkerId = (string) $request->query('satker', '');

        $pnpps = Pnpp::query()
            ->with('satker:id,nama')
            ->when($q, fn ($query) => $query->where(
                fn ($sub) => $sub->whereRaw('UPPER(nama) LIKE ?', ["%{$qAtas}%"])
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
            'templates' => $this->daftarTemplate(),
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
     * Daftar poli yang akan disimpan. Home visit boleh tanpa poli (null);
     * untuk akun poli yang mengosongkannya, poli sendiri digunakan agar
     * jadwal tetap terlihat & ter-scope di daftarnya.
     *
     * @param  array<int>  $poliIds
     * @return array<int|null>
     */
    protected function poliIdsUntukSimpan(array $poliIds): array
    {
        if ($poliIds !== []) {
            return array_values(array_unique(array_map('intval', $poliIds)));
        }

        return $this->batasiPoli() ? [$this->poliAktif()] : [null];
    }

    /**
     * Poli final untuk update — sama seperti poliIdsUntukSimpan tetapi
     * untuk satu jadwal (home visit tanpa poli → null, akun poli → miliknya).
     */
    protected function poliIdUntukUpdate(?int $poliId): ?int
    {
        return $poliId ?? ($this->batasiPoli() ? $this->poliAktif() : null);
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
     * Jadwal boleh dijadwalkan ulang bila masih "aktif" (terjadwal atau
     * sudah ditandai tidak datang) dan belum memiliki kunjungan tercatat —
     * jadwal yang sudah selesai/kunjungan tidak boleh dirombak ulang.
     */
    protected function bolehJadwalUlang(Reminder $reminder): bool
    {
        return in_array($reminder->status, ['terjadwal', 'tidak_datang'], true)
            && ! $reminder->kunjungan()->exists();
    }

    /**
     * Rule validasi template pesan — opsional, harus template aktif.
     */
    protected function aturanTemplate(): array
    {
        return [
            'nullable',
            'integer',
            Rule::exists('message_templates', 'id')->where('is_active', true),
        ];
    }

    /**
     * Bersihkan variabel kustom dari form — hanya terima nilai string
     * non-kosong; key yang kosong/null dihapus agar tidak membebani
     * penyimpanan.
     *
     * @return array<string, string>|null
     */
    protected function sanitizeVarsKustom(?array $input): ?array
    {
        if (! is_array($input)) {
            return null;
        }

        $bersih = [];

        foreach ($input as $kunci => $nilai) {
            $kunci = strtolower(trim((string) $kunci));
            $nilai = trim((string) $nilai);

            if ($kunci !== '' && $nilai !== '') {
                $bersih[$kunci] = $nilai;
            }
        }

        return $bersih !== [] ? $bersih : null;
    }

    /**
     * Template WhatsApp aktif untuk dropdown form penjadwalan — khusus
     * kategori "Digital Reminder" (seeder TemplateCategorySeeder) supaya
     * tidak bercampur dengan template modul lain.
     */
    protected function daftarTemplate(): Collection
    {
        return MessageTemplate::query()
            ->whereHas('category', fn ($q) => $q->where('slug', 'digital-reminder'))
            ->where('is_active', true)
            ->orderBy('judul')
            ->get(['id', 'judul', 'konten', 'template_category_id']);
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

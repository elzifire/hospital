<?php

namespace App\Http\Controllers\Admin;

use App\Broadcasting\BroadcastService;
use App\Broadcasting\PhoneFormat;
use App\Models\MessageLog;
use App\Models\MessageReply;
use App\Models\Pnpp;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Modul Follow Up — riwayat pesan tindak lanjut pasien plus form kirim
 * pesan manual yang diwarisi dari ManualBroadcastController. Aturan
 * generate otomatis (H-1, hari-H, tidak-datang) dinonaktifkan sementara.
 *
 * Form manual menampilkan semua template aktif (kategori bebas). Halaman
 * create & edit menampilkan saran follow up dengan dua kategori saringan:
 *  - belum_hadir: pasien yang punya jadwal Digital Reminder yang lewat
 *    tanpa kunjungan (tidak hadir) — wajib di-follow up;
 *  - outreach_belum_balas: pasien yang menerima pesan outreach (informasi
 *    & edukasi / pelayanan) belum membalas balasan.
 * Daftar penerima hanya menampilkan PNPP yang belum membalas pesan respon.
 */
class FollowUpController extends ManualBroadcastController
{
    /**
     * Pasien punya jadwal terlewat tanpa kunjungan (tidak hadir).
     */
    public const SARAN_BELUM_HADIR = 'belum_hadir';

    /**
     * Pasien menerima pesan outreach namun belum membalas.
     */
    public const SARAN_OUTREACH_BELUM_BALAS = 'outreach_belum_balas';

    /**
     * Saring kandidat penerima: buang PNPP yang sudah pernah membalas
     * (berdasarkan pnpp_id atau nomor WhatsApp-nya), namun target yang
     * sudah dipilih tetap dipertahankan supaya tidak tersapu saat edit.
     *
     * @param  Collection<int, Pnpp>  $pnpps
     * @param  array<int, int>  $wajibTampil
     * @return Collection<int, Pnpp>
     */
    protected function saringBelumBalas(Collection $pnpps, array $wajibTampil): Collection
    {
        if ($pnpps->isEmpty()) {
            return $pnpps;
        }

        $wajibSet = array_flip(array_map('intval', $wajibTampil));

        $repliedPnppIds = MessageReply::query()
            ->whereNotNull('pnpp_id')
            ->pluck('pnpp_id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();

        $repliedNoHp = MessageReply::query()
            ->whereNotNull('no_hp')
            ->pluck('no_hp')
            ->map(fn ($n) => (string) $n)
            ->flip()
            ->all();

        return $pnpps->filter(function (Pnpp $p) use ($wajibSet, $repliedPnppIds, $repliedNoHp) {
            if (isset($wajibSet[(int) $p->id])) {
                return true;
            }
            if (isset($repliedPnppIds[(int) $p->id])) {
                return false;
            }
            $wa = PhoneFormat::toWa($p->no_hp);

            return $wa === null || ! isset($repliedNoHp[$wa]);
        })->values();
    }

    /**
     * Algoritma saran follow up: gabungan dua kategori saringan —
     * 1) pasien yang jadwalnya lewat tanpa kunjungan (tidak hadir),
     * 2) pasien yang outreach-nya terkirim namun belum membalas.
     * Satu pasien boleh memenuhi dua-duanya (kategori & alasan digabung).
     * Pasien yang sudah di-follow up pada jendela saran dikecualikan
     * supaya tidak dobel, dan yang nomor WhatsApp-nya tidak valid
     * tidak disarankan.
     *
     * @return Collection<int, array{pnpp: Pnpp, kategori: array<int, string>, alasan: array<int, string>}>
     */
    protected function saranPenerima(Request $request): Collection
    {
        $kandidat = $this->reminderBelumHadir()
            ->concat($this->outreachBelumDibalas());

        if ($kandidat->isEmpty()) {
            return collect();
        }

        $gabung = collect();
        $perId = [];
        foreach ($kandidat as $item) {
            $id = (int) $item['pnpp']->id;
            if (! isset($perId[$id])) {
                $perId[$id] = $gabung->count();
                $gabung->push([
                    'pnpp' => $item['pnpp'],
                    'kategori' => [],
                    'alasan' => [],
                ]);
            }
            $idx = $perId[$id];
            $rek = $gabung->get($idx);
            $rek['kategori'][] = $item['kategori'];
            $rek['alasan'][] = $item['alasan'];
            $gabung->put($idx, $rek);
        }

        // Belum hadir lebih prioritas, lalu urut abjad nama.
        return $gabung->sortBy([
            fn ($a) => in_array(self::SARAN_BELUM_HADIR, $a['kategori'], true) ? 0 : 1,
            fn ($a) => strtolower((string) $a['pnpp']->nama),
        ])->values();
    }

    /**
     * Kandidat saran "belum hadir": jadwal Digital Reminder berstatus
     * tidak_datang, atau masih terjadwal namun tanggalnya sudah lewat
     * tanpa kunjungan. Ambil jadwal terlewat terakhir per pasien.
     *
     * @return Collection<int, array{pnpp: Pnpp, kategori: string, alasan: string}>
     */
    protected function reminderBelumHadir(): Collection
    {
        $jadwal = Reminder::query()
            ->with('pnpp.satker:id,nama', 'poli:id,nama')
            ->where(function ($query) {
                $query->where('status', 'tidak_datang')
                    ->orWhere(function ($terlambat) {
                        $terlambat->where('status', 'terjadwal')
                            ->whereDate('tanggal', '<', today()->toDateString())
                            ->whereDoesntHave('kunjungan');
                    });
            })
            ->orderByDesc('tanggal')
            ->orderBy('jam')
            ->get()
            ->unique('pnpp_id');

        return $jadwal
            ->map(function (Reminder $r) {
                $pnpp = $r->pnpp;
                if ($pnpp === null || PhoneFormat::toWa($pnpp->no_hp) === null) {
                    return null;
                }

                return [
                    'pnpp' => $pnpp,
                    'kategori' => self::SARAN_BELUM_HADIR,
                    'alasan' => 'Jadwal '.($r->poli?->nama ?? 'poli').' '
                        .($r->tanggal?->locale('id')->translatedFormat('l, d F Y') ?? '—')
                        .' lewat tanpa kunjungan.',
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Kandidat saran "outreach belum dibalas": pesan outreach (informasi
     * & edukasi / pelayanan) terkirim pada jendela saran (7 hari terakhir)
     * namun belum ada balasan setelah pesan terkirim. Balasan dikenali
     * lewat pnpp_id atau nomor WhatsApp pada MessageReply; pasien yang
     * sudah di-follow up pada jendela yang sama dikecualikan.
     *
     * @return Collection<int, array{pnpp: Pnpp, kategori: string, alasan: string}>
     */
    protected function outreachBelumDibalas(): Collection
    {
        $sejak = now()->subDays(7)->startOfDay();

        $logs = MessageLog::query()
            ->jenis('outreach')
            ->where('status', 'terkirim')
            ->where('created_at', '>=', $sejak)
            ->whereNotNull('pnpp_id')
            ->with('pnpp.satker:id,nama', 'template:id,judul')
            ->orderByDesc('created_at')
            ->get()
            ->unique('pnpp_id');

        if ($logs->isEmpty()) {
            return collect();
        }

        $pids = $logs->pluck('pnpp_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $was = $logs->pluck('pnpp')
            ->filter()
            ->map(fn (Pnpp $p) => PhoneFormat::toWa($p->no_hp))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $balas = collect();
        MessageReply::query()
            ->where('waktu_masuk', '>=', $sejak)
            ->where(function ($query) use ($pids, $was) {
                $query->whereIn('pnpp_id', $pids)->orWhereIn('no_hp', $was);
            })
            ->get(['pnpp_id', 'no_hp', 'waktu_masuk'])
            ->each(function (MessageReply $r) use ($balas) {
                if ($r->pnpp_id !== null) {
                    $balas['p'.(int) $r->pnpp_id] = $r->waktu_masuk;
                }
                if (filled($r->no_hp)) {
                    $wa = PhoneFormat::toWa((string) $r->no_hp);
                    if ($wa !== null) {
                        $balas['w'.$wa] = $r->waktu_masuk;
                    }
                }
            });

        $diFollowUp = MessageLog::query()
            ->jenis('follow_up')
            ->where('status', '!=', 'dibatalkan')
            ->where('created_at', '>=', $sejak)
            ->whereNotNull('pnpp_id')
            ->pluck('pnpp_id')
            ->map(fn ($id) => (int) $id)
            ->flip()
            ->all();

        return $logs
            ->filter(function (MessageLog $log) use ($balas, $diFollowUp) {
                $pnpp = $log->pnpp;
                if ($pnpp === null || isset($diFollowUp[(int) $pnpp->id])) {
                    return false;
                }

                $wa = PhoneFormat::toWa($pnpp->no_hp);
                if ($wa === null) {
                    return false;
                }

                $terkirim = $log->sent_at ?? $log->created_at;
                $balasan = $balas['p'.(int) $pnpp->id] ?? $balas['w'.$wa] ?? null;

                return ! ($balasan !== null && $balasan->gte($terkirim));
            })
            ->map(fn (MessageLog $log) => [
                'pnpp' => $log->pnpp,
                'kategori' => self::SARAN_OUTREACH_BELUM_BALAS,
                'alasan' => 'Outreach "'.($log->template->judul ?? 'pesan').'" terkirim '
                    .(($log->sent_at ?? $log->created_at)?->format('d/m') ?? '')
                    .', belum dibalas.',
            ])
            ->values();
    }

    /**
     * Riwayat pesan follow up — data nyata, dengan ringkasan status,
     * pencarian, filter status/aturan (termasuk manual), dan pembatalan.
     */
    public function index(Request $request)
    {
        $q = (string) $request->query('q', '');
        $status = (string) $request->query('status', '');
        $rule = (string) $request->query('rule', '');
        $poliId = $request->user()?->poliId();

        // Akun poli hanya melihat pesan hasil generate dari reminder
        // polinya sendiri.
        $scopePoli = fn ($query) => $query->when(
            $poliId !== null,
            fn ($sub) => $sub->whereHas('reminder', fn ($reminder) => $reminder->where('poli_id', $poliId)),
        );

        $logs = MessageLog::query()
            ->jenis('follow_up')
            ->with('template:id,judul', 'reminder.poli:id,nama')
            ->tap($scopePoli)
            ->when($q, fn ($query) => $query->where(
                fn ($sub) => $sub->where('penerima_nama', 'like', "%{$q}%")
                    ->orWhere('penerima_no_hp', 'like', "%{$q}%")
                    ->orWhere('konten', 'like', "%{$q}%")
            ))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($rule, fn ($query) => $query->where('rule', $rule))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $perStatus = MessageLog::jenis('follow_up')
            ->tap($scopePoli)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $saranPenerima = $this->saranPenerima($request);

        return view('admin.follow-up.index', [
            'logs' => $logs,
            'perStatus' => $perStatus,
            'penerimaUnik' => MessageLog::jenis('follow_up')->tap($scopePoli)->distinct()->count('pnpp_id'),
            'total' => (int) $perStatus->sum(),
            'filters' => ['q' => $q, 'status' => $status, 'rule' => $rule],
            'saranPenerima' => $saranPenerima,
            'saranBelumHadir' => $saranPenerima
                ->filter(fn ($s) => in_array(self::SARAN_BELUM_HADIR, $s['kategori'], true))
                ->values()
                ->all(),
            'saranOutreach' => $saranPenerima
                ->filter(fn ($s) => in_array(self::SARAN_OUTREACH_BELUM_BALAS, $s['kategori'], true))
                ->values()
                ->all(),
        ]);
    }

    /**
     * Generate pesan follow up (H-1, hari-H, tidak-datang) dari
     * penjadwalan aktif — sekaligus menyapu status penjadwalan lewat
     * tanpa kunjungan.
     */
    public function generate(Request $request)
    {
        $service = app(BroadcastService::class);
        $poliId = $request->user()?->poliId();

        $ditandai = $service->sweepStatus($poliId);
        $hasil = $service->generate('follow_up', $request->user(), $poliId);

        $pesan = $hasil['dibuat'].' pesan follow up dibuat, '
            .$hasil['dilewati'].' dilewati (sudah pernah dibuat).';

        if ($ditandai > 0) {
            $pesan .= " {$ditandai} penjadwalan lewat tanpa kunjungan ditandai tidak datang.";
        }

        return redirect()
            ->route('admin.follow-up.index')
            ->with('success', $pesan);
    }

    protected function jenisManual(): string
    {
        return 'follow_up';
    }

    protected function kategoriManual(): ?string
    {
        return null;
    }

    protected function viewManual(): string
    {
        return 'admin.follow-up.manual';
    }

    protected function viewEdit(): string
    {
        return 'admin.follow-up.edit';
    }

    protected function routeIndex(): string
    {
        return 'admin.follow-up.index';
    }
}

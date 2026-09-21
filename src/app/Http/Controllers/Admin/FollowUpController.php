<?php

namespace App\Http\Controllers\Admin;

use App\Broadcasting\BroadcastService;
use App\Broadcasting\PhoneFormat;
use App\Models\MessageLog;
use App\Models\MessageReply;
use App\Models\Pnpp;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
     * Pasien punya jadwal terjadwal hari ini/mendatang tanpa kunjungan.
     */
    public const SARAN_BELUM_BERKUNJUNG = 'belum_berkunjung';

    /**
     * Label template saat sumber saran tidak punya template terpasang.
     */
    public const TEMPLATE_TANPA = 'Tanpa template';

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
     * Konfigurasi saran follow up — gabungan tiga kategori saringan:
     * 1) pasien yang jadwalnya lewat tanpa kunjungan (tidak hadir),
     * 2) pasien yang jadwalnya hari ini/mendatang namun belum berkunjung,
     * 3) pasien yang outreach-nya terkirim namun belum membalas.
     * Satu pasien boleh memenuhi beberapa sekaligus (kategori, alasan &
     * template sumber digabung). Pasien yang sudah di-follow up pada jendela
     * saran dikecualikan supaya tidak dobel, dan yang nomor WhatsApp-nya
     * tidak valid tidak disarankan.
     *
     * @return Collection<int, array{pnpp: Pnpp, kategori: array<int, string>, alasan: array<int, string>, template: array<int, string>}>
     */
    protected function saranPenerima(Request $request): Collection
    {
        $kandidat = $this->reminderBelumHadir()
            ->concat($this->reminderBelumBerkunjung())
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
                    'template' => [],
                ]);
            }
            $idx = $perId[$id];
            $rek = $gabung->get($idx);
            $rek['kategori'][] = $item['kategori'];
            $rek['alasan'][] = $item['alasan'];
            $rek['template'][] = $item['template'] ?? self::TEMPLATE_TANPA;
            $gabung->put($idx, $rek);
        }

        // Prioritas: belum hadir > belum berkunjung > outreach, lalu abjad nama.
        return $gabung->sortBy([
            fn ($a) => in_array(self::SARAN_BELUM_HADIR, $a['kategori'], true) ? 0
                : (in_array(self::SARAN_BELUM_BERKUNJUNG, $a['kategori'], true) ? 1 : 2),
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
            ->with('pnpp.satker:id,nama', 'poli:id,nama', 'messageTemplate:id,judul')
            ->where('home_visit', false)
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
                    'template' => $r->messageTemplate?->judul ?? self::TEMPLATE_TANPA,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Kandidat saran "belum berkunjung": jadwal Digital Reminder berstatus
     * terjadwal untuk hari ini atau mendatang yang belum tercatat
     * kunjungannya. Ambil jadwal terdekat per pasien; yang sudah di-follow
     * up pada jendela saran dikecualikan.
     *
     * @return Collection<int, array{pnpp: Pnpp, kategori: string, alasan: string}>
     */
    protected function reminderBelumBerkunjung(): Collection
    {
        $sejak = now()->subDays(7)->startOfDay();
        $diFollowUp = $this->diFollowUpDalamJendela($sejak);
        $sudahBalas = $this->balasanDalamJendela($sejak);

        $jadwal = Reminder::query()
            ->with('pnpp.satker:id,nama', 'poli:id,nama', 'messageTemplate:id,judul')
            ->where('home_visit', false)
            ->where('status', 'terjadwal')
            ->whereDate('tanggal', '>=', today()->toDateString())
            ->whereDoesntHave('kunjungan')
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->get()
            ->unique('pnpp_id');

        return $jadwal
            ->map(function (Reminder $r) use ($diFollowUp, $sudahBalas) {
                $pnpp = $r->pnpp;
                if ($pnpp === null
                    || isset($diFollowUp[(int) $pnpp->id])
                    || isset($sudahBalas[(int) $pnpp->id])
                    || PhoneFormat::toWa($pnpp->no_hp) === null) {
                    return null;
                }

                return [
                    'pnpp' => $pnpp,
                    'kategori' => self::SARAN_BELUM_BERKUNJUNG,
                    'alasan' => 'Jadwal '.($r->poli?->nama ?? 'poli').' '
                        .($r->tanggal?->locale('id')->translatedFormat('l, d F Y') ?? '—')
                        .' hari ini/mendatang, belum berkunjung.',
                    'template' => $r->messageTemplate?->judul ?? self::TEMPLATE_TANPA,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Pnpp yang sudah membalas pesan apapun pada jendela saran — dianggap
     * sudah merespons (engaged) sehingga tidak perlu disarankan kembali.
     *
     * @return array<int, true>
     */
    protected function balasanDalamJendela(Carbon $sejak): array
    {
        return MessageReply::query()
            ->whereNotNull('pnpp_id')
            ->where('waktu_masuk', '>=', $sejak)
            ->pluck('pnpp_id')
            ->map(fn ($id) => (int) $id)
            ->flip()
            ->all();
    }

    /**
     * Pnpp yang sudah mendapat pesan follow up pada jendela saran
     * (mengikuti outreachBelumDibalas: 7 hari terakhir, status bukan
     * dibatalkan) — dipakai untuk mencegah saran dobel.
     *
     * @return array<int, true>
     */
    protected function diFollowUpDalamJendela(Carbon $sejak): array
    {
        return MessageLog::query()
            ->jenis('follow_up')
            ->where('status', '!=', 'dibatalkan')
            ->where('created_at', '>=', $sejak)
            ->whereNotNull('pnpp_id')
            ->pluck('pnpp_id')
            ->map(fn ($id) => (int) $id)
            ->flip()
            ->all();
    }

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

        $diFollowUp = $this->diFollowUpDalamJendela($sejak);

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
                'template' => $log->template?->judul ?? self::TEMPLATE_TANPA,
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
        $qAtas = strtoupper($q);
        $status = (string) $request->query('status', '');
        $rule = (string) $request->query('rule', '');
        // Rentang tanggal dibuat: filter riwayat pesan follow up.
        $tanggalAwal = $this->tanggalQuery($request, 'tanggal_awal');
        $tanggalAkhir = $this->tanggalQuery($request, 'tanggal_akhir');
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
                fn ($sub) => $sub->whereRaw('UPPER(penerima_nama) LIKE ?', ["%{$qAtas}%"])
                    ->orWhere('penerima_no_hp', 'like', "%{$q}%")
                    ->orWhere('konten', 'like', "%{$q}%")
            ))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($rule, fn ($query) => $query->where('rule', $rule))
            ->when($tanggalAwal, fn ($query) => $query->whereDate('created_at', '>=', $tanggalAwal))
            ->when($tanggalAkhir, fn ($query) => $query->whereDate('created_at', '<=', $tanggalAkhir))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $perStatus = MessageLog::jenis('follow_up')
            ->tap($scopePoli)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $saranPenerima = $this->saranPenerima($request);

        // Tampil per template: saring saran sesuai template yang dipilih
        // (penuh/persis — label 'Tanpa template' untuk sumber tanpa template).
        $saranTemplate = (string) $request->query('template', '');
        $cocokTemplate = fn (array $s) => $saranTemplate === ''
            || in_array($saranTemplate, $s['template'] ?? [], true);

        $saranTemplates = $saranPenerima
            ->flatMap(fn ($s) => array_values(array_unique($s['template'] ?? [])))
            ->countBy()
            ->sortKeys()
            ->all();

        return view('admin.follow-up.index', [
            'logs' => $logs,
            'perStatus' => $perStatus,
            'penerimaUnik' => MessageLog::jenis('follow_up')->tap($scopePoli)->distinct()->count('pnpp_id'),
            'total' => (int) $perStatus->sum(),
            'filters' => ['q' => $q, 'status' => $status, 'rule' => $rule, 'tanggal_awal' => $tanggalAwal, 'tanggal_akhir' => $tanggalAkhir],
            'saranPenerima' => $saranPenerima,
            'currentSaranTemplate' => $saranTemplate,
            'saranTemplates' => $saranTemplates,
            'saranBelumHadir' => $saranPenerima
                ->filter(fn ($s) => $cocokTemplate($s) && in_array(self::SARAN_BELUM_HADIR, $s['kategori'], true))
                ->values()
                ->all(),
            'saranOutreach' => $saranPenerima
                ->filter(fn ($s) => $cocokTemplate($s) && in_array(self::SARAN_OUTREACH_BELUM_BALAS, $s['kategori'], true))
                ->values()
                ->all(),
            'saranBelumBerkunjung' => $saranPenerima
                ->filter(fn ($s) => $cocokTemplate($s) && in_array(self::SARAN_BELUM_BERKUNJUNG, $s['kategori'], true))
                ->values()
                ->all(),
        ]);
    }

    /**
     * Baca parameter tanggal query (format Y-m-d); null bila kosong atau
     * formatnya tidak valid, supaya tidak menjatuhkan query.
     */
    protected function tanggalQuery(Request $request, string $param): ?string
    {
        $nilai = (string) $request->query($param, '');

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai) === 1 ? $nilai : null;
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

    /**
     * Jadwal Home Visit tidak termasuk calon penerima follow up manual.
     */
    protected function saringTargetJadwal(): \Closure
    {
        return fn ($query) => $query->where('home_visit', false);
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

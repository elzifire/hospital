<?php

namespace App\Http\Controllers\Admin;

use App\Broadcasting\BroadcastService;
use App\Broadcasting\MessageRenderer;
use App\Broadcasting\PesanFactory;
use App\Broadcasting\PhoneFormat;
use App\Http\Controllers\Controller;
use App\Jobs\KirimPesanJob;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\Reminder;
use App\Models\Satker;
use App\Support\TextSanitizer;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Basis bersama alur "kirim pesan manual" modul broadcast (Outreach dan
 * Follow Up): pilih target PNPP, pilih template (bisa disaring per
 * kategori seeder), isi variabel, lalu kirim/jadwalkan. Subclass hanya
 * melengkapi konfigurasi jenis, kategori template, view, dan route
 * kembali ke modulnya.
 *
 * Setiap sesi kirim manual menghasilkan satu grup (kirim_group). Pesan
 * berstatus menunggu masih bisa diubah target, template & variabelnya.
 */
abstract class ManualBroadcastController extends Controller
{
    /**
     * Jenis MessageLog yang dibuat modul ini ('outreach' | 'follow_up').
     */
    abstract protected function jenisManual(): string;

    /**
     * Slug TemplateCategory yang menyaring opsi template form kirim
     * manual; null = tampilkan semua template aktif.
     */
    abstract protected function kategoriManual(): ?string;

    abstract protected function viewManual(): string;

    abstract protected function viewEdit(): string;

    abstract protected function routeIndex(): string;

    /**
     * Form kirim pesan manual (GET) — saring pasien PNPP lalu pilih satu
     * atau beberapa pasien sekaligus (termasuk "centang semua" lewat
     * daftar penerima yang nomor WhatsApp-nya valid). Pesan bisa memakai
     * template terpilih dengan variabel yang diisi manual atau otomatis.
     */
    public function create(Request $request)
    {
        $saranPenerima = $this->saranPenerima($request);

        // Hook "sasar" dari halaman index (mis. Follow Up): tangkap saran
        // dengan kategori tertentu dan preselected-kan sebagai penerima.
        // Bila ada "template", saring hanya saran ber-template itu.
        $sasar = (string) $request->query('sasar', '');
        $sasarTemplate = (string) $request->query('template', '');
        $sasarIds = [];
        if (in_array($sasar, ['belum_hadir', 'outreach_belum_balas', 'belum_berkunjung'], true)) {
            $sasarIds = $saranPenerima
                ->filter(fn ($s) => in_array($sasar, (array) ($s['kategori'] ?? []), true)
                    && ($sasarTemplate === '' || in_array($sasarTemplate, (array) ($s['template'] ?? []), true)))
                ->map(fn ($s) => (int) $s['pnpp']->id)
                ->values()
                ->all();
        }

        [$pnpps, $canKirimIds] = $this->kumpulanTarget($request, $sasarIds);

        return view($this->viewManual(), [
            'pnpps' => $pnpps,
            'satkers' => Satker::orderBy('nama')->get(['id', 'nama']),
            'filters' => [
                'q' => (string) $request->query('q', ''),
                'satker' => (string) $request->query('satker', ''),
                'tanggal' => (string) $request->query('tanggal', ''),
                'tampilkan' => (string) $request->query('tampilkan', 'berjadwal'),
            ],
            'canKirimIds' => $canKirimIds,
            'templates' => $this->templateOptions(),
            'selectedIds' => $sasarIds,
            'templateId' => null,
            'varsAwal' => [],
            'reminders' => $this->pilihanReminder($pnpps),
            'reminderAwal' => [],
            'pnppData' => $this->dataPnpp($pnpps),
            'poliOptions' => $this->poliOptions(),
            'jadwalMap' => $this->jadwalTerdekat($pnpps),
            'sudahDikirimHariIni' => $this->sudahDikirimHariIni($pnpps),
            'saranPenerima' => $saranPenerima,
        ]);
    }

    /**
     * Kirim pesan manual (POST) ke satu atau beberapa pasien: "sekarang"
     * langsung terkirim, atau "jadwalkan" untuk dikirim otomatis oleh
     * sistem pada waktu yang dipilih. Setiap pasien mendapat satu pesan.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'pnpp_ids' => ['required', 'array', 'min:1'],
            'pnpp_ids.*' => ['integer', Rule::exists('pnpps', 'id')],
            'jenis' => ['required', Rule::in(MessageLog::JENIS)],
            'mode' => ['nullable', Rule::in(['sekarang', 'jadwalkan'])],
            'kirim_pada' => ['nullable', 'required_if:mode,jadwalkan', 'date', 'after:now'],
            'message_template_id' => ['required', 'integer', Rule::exists('message_templates', 'id')->where('is_active', true)],
            'vars' => ['nullable', 'array'],
            'vars.*' => ['nullable', 'string', 'max:255'],
            'reminder_ids' => ['nullable', 'array'],
            'reminder_ids.*' => ['nullable', 'integer', Rule::exists('reminders', 'id')],
        ]);

        $mode = $data['mode'] ?? 'sekarang';

        // Form mengirim jenis pesan miliknya sendiri; kalau mencoba jenis
        // lain, izin modul itu tetap wajib (dicek per route).
        if ($data['jenis'] === 'follow_up') {
            abort_unless($request->user()->can('manage follow-up'), 403);
        }

        $template = MessageTemplate::findOrFail($data['message_template_id']);

        $varsKustom = $this->varsBersih((array) ($data['vars'] ?? []));
        $kirimGroup = (string) Str::uuid();

        $pnpps = Pnpp::query()->whereIn('id', $data['pnpp_ids'])->get();
        $reminders = $this->remindersTerpilih((array) ($data['reminder_ids'] ?? []));

        $logs = $pnpps->map(function (Pnpp $pnpp) use ($request, $data, $mode, $template, $varsKustom, $kirimGroup, $reminders) {
            $noHp = PhoneFormat::toWa($pnpp->no_hp);
            $reminder = $this->reminderUntukPnpp($reminders, $data['reminder_ids'] ?? [], $pnpp);
            $vars = $varsKustom;

            // Beberapa jadwal di hari yang sama digabung jadi satu pesan
            // (tanggal sekali, waktu & poli disambung " & ") — hemat blast.
            $gabungan = $this->nilaiGabungan($reminder, $pnpp);
            if ($gabungan !== []) {
                $vars += $gabungan;
            }

            $atribut = app(PesanFactory::class)->atribut($template, $pnpp, $reminder, $data['jenis'], 'manual', $request->user(), $vars);

            Log::channel('whatsapp')->debug('ManualBroadcastController::store', [
                'jenis' => $data['jenis'],
                'mode' => $mode,
                'pnpp_id' => $pnpp->id,
                'template_id' => $template->id,
                'konten_template' => (string) $template->konten,
                'token_template' => $template->tokenParam(),
                'reminder_id' => $reminder?->id,
                'gabungan_jadwal' => $gabungan !== [] ? $gabungan : null,
                'vars_kustom' => $varsKustom,
                'konten_terrender' => (string) ($atribut['konten'] ?? ''),
                'template_params' => $atribut['template_params'] ?? [],
            ]);

            return MessageLog::create([
                ...$atribut,
                'kirim_group' => $kirimGroup,
                'kirim_pada' => $mode === 'jadwalkan' && $noHp !== null
                    ? CarbonImmutable::parse($data['kirim_pada'])
                    : null,
                'konten' => TextSanitizer::win1252((string) $atribut['konten']),
                'template_params' => $this->bersihkanParams($atribut['template_params'] ?? []),
            ]);
        });

        $menunggu = $logs->where('status', 'menunggu');
        $tanpaNomor = $logs->where('status', 'gagal');

        $tujuan = $data['jenis'] === 'follow_up' ? 'admin.follow-up.index' : 'admin.outreach.index';
        $tambahan = $tanpaNomor->isNotEmpty()
            ? $tanpaNomor->count().' pasien tidak bisa dikirimi karena nomor WhatsApp tidak valid.'
            : null;

        if ($mode === 'jadwalkan') {
            $waktu = CarbonImmutable::parse($data['kirim_pada']);

            return redirect()
                ->route($tujuan)
                ->with(
                    $menunggu->isNotEmpty() ? 'success' : 'error',
                    implode(' ', array_filter([
                        $menunggu->isNotEmpty()
                            ? 'Pesan untuk '.$menunggu->count().' pasien dijadwalkan terkirim '.$waktu->translatedFormat('l, d F Y').' pukul '.$waktu->format('H:i').'.'
                            : null,
                        $tambahan,
                    ])) ?: 'Tidak ada pesan yang bisa dijadwalkan.',
                );
        }

        // Kirim sekarang menjadi async: pesan langsung diantrekan lewat
        // queue dan dikirim worker di background — form cepat kembali walau
        // banyak penerima, tanpa timeout di sisi frontend.
        $diantrekan = 0;
        foreach ($menunggu as $log) {
            KirimPesanJob::dispatch($log->id);
            $diantrekan++;
        }

        $pesan = implode(' ', array_filter([
            $diantrekan > 0
                ? 'Pesan untuk '.$diantrekan.' pasien masuk antrean pengiriman dan akan dikirim otomatis sesaat lagi.'
                : null,
            $tambahan,
        ]));

        return redirect()
            ->route($tujuan)
            ->with($diantrekan > 0 ? 'success' : 'error', $pesan ?: 'Tidak ada pesan yang perlu dikirim.');
    }

    /**
     * Form ubah pesan manual (GET) — target, template & variabel pesan
     * yang masih berstatus "menunggu" bisa diperbaiki sekaligus.
     *
     * @param  string  $group  uuid kirim_group
     */
    public function edit(Request $request, string $group)
    {
        $logs = $this->grupMenunggu($group);

        $targetIds = $logs->pluck('pnpp_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();

        // Jadwal yang sudah dirujuk pesan di grup ini ikut tampil di
        // dropdown walau statusnya sudah bukan "terjadwal".
        $refReminderIds = $logs->pluck('reminder_id')->filter()->map(fn ($id) => (int) $id)->values();
        $reminderAwal = $logs->whereNotNull('reminder_id')
            ->pluck('reminder_id', 'pnpp_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        // Target yang sudah dipilih TETAP tampil walau tidak cocok filter,
        // supaya tidak tersapu diam-diam saat menyimpan.
        [$pnpps, $canKirimIds] = $this->kumpulanTarget($request, $targetIds);

        $templateId = $logs->first()->message_template_id;
        $template = $templateId !== null ? MessageTemplate::find($templateId) : null;

        $varsAwal = [];
        if ($template !== null) {
            foreach ($template->tokenParam() as $i => $token) {
                $params = (array) ($logs->first()->template_params ?? []);
                if (array_key_exists($i, $params)) {
                    $varsAwal[$token] = (string) $params[$i];
                }
            }
        }

        return view($this->viewEdit(), [
            'kirimGroup' => $group,
            'pnpps' => $pnpps,
            'satkers' => Satker::orderBy('nama')->get(['id', 'nama']),
            'filters' => [
                'q' => (string) $request->query('q', ''),
                'satker' => (string) $request->query('satker', ''),
                'tanggal' => (string) $request->query('tanggal', ''),
                'tampilkan' => (string) $request->query('tampilkan', 'berjadwal'),
            ],
            'canKirimIds' => $canKirimIds,
            'templates' => $this->templateOptions(),
            'selectedIds' => $targetIds,
            'templateId' => $templateId,
            'varsAwal' => $varsAwal,
            'reminders' => $this->pilihanReminder($pnpps, $refReminderIds),
            'reminderAwal' => $reminderAwal,
            'pnppData' => $this->dataPnpp($pnpps),
            'poliOptions' => $this->poliOptions(),
            'jadwalMap' => $this->jadwalTerdekat($pnpps),
            'sudahDikirimHariIni' => $this->sudahDikirimHariIni($pnpps, $group),
            'saranPenerima' => $this->saranPenerima($request),
        ]);
    }

    /**
     * Simpan perubahan pesan manual: perbarui template/variabel untuk
     * target yang dipertahankan, batalkan yang dilepas, dan buat pesan
     * baru (satu grup) untuk target yang baru dipilih.
     *
     * @param  string  $group  uuid kirim_group
     */
    public function update(Request $request, string $group)
    {
        $data = $request->validate([
            'pnpp_ids' => ['required', 'array', 'min:1'],
            'pnpp_ids.*' => ['integer', Rule::exists('pnpps', 'id')],
            'message_template_id' => ['required', 'integer', Rule::exists('message_templates', 'id')->where('is_active', true)],
            'vars' => ['nullable', 'array'],
            'vars.*' => ['nullable', 'string', 'max:255'],
            'reminder_ids' => ['nullable', 'array'],
            'reminder_ids.*' => ['nullable', 'integer', Rule::exists('reminders', 'id')],
        ]);

        $logs = $this->grupMenunggu($group);

        $template = MessageTemplate::findOrFail($data['message_template_id']);

        $varsKustom = $this->varsBersih((array) ($data['vars'] ?? []));
        $user = $request->user();
        $reminders = $this->remindersTerpilih((array) ($data['reminder_ids'] ?? []));

        $kini = $logs->pluck('pnpp_id')->filter()->map(fn ($id) => (int) $id)->all();
        $pilih = array_map('intval', $data['pnpp_ids']);
        $ditahan = array_values(array_intersect($kini, $pilih));
        $ditambah = array_values(array_diff($pilih, $kini));
        $dihapus = array_values(array_diff($kini, $pilih));

        foreach ($logs->whereIn('pnpp_id', $ditahan) as $log) {
            $reminder = $this->reminderUntukPnpp($reminders, $data['reminder_ids'] ?? [], $log->pnpp);
            $vars = $varsKustom;

            $gabungan = $this->nilaiGabungan($reminder, $log->pnpp);
            if ($gabungan !== []) {
                $vars += $gabungan;
            }

            $atribut = app(PesanFactory::class)->atribut($template, $log->pnpp, $reminder, $logs->first()->jenis, 'manual', $user, $vars);

            Log::channel('whatsapp')->debug('ManualBroadcastController::update', [
                'aksi' => 'perbarui',
                'kirim_group' => $group,
                'pnpp_id' => $log->pnpp_id,
                'template_id' => $template->id,
                'konten_template' => (string) $template->konten,
                'token_template' => $template->tokenParam(),
                'reminder_id' => $reminder?->id,
                'gabungan_jadwal' => $gabungan !== [] ? $gabungan : null,
                'vars_kustom' => $varsKustom,
                'konten_terrender' => (string) ($atribut['konten'] ?? ''),
                'template_params' => $atribut['template_params'] ?? [],
            ]);

            $log->update([
                'message_template_id' => $template->id,
                'reminder_id' => $reminder?->id,
                'konten' => TextSanitizer::win1252((string) $atribut['konten']),
                'meta_template_name' => $atribut['meta_template_name'],
                'meta_language' => $atribut['meta_language'],
                'template_params' => $this->bersihkanParams($atribut['template_params'] ?? []),
            ]);
        }

        if ($dihapus !== []) {
            app(BroadcastService::class)->batalkan(
                $logs->whereIn('pnpp_id', $dihapus)->pluck('id')->all()
            );
        }

        $kirimPada = $logs->first()->kirim_pada;

        foreach (Pnpp::query()->whereIn('id', $ditambah)->get() as $pnpp) {
            $reminder = $this->reminderUntukPnpp($reminders, $data['reminder_ids'] ?? [], $pnpp);
            $vars = $varsKustom;

            $gabungan = $this->nilaiGabungan($reminder, $pnpp);
            if ($gabungan !== []) {
                $vars += $gabungan;
            }

            $atribut = app(PesanFactory::class)->atribut($template, $pnpp, $reminder, $logs->first()->jenis, 'manual', $user, $vars);

            Log::channel('whatsapp')->debug('ManualBroadcastController::update', [
                'aksi' => 'tambah',
                'kirim_group' => $group,
                'pnpp_id' => $pnpp->id,
                'template_id' => $template->id,
                'konten_template' => (string) $template->konten,
                'token_template' => $template->tokenParam(),
                'reminder_id' => $reminder?->id,
                'gabungan_jadwal' => $gabungan !== [] ? $gabungan : null,
                'vars_kustom' => $varsKustom,
                'konten_terrender' => (string) ($atribut['konten'] ?? ''),
                'template_params' => $atribut['template_params'] ?? [],
            ]);

            MessageLog::create([
                ...$atribut,
                'kirim_group' => $group,
                'kirim_pada' => $kirimPada,
                'konten' => TextSanitizer::win1252((string) $atribut['konten']),
                'template_params' => $this->bersihkanParams($atribut['template_params'] ?? []),
            ]);
        }

        return redirect()
            ->route($this->routeIndex())
            ->with('success', $this->pesanUbah($ditahan, $ditambah, $dihapus));
    }

    /**
     * Daftar calon penerima PNPP sesuai filter (q/satker/tanggal jadwal),
     * hanya pasien yang masih punya jadwal Digital Reminder terjadwal,
     * diurutkan sesuai jadwalnya. Target yang sudah dipilih selalu ikut
     * tampil, plus deretan id yang nomor WhatsApp-nya valid untuk
     * "Pilih semua".
     *
     * @param  array<int, int>  $wajibTampil
     * @return array{0: Collection<int, Pnpp>, 1: array<int, int>}
     */
    protected function kumpulanTarget(Request $request, array $wajibTampil): array
    {
        $q = trim((string) $request->query('q', ''));
        $qAtas = strtoupper($q);
        $satkerId = (string) $request->query('satker', '');
        $tanggal = (string) $request->query('tanggal', '');
        // tampilkan=berjadwal (bawaan) → hanya PNPP yang punya jadwal
        // Digital Reminder terjadwal; tampilkan=semua → seluruh PNPP.
        $tampilkanSemua = (string) $request->query('tampilkan', 'berjadwal') === 'semua';
        $saring = ! $tampilkanSemua && $tanggal !== '' ? ' AND r.tanggal = ?' : '';
        $bindings = $saring !== '' ? ['terjadwal', $tanggal] : ['terjadwal'];

        $pnpps = Pnpp::query()
            ->with('satker:id,nama', 'latestKunjungan.poli:id,nama')
            ->where(function ($query) use ($wajibTampil, $q, $qAtas, $satkerId, $tanggal, $tampilkanSemua) {
                $query->where(function ($cocok) use ($q, $qAtas, $satkerId) {
                    $dibuka = false;

                    if ($q !== '') {
                        $cocok->where(fn ($cari) => $cari->whereRaw('UPPER(nama) LIKE ?', ["%{$qAtas}%"])
                            ->orWhere('nip', 'like', "%{$q}%")
                            ->orWhere('no_hp', 'like', "%{$q}%"));
                        $dibuka = true;
                    }

                    if ($satkerId !== '') {
                        $dibuka
                            ? $cocok->orWhere('satker_id', $satkerId)
                            : $cocok->where('satker_id', $satkerId);
                        $dibuka = true;
                    }

                    if (! $dibuka) {
                        $cocok->whereRaw('1 = 1');
                    }
                });

                // Hanya pasien yang masih punya jadwal Digital Reminder
                // terjadwal; filter tanggal mempersempit ke jadwal tanggal
                // itu. Mode "semua" melewati pembatasan ini.
                if (! $tampilkanSemua) {
                    $query->whereHas('reminders', function ($jadwal) use ($tanggal) {
                        ($this->saringTargetJadwal())(
                            $jadwal->where('status', 'terjadwal')
                                ->when($tanggal !== '', fn ($sub) => $sub->whereDate('tanggal', $tanggal)),
                        );
                    });
                }

                // Target yang sudah dipilih TETAP tampil walau tidak cocok
                // filter, supaya tidak tersapu diam-diam saat menyimpan.
                if ($wajibTampil !== []) {
                    $query->orWhereIn('id', $wajibTampil);
                }
            })
            ->when(! $tampilkanSemua, fn ($query) => $query
                ->orderByRaw('(SELECT MIN(r.tanggal) FROM reminders r WHERE r.pnpp_id = pnpps.id AND r.status = ?'.$saring.')', $bindings)
                ->orderByRaw('(SELECT MIN(r.jam) FROM reminders r WHERE r.pnpp_id = pnpps.id AND r.status = ?'.$saring.')', $bindings))
            ->orderBy('nama')
            ->get(['id', 'nama', 'nip', 'no_hp', 'satker_id'])
            ->unique('id')
            ->values();

        // Saringan khusus modul (mis. Follow Up: hanya yang belum membalas).
        $pnpps = $this->saringBelumBalas($pnpps, $wajibTampil);

        $canKirimIds = $pnpps
            ->filter(fn (Pnpp $p) => PhoneFormat::toWa($p->no_hp) !== null)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return [$pnpps, $canKirimIds];
    }

    /**
     * Saringan khusus modul atas kandidat penerima. Bawaan: tanpa
     * perubahan. Follow Up memakainya untuk hanya menampilkan PNPP yang
     * belum membalas pesan respon.
     *
     * @param  Collection<int, Pnpp>  $pnpps
     * @param  array<int, int>  $wajibTampil
     * @return Collection<int, Pnpp>
     */
    protected function saringBelumBalas(Collection $pnpps, array $wajibTampil): Collection
    {
        return $pnpps;
    }

    /**
     * Batasan atas query jadwal calon penerima di daftar target manual.
     * Bawaan: tanpa pembatasan. Follow Up memakainya untuk menyisihkan
     * jadwal Home Visit (kunjungan home visit tidak masuk follow up).
     */
    protected function saringTargetJadwal(): \Closure
    {
        return fn ($query) => $query;
    }

    /**
     * Saran khusus modul berupa daftar pasien yang disarankan sebagai
     * penerima pada halaman kirim manual, beserta alasan mengapa. Bawaan
     * kosong; modul (mis. Follow Up) bisa menimpanya untuk menampilkan
     * rekomendasi penerima di samping daftar hasil filter.
     *
     * @return Collection<int, array{pnpp: Pnpp, alasan: string}>
     */
    protected function saranPenerima(Request $request): Collection
    {
        return collect();
    }

    /**
     * Label jadwal terjadwal terdekat per PNPP untuk kolom "Jadwal" pada
     * daftar penerima; tanggal yang disaring ikut memperjelas jadwalnya.
     *
     * @param  Collection<int, Pnpp>  $pnpps
     * @return array<int, string> pnpp_id => label
     */
    protected function jadwalTerdekat(Collection $pnpps): array
    {
        if ($pnpps->isEmpty()) {
            return [];
        }

        return Reminder::query()
            ->with('poli:id,nama')
            ->whereIn('pnpp_id', $pnpps->pluck('id')->all())
            ->where('status', 'terjadwal')
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->get()
            ->groupBy('pnpp_id')
            ->mapWithKeys(fn ($grup, $pnppId) => [
                (int) $pnppId => ($grup->first()->tanggal?->format('d/m/Y') ?? '—').' '
                    .($grup->first()->jam?->format('H:i') ?? '—')
                    .' · '.($grup->first()->poli?->nama ?? '—'),
            ])
            ->all();
    }

    /**
     * Id PNPP yang sudah pernah dikirimi pesan modul ini hari ini — untuk
     * menandai pasien yang sudah di-outreach (atau di-follow up) di daftar
     * penerima. Grup yang sedang diedit bisa dikecualikan supaya targetnya
     * sendiri tidak ikut ditandai.
     *
     * @param  Collection<int, Pnpp>  $pnpps
     * @return array<int, int>
     */
    protected function sudahDikirimHariIni(Collection $pnpps, ?string $kecualiGroup = null): array
    {
        if ($pnpps->isEmpty()) {
            return [];
        }

        return MessageLog::query()
            ->jenis($this->jenisManual())
            ->whereIn('pnpp_id', $pnpps->pluck('id')->all())
            ->where('status', '!=', 'dibatalkan')
            ->whereDate('created_at', today())
            ->when($kecualiGroup !== null, fn ($query) => $query->where('kirim_group', '!=', $kecualiGroup))
            ->pluck('pnpp_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Opsi template aktif untuk dropdown form — dibatasi ke kategori modul
     * bila kategoriManual() diisi (mis. Follow Up hanya template kategori
     * Follow Up). Token/parameternya ikut disertakan supaya form bisa
     * menampilkan isian variabel.
     *
     * @return array<int, array{id: int, judul: string, konten: string, token: array<int, string>}>
     */
    protected function templateOptions(): array
    {
        return MessageTemplate::query()
            ->where('is_active', true)
            ->when($this->kategoriManual() !== null, function ($query) {
                $query->whereHas('category', fn ($cat) => $cat->where('slug', $this->kategoriManual()));
            })
            ->orderBy('judul')
            ->get(['id', 'judul', 'konten', 'template_category_id', 'image_url', 'meta_components'])
            ->map(fn (MessageTemplate $t) => [
                'id' => $t->id,
                'judul' => (string) $t->judul,
                'konten' => (string) $t->konten,
                'token' => $t->tokenParam(),
                'image_url' => $t->image_url,
                'buttons' => $this->tombolTemplate($t),
            ])
            ->values()
            ->all();
    }

    /**
     * Override variabel manual dari form: token → nilai (string),
     * kosong diabaikan (biarkan otomatis dari data pasien), semua
     * karakter luar WIN1252 dibersihkan sebelum disimpan.
     *
     * @param  array<string, mixed>  $vars
     * @return array<string, string>
     */
    protected function varsBersih(array $vars): array
    {
        $hasil = [];

        foreach ($vars as $token => $nilai) {
            if (is_string($nilai) && trim($nilai) !== '') {
                $hasil[strtolower(trim($token))] = TextSanitizer::win1252($nilai);
            }
        }

        return $hasil;
    }

    /**
     * Bersihkan nilai parameter template Meta sebelum disimpan.
     *
     * @return array<int, string>
     */
    protected function bersihkanParams(mixed $params): array
    {
        return array_values(array_map(
            fn ($nilai) => TextSanitizer::win1252((string) $nilai),
            (array) $params,
        ));
    }

    /**
     * Ambil semua pesan "menunggu" satu grup kirim manual modul ini;
     * grup yang tidak ada atau sudah terkirim dianggap tidak boleh diubah.
     *
     * @return Collection<int, MessageLog>
     */
    protected function grupMenunggu(string $group)
    {
        $logs = MessageLog::query()
            ->with('pnpp')
            ->where('kirim_group', $group)
            ->jenis($this->jenisManual())
            ->get();

        abort_if($logs->isEmpty(), 404);
        abort_if($logs->contains(fn (MessageLog $l) => $l->status !== 'menunggu'), 403);

        return $logs;
    }

    /**
     * Ambil jadwal Digital Reminder pilihan form (reminder_ids) untuk
     * mengisi variabel token template pengingat kunjungan.
     *
     * @param  array<int|string, mixed>  $pilihan
     * @return Collection<int, Reminder>
     */
    protected function remindersTerpilih(array $pilihan): Collection
    {
        $ids = array_values(array_unique(array_map(
            'intval',
            array_filter($pilihan, fn ($nilai) => filled($nilai)),
        )));

        return Reminder::query()->whereIn('id', $ids)->get()->keyBy('id');
    }

    /**
     * Jadwal yang sah untuk satu penerima: harus milik PNPP tersebut.
     * Jadwal milik pasien lain diabaikan (dianggap tidak dipilih).
     *
     * @param  Collection<int, Reminder>  $reminders
     * @param  array<int|string, mixed>  $pilihan
     */
    protected function reminderUntukPnpp(Collection $reminders, array $pilihan, Pnpp $pnpp): ?Reminder
    {
        $chosenId = (int) ($pilihan[(string) $pnpp->id] ?? 0);
        $reminder = $reminders->get($chosenId);

        return $reminder !== null && (int) $reminder->pnpp_id === (int) $pnpp->id ? $reminder : null;
    }

    /**
     * Nilai token gabungan ketika satu pasien punya beberapa jadwal pada
     * tanggal yang sama: digabung jadi SATU pesan (tanggal sekali, waktu
     * disambung " & ", poli/dokter disambung " & ") supaya hemat biaya
     * blast. Berlaku untuk kirim manual; alur generate otomatis tetap
     * per-jadwal. Mengembalikan array kosong bila jadwal acuan tidak ada
     * atau tidak ada jadwal lain di hari yang sama.
     *
     * @return array<string, string>
     */
    protected function nilaiGabungan(?Reminder $acuan, Pnpp $pnpp): array
    {
        if ($acuan === null || $acuan->tanggal === null) {
            return [];
        }

        $bersamaan = Reminder::query()
            ->with('poli:id,nama', 'dokter:id,nama')
            ->where('pnpp_id', $pnpp->id)
            ->where('status', 'terjadwal')
            ->whereDate('tanggal', $acuan->tanggal)
            ->orderBy('jam')
            ->get();

        if ($bersamaan->count() < 2) {
            return [];
        }

        $gabung = function (callable $ambil) use ($bersamaan): string {
            return $bersamaan->map($ambil)->filter()->unique()->values()->implode(' & ');
        };

        return [
            'hari_tanggal' => $acuan->tanggal->locale('id')->translatedFormat('l, d F Y'),
            'tanggal' => $acuan->tanggal->format('Y-m-d'),
            'waktu_kunjungan' => $gabung(fn (Reminder $r) => $r->jam?->format('H:i')),
            'jam' => $gabung(fn (Reminder $r) => $r->jam?->format('H:i')),
            'poli_layanan' => $gabung(fn (Reminder $r) => $r->poli?->nama),
            'poli' => $gabung(fn (Reminder $r) => $r->poli?->nama),
            'dokter' => $gabung(fn (Reminder $r) => $r->dokter?->nama),
        ];
    }

    /**
     * Daftar jadwal Digital Reminder untuk pelengkap variabel template
     * pengingat kunjungan, per penerima PNPP. Nilai token turunan
     * (hari_tanggal/waktu_kunjungan/poli_layanan) ikut disertakan agar
     * pratinjau pesan di form bisa menghitung isi pesan per penerima.
     *
     * @param  Collection<int, Pnpp>  $pnpps
     * @param  Collection<int, int>|array<int, int>  $wajib  id jadwal yang tetap ditampilkan
     * @return array<int, array{id: int, pnpp_id: int, label: string, nilai: array<string, string>}>
     */
    protected function pilihanReminder(Collection $pnpps, Collection|array $wajib = []): array
    {
        if ($pnpps->isEmpty()) {
            return [];
        }

        $jadwalIds = $wajib instanceof Collection ? $wajib->all() : $wajib;

        return Reminder::query()
            ->with('poli:id,nama', 'dokter:id,nama')
            ->whereIn('pnpp_id', $pnpps->pluck('id')->all())
            ->where(fn ($query) => $query->where('status', 'terjadwal')->orWhereIn('id', $jadwalIds))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Reminder $r) => [
                'id' => (int) $r->id,
                'pnpp_id' => (int) $r->pnpp_id,
                'label' => ($r->tanggal?->format('d/m/Y') ?? '—').' '.($r->jam?->format('H:i') ?? '—')
                    .' · '.($r->poli?->nama ?? '—')
                    .($r->status !== 'terjadwal' ? ' · '.ucfirst($r->status) : ''),
                'nilai' => [
                    'hari_tanggal' => (string) ($r->tanggal?->locale('id')->translatedFormat('l, d F Y') ?? ''),
                    'waktu_kunjungan' => (string) ($r->jam?->format('H:i') ?? ''),
                    'poli_layanan' => (string) ($r->poli?->nama ?? ''),
                    'poli' => (string) ($r->poli?->nama ?? ''),
                    'dokter' => (string) ($r->dokter?->nama ?? ''),
                    'tanggal' => (string) ($r->tanggal?->format('Y-m-d') ?? ''),
                    'jam' => (string) ($r->jam?->format('H:i') ?? ''),
                ],
            ])
            ->values()
            ->all();
    }

    /**
     * Data pasien untuk pratinjau pesan di sisi frontend ({nama}, {nip},
     * {satker}, {poli}/{instalasi}, {tanggal}) — poli & tanggal dari
     * kunjungan terakhir pasien bila tersedia. Alias token per-pasien
     * ({nama_pasien}, {nomor_hp}, …) ikut disertakan supaya pratinjau dan
     * nilai otomatis frontend konsisten dengan render di server.
     *
     * @param  Collection<int, Pnpp>  $pnpps
     * @return array<int, array<string, string>>
     */
    protected function dataPnpp(Collection $pnpps): array
    {
        return $pnpps->mapWithKeys(function (Pnpp $p) {
            $kunjungan = $p->latestKunjungan;
            $poli = (string) ($kunjungan?->poli?->nama ?? '');
            $tanggal = $kunjungan?->tanggal_kunjungan;

            $data = [
                'nama' => (string) ($p->nama ?? '—'),
                'nip' => (string) ($p->nip ?? ''),
                'satker' => (string) ($p->satker?->nama ?? ''),
                'no_hp' => (string) ($p->no_hp ?? ''),
                'poli' => $poli,
                'instalasi' => $poli,
                'tanggal' => (string) ($tanggal?->format('Y-m-d') ?? ''),
                'hari_tanggal' => (string) ($tanggal?->locale('id')->translatedFormat('l, d F Y') ?? ''),
            ];

            foreach (MessageRenderer::aliasPnpp() as $alias => $asli) {
                $data[$alias] ??= $data[$asli] ?? '';
            }

            return [(string) $p->id => $data];
        })->all();
    }

    protected function poliOptions(): array
    {
        return Poli::orderBy('nama')->pluck('nama')->map(fn ($n) => (string) $n)->values()->all();
    }

    /**
     * Ekstrak daftar tombol dari komponen BUTTONS template Meta untuk
     * ditampilkan di form outreach (info tombol + redirect URL untuk
     * tipe URL / QUICK_REPLY).
     *
     * @return array<int, array{type: string, text: string, sub_type?: string, url?: string, payload?: string}>
     */
    protected function tombolTemplate(MessageTemplate $template): array
    {
        $tombol = [];

        foreach ((array) ($template->meta_components ?? []) as $komponen) {
            if (strtoupper((string) ($komponen['type'] ?? '')) !== 'BUTTONS') {
                continue;
            }

            foreach ((array) ($komponen['buttons'] ?? []) as $btn) {
                $tombol[] = [
                    'type' => (string) ($btn['type'] ?? 'QUICK_REPLY'),
                    'text' => (string) ($btn['text'] ?? ''),
                    'url' => (string) ($btn['url'] ?? ''),
                    'payload' => (string) (is_array($btn['parameters'][0] ?? null) ? ($btn['parameters'][0]['text'] ?? '') : ''),
                ];
            }
        }

        return $tombol;
    }

    protected function pesanUbah(array $ditahan, array $ditambah, array $dihapus): string
    {
        return implode(' ', array_filter([
            $ditahan !== [] ? count($ditahan).' pesan diperbarui.' : null,
            $ditambah !== [] ? count($ditambah).' target baru ditambahkan.' : null,
            $dihapus !== [] ? count($dihapus).' target dilepas (dibatalkan).' : null,
        ]));
    }
}

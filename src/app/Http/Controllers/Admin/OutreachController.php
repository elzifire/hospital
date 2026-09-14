<?php

namespace App\Http\Controllers\Admin;

use App\Broadcasting\BroadcastService;
use App\Broadcasting\PesanFactory;
use App\Broadcasting\PhoneFormat;
use App\Broadcasting\WhatsApp\AntreanKirim;
use App\Http\Controllers\Controller;
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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Modul Outreach — riwayat pesan undangan jadwal (rule H-7 & H-1) yang
 * digenerate dari penjadwalan Digital Reminder, plus form kirim pesan
 * manual: target (nama/NIP/NRP/no HP) dari data PNPP, boleh dikaitkan
 * ke template pesan dan variabelnya bisa diisi/ubah manual.
 *
 * Setiap sesi kirim manual menghasilkan satu grup (kirim_group). Pesan
 * berstatus menunggu masih bisa diubah target, template & variabelnya.
 */
class OutreachController extends Controller
{
    /**
     * Riwayat pesan outreach — data nyata, dengan ringkasan status,
     * pencarian, filter status/aturan, dan pembatalan.
     */
    public function index(Request $request)
    {
        $q = (string) $request->query('q', '');
        $status = (string) $request->query('status', '');
        $rule = (string) $request->query('rule', '');

        $logs = MessageLog::query()
            ->jenis('outreach')
            ->with('template:id,judul', 'reminder.poli:id,nama')
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

        $perStatus = MessageLog::jenis('outreach')
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.outreach.index', [
            'logs' => $logs,
            'perStatus' => $perStatus,
            'penerimaUnik' => MessageLog::jenis('outreach')->distinct()->count('pnpp_id'),
            'total' => (int) $perStatus->sum(),
            'filters' => ['q' => $q, 'status' => $status, 'rule' => $rule],
        ]);
    }

    /**
     * Generate pesan outreach (H-7 & H-1) dari penjadwalan aktif —
     * sekaligus menyapu status penjadwalan lewat tanpa kunjungan.
     * Pesan berstatus menunggu, lalu dikirim otomatis oleh scheduler
     * (broadcast:kirim tiap menit) atau tombol "Kirim Sekarang".
     */
    public function generate(Request $request)
    {
        $service = app(BroadcastService::class);

        $ditandai = $service->sweepStatus();
        $hasil = $service->generate('outreach', $request->user());

        $pesan = $hasil['dibuat'].' pesan outreach dibuat, '
            .$hasil['dilewati'].' dilewati (sudah pernah dibuat). '
            .'Pesan menunggu dikirim otomatis tiap menit, atau tekan "Kirim Sekarang".';

        if ($ditandai > 0) {
            $pesan .= " {$ditandai} penjadwalan lewat tanpa kunjungan ditandai tidak datang.";
        }

        return redirect()
            ->route('admin.outreach.index')
            ->with('success', $pesan);
    }

    /**
     * Form kirim pesan manual (GET) — saring pasien PNPP lalu pilih satu
     * atau beberapa pasien sekaligus (termasuk "centang semua" lewat
     * daftar penerima yang nomor WhatsApp-nya valid). Pesan bisa memakai
     * template terpilih dengan variabel yang diisi manual atau otomatis.
     */
    public function create(Request $request)
    {
        [$pnpps, $canKirimIds] = $this->kumpulanTarget($request, []);

        return view('admin.outreach.manual', [
            'pnpps' => $pnpps,
            'satkers' => Satker::orderBy('nama')->get(['id', 'nama']),
            'filters' => ['q' => (string) $request->query('q', ''), 'satker' => (string) $request->query('satker', '')],
            'canKirimIds' => $canKirimIds,
            'templates' => $this->templateOptions(),
            'selectedIds' => [],
            'templateId' => null,
            'varsAwal' => [],
            'reminders' => $this->pilihanReminder($pnpps),
            'reminderAwal' => [],
            'pnppData' => $this->dataPnpp($pnpps),
            'poliOptions' => $this->poliOptions(),
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

        // Route digate "manage outreach" — jenis follow up butuh izinnya sendiri.
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
            $atribut = app(PesanFactory::class)->atribut($template, $pnpp, $reminder, $data['jenis'], 'manual', $request->user(), $varsKustom);

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

        $hasil = $menunggu->isEmpty()
            ? ['terkirim' => 0, 'gagal' => 0, 'dilewati' => 0]
            : app(AntreanKirim::class)->kirimSinkron($menunggu->all());

        $pesan = implode(' ', array_filter([
            ($hasil['terkirim'] ?? 0) > 0 ? 'Pesan terkirim ke '.$hasil['terkirim'].' pasien.' : null,
            ($hasil['gagal'] ?? 0) > 0 ? 'Pesan gagal terkirim untuk '.$hasil['gagal'].' pasien: '.($logs->first(fn ($l) => $l->status === 'gagal')?->error ?? 'kendala di sisi WhatsApp.').' ' : '',
            $tambahan,
        ]));

        return redirect()
            ->route($tujuan)
            ->with(($hasil['terkirim'] ?? 0) > 0 ? 'success' : 'error', $pesan ?: 'Tidak ada pesan yang perlu dikirim.');
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

        return view('admin.outreach.edit', [
            'kirimGroup' => $group,
            'pnpps' => $pnpps,
            'satkers' => Satker::orderBy('nama')->get(['id', 'nama']),
            'filters' => ['q' => (string) $request->query('q', ''), 'satker' => (string) $request->query('satker', '')],
            'canKirimIds' => $canKirimIds,
            'templates' => $this->templateOptions(),
            'selectedIds' => $targetIds,
            'templateId' => $templateId,
            'varsAwal' => $varsAwal,
            'reminders' => $this->pilihanReminder($pnpps, $refReminderIds),
            'reminderAwal' => $reminderAwal,
            'pnppData' => $this->dataPnpp($pnpps),
            'poliOptions' => $this->poliOptions(),
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
            $atribut = app(PesanFactory::class)->atribut($template, $log->pnpp, $reminder, $log->jenis, 'manual', $user, $varsKustom);

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
            $atribut = app(PesanFactory::class)->atribut($template, $pnpp, $reminder, 'outreach', 'manual', $user, $varsKustom);

            MessageLog::create([
                ...$atribut,
                'kirim_group' => $group,
                'kirim_pada' => $kirimPada,
                'konten' => TextSanitizer::win1252((string) $atribut['konten']),
                'template_params' => $this->bersihkanParams($atribut['template_params'] ?? []),
            ]);
        }

        return redirect()
            ->route('admin.outreach.index')
            ->with('success', $this->pesanUbah($ditahan, $ditambah, $dihapus));
    }

    /**
     * Daftar calon penerima PNPP sesuai filter (q/satker), target yang
     * sudah dipilih selalu ikut tampil, plus deretan id yang nomor
     * WhatsApp-nya valid untuk "Pilih semua".
     *
     * @param  array<int, int>  $wajibTampil
     * @return array{0: Collection<int, Pnpp>, 1: array<int, int>}
     */
    protected function kumpulanTarget(Request $request, array $wajibTampil): array
    {
        $q = trim((string) $request->query('q', ''));
        $satkerId = (string) $request->query('satker', '');
        $butuhSaring = $wajibTampil !== [] || $q !== '' || $satkerId !== '';

        $pnpps = Pnpp::query()
            ->with('satker:id,nama', 'latestKunjungan.poli:id,nama')
            ->when($butuhSaring, function ($query) use ($wajibTampil, $q, $satkerId) {
                $query->where(function ($sub) use ($wajibTampil, $q, $satkerId) {
                    $dibuka = false;

                    if ($wajibTampil !== []) {
                        $sub->whereIn('id', $wajibTampil);
                        $dibuka = true;
                    }

                    if ($q !== '') {
                        $dibuka
                            ? $sub->orWhere(fn ($cocok) => $cocok->where('nama', 'like', "%{$q}%")
                                ->orWhere('nip', 'like', "%{$q}%")
                                ->orWhere('no_hp', 'like', "%{$q}%"))
                            : $sub->where(fn ($cocok) => $cocok->where('nama', 'like', "%{$q}%")
                                ->orWhere('nip', 'like', "%{$q}%")
                                ->orWhere('no_hp', 'like', "%{$q}%"));
                        $dibuka = true;
                    }

                    if ($satkerId !== '') {
                        $dibuka
                            ? $sub->orWhere('satker_id', $satkerId)
                            : $sub->where('satker_id', $satkerId);
                        $dibuka = true;
                    }

                    if (! $dibuka) {
                        $sub->whereRaw('1 = 1');
                    }
                });
            })
            ->orderBy('nama')
            ->get(['id', 'nama', 'nip', 'no_hp', 'satker_id'])
            ->unique('id')
            ->values();

        $canKirimIds = $pnpps
            ->filter(fn (Pnpp $p) => PhoneFormat::toWa($p->no_hp) !== null)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return [$pnpps, $canKirimIds];
    }

    /**
     * Opsi template aktif untuk dropdown form — token/parameternya ikut
     * disertakan supaya form bisa menampilkan isian variabel.
     *
     * @return array<int, array{id: int, judul: string, konten: string, token: array<int, string>}>
     */
    protected function templateOptions(): array
    {
        return MessageTemplate::query()
            ->where('is_active', true)
            ->orderBy('judul')
            ->get(['id', 'judul', 'konten'])
            ->map(fn (MessageTemplate $t) => [
                'id' => $t->id,
                'judul' => (string) $t->judul,
                'konten' => (string) $t->konten,
                'token' => $t->tokenParam(),
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
     * Ambil semua pesan "menunggu" satu grup kirim manual; grup yang tidak
     * ada atau sudah terkirim dianggap tidak boleh diubah.
     *
     * @return Collection<int, MessageLog>
     */
    protected function grupMenunggu(string $group)
    {
        $logs = MessageLog::query()
            ->with('pnpp')
            ->where('kirim_group', $group)
            ->jenis('outreach')
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
     * kunjungan terakhir pasien bila tersedia.
     *
     * @param  Collection<int, Pnpp>  $pnpps
     * @return array<int, array{nama: string, nip: string, satker: string, poli: string, instalasi: string, tanggal: string, hari_tanggal: string}>
     */
    protected function dataPnpp(Collection $pnpps): array
    {
        return $pnpps->mapWithKeys(function (Pnpp $p) {
            $kunjungan = $p->latestKunjungan;
            $poli = (string) ($kunjungan?->poli?->nama ?? '');
            $tanggal = $kunjungan?->tanggal_kunjungan;

            return [
                (string) $p->id => [
                    'nama' => (string) ($p->nama ?? '—'),
                    'nip' => (string) ($p->nip ?? ''),
                    'satker' => (string) ($p->satker?->nama ?? ''),
                    'poli' => $poli,
                    'instalasi' => $poli,
                    'tanggal' => (string) ($tanggal?->format('Y-m-d') ?? ''),
                    'hari_tanggal' => (string) ($tanggal?->locale('id')->translatedFormat('l, d F Y') ?? ''),
                ],
            ];
        })->all();
    }

    protected function poliOptions(): array
    {
        return Poli::orderBy('nama')->pluck('nama')->map(fn ($n) => (string) $n)->values()->all();
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

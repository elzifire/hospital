<?php

namespace App\Http\Controllers\Admin;

use App\Broadcasting\PhoneFormat;
use App\Broadcasting\WhatsApp\AntreanKirim;
use App\Broadcasting\WhatsApp\MetaMedia;
use App\Http\Controllers\Controller;
use App\Jobs\ImportMasterJob;
use App\Jobs\PreviewImportJob;
use App\Models\MessageLog;
use App\Models\MessageReply;
use App\Models\MessageTemplate;
use App\Models\Pnpp;
use App\Models\ResponManual;
use App\Models\Satker;
use App\Support\MasterRegistry;
use App\Support\TextSanitizer;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\Mime\MimeTypes;

class ResponController extends Controller
{
    /**
     * Daftar percakapan WhatsApp (mirip chat list WhatsApp).
     */
    public function index(Request $request)
    {
        [$konversasi, $stats, $filters] = $this->daftarKonversasi($request);

        return view('admin.respon.balasan', [
            'konversasi' => $konversasi,
            'total' => $stats['total'],
            'belumDibaca' => $stats['belumDibaca'],
            'hariIni' => $stats['hariIni'],
            'pasienUnik' => $stats['pasienUnik'],
            'takTerdaftar' => $stats['takTerdaftar'],
            'poliId' => $stats['poliId'],
            'filters' => $filters,
            'queryString' => http_build_query(array_filter($filters, fn ($v) => $v !== '')),
            'signature' => $this->signaturePercakapan($stats, $konversasi),
            'hasMore' => $konversasi->hasMorePages(),
            'templates' => MessageTemplate::query()
                ->where('is_active', true)
                ->orderBy('judul')
                ->get(['id', 'judul']),
        ]);
    }

    /**
     * Polling JS (tanpa websocket): kembalikan signature + statistik +
     * HTML daftar percakapan. Frontend mengganti isi list bila berubah.
     * `hasMore` ikut dikirim agar state infinite scroll direset bila
     * daftar disegarkan.
     */
    public function poll(Request $request)
    {
        [$konversasi, $stats, $filters] = $this->daftarKonversasi($request);

        return response()->json([
            'signature' => $this->signaturePercakapan($stats, $konversasi),
            'stats' => $stats,
            'html' => view('admin.respon._chatlist', [
                'konversasi' => $konversasi,
            ])->render(),
            'hasMore' => $konversasi->hasMorePages(),
        ]);
    }

    /**
     * Endpoint fragment daftar percakapan untuk infinite scroll: baris
     * halaman berikutnya (`_chatrows`) + penanda masih ada data.
     */
    public function konten(Request $request)
    {
        [$konversasi] = $this->daftarKonversasi($request);

        return response()->json([
            'html' => view('admin.respon._chatrows', ['konversasi' => $konversasi])->render(),
            'hasMore' => $konversasi->hasMorePages(),
            'halaman' => $konversasi->currentPage(),
        ]);
    }

    /**
     * Data chat list + statistik, dipakai bersama oleh halaman index
     * dan endpoint polling.
     *
     * @return array{0: LengthAwarePaginator, 1: array<string, int|null>, 2: array<string, string>}
     */
    protected function daftarKonversasi(Request $request, int $perPage = 15): array
    {
        $q = (string) $request->query('q', '');
        $qAtas = strtoupper($q);
        // Filter urut: belum dibaca dulu (badge merah), lalu pesan terbaru.
        $statusBaca = (string) $request->query('status_baca', '');
        // Filter asal kontak: terdaftar / tak terdaftar di tabel PNPP.
        $asal = (string) $request->query('asal', '');
        // Filter rentang tanggal masuk balasan (dari/sampai).
        $dari = (string) $request->query('dari', '');
        $sampai = (string) $request->query('sampai', '');
        // Filter percakapan yang pernah menerima pesan dari template Meta.
        $templateId = (string) $request->query('template', '');
        $poliId = $request->user()?->poliId();

        $tanggal = function (string $nilai): ?Carbon {
            try {
                return Carbon::parse($nilai);
            } catch (\Throwable) {
                return null;
            }
        };
        $dariAtas = ($dari !== '' ? $tanggal($dari) : null)?->startOfDay();
        $sampaiAtas = ($sampai !== '' ? $tanggal($sampai) : null)?->endOfDay();

        $scopePoli = fn ($query) => $query->when(
            $poliId !== null,
            fn ($sub) => $sub->whereHas(
                'pnpp',
                fn ($pnpp) => $pnpp->whereHas('kunjungans', fn ($kunjungan) => $kunjungan->where('poli_id', $poliId))
            ),
        );

        $konversasi = MessageReply::query()
            ->tap($scopePoli)
            ->when($q, fn ($query) => $query->where(
                fn ($sub) => $sub->whereRaw('UPPER(nama) LIKE ?', ["%{$qAtas}%"])
                    ->orWhere('no_hp', 'like', "%{$q}%")
                    ->orWhere('isi_pesan', 'like', "%{$q}%")
            ))
            ->when($dariAtas !== null, fn ($query) => $query->where('waktu_masuk', '>=', $dariAtas))
            ->when($sampaiAtas !== null, fn ($query) => $query->where('waktu_masuk', '<=', $sampaiAtas))
            ->when($templateId !== '', function ($query) use ($templateId) {
                $query->whereIn('no_hp', MessageLog::query()
                    ->where('message_template_id', $templateId)
                    ->whereNotNull('message_template_id')
                    ->select('penerima_no_hp'));
            })
            ->select('no_hp')
            ->selectRaw('MAX("waktu_masuk") as waktu_terakhir')
            ->selectRaw('COUNT(*) as total_pesan')
            ->selectRaw('COALESCE(SUM(CASE WHEN "read_at" IS NULL THEN 1 ELSE 0 END), 0) as belum_dibaca')
            ->groupBy('no_hp')
            ->when($statusBaca === 'belum', fn ($query) => $query->havingRaw(
                'COALESCE(SUM(CASE WHEN "read_at" IS NULL THEN 1 ELSE 0 END), 0) > 0'
            ))
            ->when($statusBaca === 'dibaca', fn ($query) => $query->havingRaw(
                'COALESCE(SUM(CASE WHEN "read_at" IS NULL THEN 1 ELSE 0 END), 0) = 0'
            ))
            ->when($asal === 'terdaftar', fn ($query) => $query->havingRaw(
                'COALESCE(MAX("pnpp_id"), 0) > 0'
            ))
            ->when($asal === 'tak_terdaftar', fn ($query) => $query->havingRaw(
                'COALESCE(MAX("pnpp_id"), 0) = 0'
            ))
            ->orderByDesc('belum_dibaca')
            ->orderByDesc('waktu_terakhir')
            ->paginate($perPage)
            ->withQueryString();

        $nomors = $konversasi->pluck('no_hp')->all();
        $pesanTerakhir = $nomors === []
            ? collect()
            : MessageReply::query()
                ->whereIn('no_hp', $nomors)
                ->orderBy('waktu_masuk')
                ->get()
                ->groupBy('no_hp')
                ->map->last();

        $pnppByNomor = Pnpp::query()
            ->whereNotNull('no_hp')
            ->get(['id', 'nama', 'no_hp'])
            ->reduce(function (array $carry, Pnpp $p) {
                $wa = PhoneFormat::toWa($p->no_hp);
                $wa !== null && ($carry[$wa] = $p);

                return $carry;
            }, []);

        $konversasi->getCollection()->transform(function ($row) use ($pesanTerakhir, $pnppByNomor) {
            $terakhir = $pesanTerakhir[$row->no_hp] ?? null;
            $row->waktu_terakhir = $terakhir?->waktu_masuk;
            $row->isi_terakhir = $terakhir?->isi_pesan;
            $row->nama_pengirim = $terakhir?->nama;
            $row->pnpp = $row->pnpp ?? ($pnppByNomor[$row->no_hp] ?? null);

            return $row;
        });

        $stats = [
            'total' => MessageReply::query()->tap($scopePoli)->count(),
            'belumDibaca' => MessageReply::query()->tap($scopePoli)->belumDibaca()->count(),
            'hariIni' => MessageReply::query()->tap($scopePoli)->whereBetween('waktu_masuk', [now()->startOfDay(), now()])->count(),
            'pasienUnik' => MessageReply::query()->tap($scopePoli)->whereNotNull('pnpp_id')->distinct()->count('pnpp_id'),
            'takTerdaftar' => MessageReply::query()->tap($scopePoli)->whereNull('pnpp_id')->count(),
            'poliId' => $poliId,
        ];

        return [$konversasi, $stats, [
            'q' => $q,
            'status_baca' => $statusBaca,
            'asal' => $asal,
            'dari' => $dari,
            'sampai' => $sampai,
            'template' => $templateId,
        ]];
    }

    /**
     * Tanda tangan perubahan chat list: kombinasi jumlah + pesan terakhir.
     * Berubah hanya bila ada data baru → frontend memicu refresh.
     *
     * @param  array<string, int|null>  $stats
     */
    protected function signaturePercakapan(array $stats, $konversasi): string
    {
        $pertama = $konversasi->getCollection()->first();
        $waktu = $pertama?->waktu_terakhir ? optional($pertama->waktu_terakhir)->toIso8601String() : null;

        return implode(':', [
            $stats['total'],
            $stats['belumDibaca'],
            $stats['hariIni'],
            $stats['pasienUnik'],
            $stats['takTerdaftar'],
            $waktu ?? '0',
        ]);
    }

    /**
     * Index data respon manual & import (tabel respon_manuals).
     */
    public function indexData(Request $request)
    {
        $q = (string) $request->query('q', '');
        $qAtas = strtoupper($q);

        $dataRespon = ResponManual::query()
            ->when($q, fn ($query) => $query->where(
                fn ($sub) => $sub->whereRaw('UPPER(nama) LIKE ?', ["%{$qAtas}%"])
                    ->orWhere('nrp_nip', 'like', "%{$q}%")
                    ->orWhere('no_hp', 'like', "%{$q}%")
                    ->orWhere('satker', 'like', "%{$q}%")
                    ->orWhere('isi', 'like', "%{$q}%")
            ))
            ->orderByDesc('waktu')
            ->paginate(15)
            ->withQueryString();

        return view('admin.respon.data', [
            'dataRespon' => $dataRespon,
            'totalRespon' => ResponManual::count(),
            'totalManual' => ResponManual::where('sumber', ResponManual::SUMBER_MANUAL)->count(),
            'totalImport' => ResponManual::where('sumber', ResponManual::SUMBER_IMPORT)->count(),
            'filters' => ['q' => $q],
        ]);
    }

    /**
     * Form input manual.
     */
    public function indexManual()
    {
        return view('admin.respon.manual');
    }

    /**
     * Form kirim pesan manual ke target kontak PNPP (bukan template Meta —
     * teks bebas, hemat biaya). Saring pasien via kata kunci / satker lalu
     * centang satu atau beberapa kontak sebagai penerima.
     */
    public function pesanManual(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $qAtas = strtoupper($q);
        $satkerId = (string) $request->query('satker', '');

        $pnpps = Pnpp::query()
            ->with('satker:id,nama')
            ->when($q !== '', function ($query) use ($q, $qAtas) {
                $query->where(
                    fn ($sub) => $sub->whereRaw('UPPER(nama) LIKE ?', ["%{$qAtas}%"])
                        ->orWhere('nip', 'like', "%{$q}%")
                        ->orWhere('no_hp', 'like', "%{$q}%")
                );
            })
            ->when($satkerId !== '', fn ($query) => $query->where('satker_id', $satkerId))
            ->orderBy('nama')
            ->get(['id', 'nama', 'nip', 'no_hp', 'satker_id']);

        $canKirimIds = $pnpps
            ->filter(fn (Pnpp $p) => PhoneFormat::toWa($p->no_hp) !== null)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return view('admin.respon.pesan-manual', [
            'pnpps' => $pnpps,
            'satkers' => Satker::orderBy('nama')->get(['id', 'nama']),
            'filters' => ['q' => $q, 'satker' => $satkerId],
            'canKirimIds' => $canKirimIds,
            'templates' => $this->templateReferensi(),
        ]);
    }

    /**
     * Kirim pesan manual (POST) ke kontak PNPP terpilih — teks bebas
     * (bukan template Meta), dikirim sinkron lewat WhatsApp Business.
     */
    public function kirimPesanManual(Request $request)
    {
        $data = $request->validate([
            'pnpp_ids' => ['required', 'array', 'min:1'],
            'pnpp_ids.*' => ['integer', Rule::exists('pnpps', 'id')],
            'isi' => ['required', 'string', 'max:5000'],
        ]);

        $isi = TextSanitizer::win1252((string) $data['isi']);
        $kirimGroup = (string) Str::uuid();

        $pnpps = Pnpp::query()->whereIn('id', $data['pnpp_ids'])->get();

        $logs = $pnpps->map(function (Pnpp $pnpp) use ($request, $isi, $kirimGroup) {
            $noHp = PhoneFormat::toWa($pnpp->no_hp);

            // Nomor WhatsApp tidak valid → pesan langsung ditandai gagal,
            // tidak masuk antrean pengiriman.
            $valid = $noHp !== null;

            return MessageLog::create([
                'jenis' => 'respon',
                'rule' => 'pesan_manual',
                'pnpp_id' => $pnpp->id,
                'created_by' => $request->user()?->id,
                'kirim_group' => $kirimGroup,
                'penerima_nama' => (string) $pnpp->nama,
                'penerima_no_hp' => $noHp ?? (string) ($pnpp->no_hp ?? ''),
                'konten' => $isi,
                'status' => $valid ? 'menunggu' : 'gagal',
                'error' => $valid ? null : 'Nomor WhatsApp tidak valid.',
                'provider' => (string) config('whatsapp.driver'),
                // Tanpa meta template → teks bebas, hemat biaya & aman dari
                // galat parameter template.
                'meta_template_name' => null,
                'template_params' => [],
            ]);
        });

        $menunggu = $logs->where('status', 'menunggu');
        $tanpaNomor = $logs->where('status', 'gagal');

        $hasil = $menunggu->isEmpty()
            ? ['terkirim' => 0, 'gagal' => 0, 'dilewati' => 0]
            : app(AntreanKirim::class)->kirimSinkron($menunggu->all());

        $pesan = implode(' ', array_filter([
            ($hasil['terkirim'] ?? 0) > 0 ? 'Pesan terkirim ke '.$hasil['terkirim'].' kontak.' : null,
            ($hasil['gagal'] ?? 0) > 0 ? 'Pesan gagal terkirim untuk '.$hasil['gagal'].' kontak.' : null,
            $tanpaNomor->isNotEmpty() ? $tanpaNomor->count().' kontak dilewati karena nomor WhatsApp tidak valid.' : null,
        ]));

        return redirect()
            ->route('admin.respon.pesan-manual')
            ->with(filled($pesan) && ($hasil['terkirim'] ?? 0) > 0 ? 'success' : 'error', $pesan ?: 'Tidak ada pesan yang terkirim.');
    }

    /**
     * Daftar template aktif sebagai referensi isi pesan — ditampilkan di
     * halaman kirim manual agar user tinggal menyalin (pengiriman tetap
     * teks bebas, bukan tipe template Meta sehingga lebih hemat).
     */
    protected function templateReferensi(): array
    {
        return MessageTemplate::query()
            ->with('category:id,nama')
            ->where('is_active', true)
            ->orderBy('judul')
            ->get(['id', 'judul', 'konten', 'template_category_id'])
            ->map(fn (MessageTemplate $t) => [
                'id' => $t->id,
                'judul' => (string) $t->judul,
                'konten' => (string) $t->konten,
                'kategori' => (string) ($t->category?->nama ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * Import Excel/CSV.
     */
    public function indexImport(Request $request)
    {
        [$status, $preview, $token] = $this->importState();

        return view('admin.respon.import', [
            'importStatus' => $status,
            'preview' => $preview,
            'importToken' => $preview ? $token : null,
        ]);
    }

    /**
     * Percakapan satu nomor: pesan keluar (broadcast/balasan) + balasan
     * masuk, digabung dalam satu garis waktu.
     */
    public function show(Request $request, string $nomor)
    {
        $noHp = PhoneFormat::toWa($nomor) ?? $nomor;
        $this->pastikanAksesNomor($request, $noHp);

        // Membuka percakapan = membaca balasan masuk (badge merah hilang).
        MessageReply::tandaiDibaca($noHp);

        $pnpp = $this->pnppUntukNomor($noHp);
        $konteksAkhir = (string) MessageReply::query()
            ->where('no_hp', $noHp)
            ->orderByDesc('waktu_masuk')
            ->value('isi_pesan');

        return view('admin.respon.show', [
            'noHp' => $noHp,
            'pnpp' => $pnpp,
            'timeline' => $this->timelineData($noHp),
            'replis' => $this->replisKontekstual($konteksAkhir),
            'konteksAkhir' => $konteksAkhir,
        ]);
    }

    /**
     * Balasan kontekstual: saran balas cepat yang disesuaikan dengan
     * pesan terakhir pasien. Item yang kata kuncinya cocok dengan
     * konteks ditandai `kontekstual` agar ditampil lebih dulu; sisanya
     * tetap tersedia sebagai balasan cepat.
     *
     * @return array<int, array{pemicu: array<int, string>, label: string, teks: string, kontekstual: bool}>
     */
    protected function replisKontekstual(string $konteks): array
    {
        $daftar = [
            [
                'pemicu' => ['jadwal', 'kontrol', 'perjanjian', 'pemeriksaan'],
                'label' => 'Jadwal kontrol',
                'teks' => 'Baik, jadwal kontrol Bapak/Ibu akan kami siapkan. Silakan datang sesuai jam pada pesan jadwal sebelumnya dan bawa kartu berobat.',
            ],
            [
                'pemicu' => ['poli', 'dokter', 'klinik'],
                'label' => 'Jadwal poli / dokter',
                'teks' => 'Jadwal poli dan dokter sudah tertera pada pesan yang kami kirimkan. Bila masih ragu, kami bantu konfirmasikan ke bagian pendaftaran.',
            ],
            [
                'pemicu' => ['sakit', 'keluhan', 'demam', 'nyeri', 'pusing'],
                'label' => 'Keluhan kesehatan',
                'teks' => 'Mohon dijaga kesehatannya. Bila keluhan berlanjut atau memberat, segera datang ke instalasi gawat darurat atau poli terdekat.',
            ],
            [
                'pemicu' => ['obat', 'resep'],
                'label' => 'Info obat / resep',
                'teks' => 'Untuk informasi obat atau resep, mohon menanyakan langsung ke apotek atau petugas poli agar petunjuk pemakaiannya sesuai kondisi Bapak/Ibu.',
            ],
            [
                'pemicu' => ['hadir', 'datang', 'siap', 'insya'],
                'label' => 'Konfirmasi kehadiran',
                'teks' => 'Terima kasih konfirmasinya. Mohon datang 30 menit sebelum jadwal untuk proses administrasi.',
            ],
            [
                'pemicu' => ['daftar', 'pendaftaran', 'antrian', 'registrasi'],
                'label' => 'Cara pendaftaran',
                'teks' => 'https://docs.google.com/forms/d/e/1FAIpQLSciyFxqTOXei_xLrHc2aE3jnlx6V8eaNHlQHulsi_UCeV6Zng/viewform?fbzx=9126598892601474435',
            ],
        ];

        $kecil = mb_strtolower($konteks);

        foreach ($daftar as &$item) {
            $item['kontekstual'] = $kecil !== '' && Str::contains($kecil, $item['pemicu']);
        }

        return $daftar;
    }

    /**
     * Kirim balasan langsung ke pasien: teks bebas, media (image/audio/
     * video/document), atau pesan interactive CTA-URL. Dipanggil
     * frontend lewat fetch (JSON event) — tanpa websocket; hasil
     * pengiriman sinkron.
     */
    public function balas(Request $request, string $nomor)
    {
        $noHp = PhoneFormat::toWa($nomor) ?? $nomor;
        $this->pastikanAksesNomor($request, $noHp);

        $tipe = (string) $request->input('tipe', 'text');

        abort_unless(in_array($tipe, ['text', 'image', 'audio', 'video', 'document', 'interactive'], true), 422);
        $validated = $request->validate($this->aturanBalasan($tipe));

        $pnpp = $this->pnppUntukNomor($noHp);

        [$konten, $metaPayload] = $this->siapkanBalasan($request, $tipe, $validated);

        $log = MessageLog::create([
            'jenis' => 'respon',
            'rule' => 'balasan',
            'pnpp_id' => $pnpp?->id,
            'created_by' => $request->user()?->id,
            'penerima_nama' => (string) ($pnpp?->nama ?? 'Nomor Tak Dikenal'),
            'penerima_no_hp' => $noHp,
            'konten' => $konten,
            'status' => 'menunggu',
            'provider' => (string) config('whatsapp.driver'),
            // Tanpa meta_template_name → MetaSender mengirim teks bebas
            // (bukan template), aman dari galat parameter template.
            'meta_template_name' => null,
            'template_params' => [],
            'meta_payload' => $metaPayload,
        ]);

        $hasil = app(AntreanKirim::class)->kirimSinkron([$log]);
        $ok = ($hasil['terkirim'] ?? 0) > 0;
        $pesan = $ok
            ? 'Balasan terkirim ke '.$log->penerima_nama.'.'
            : 'Balasan gagal terkirim: '.($log->refresh()->error ?? 'tidak diketahui.');

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => $ok,
                'message' => $pesan,
                'log' => [
                    'id' => $log->id,
                    'arah' => 'keluar',
                    'isi' => $log->konten,
                    'tipe' => $tipe,
                    'status' => $log->status,
                    'waktu' => ($log->sent_at ?? $log->created_at)?->toIso8601String(),
                ],
            ]);
        }

        return back()->with($ok ? 'success' : 'error', $pesan);
    }

    /**
     * Aturan validasi per tipe balasan.
     *
     * @return array<string, mixed>
     */
    protected function aturanBalasan(string $tipe): array
    {
        $file = ['required', 'file', 'max:10240'];

        return match ($tipe) {
            'text' => ['isi' => ['required', 'string', 'max:5000']],
            'interactive' => [
                'isi' => ['required', 'string', 'max:1024'],
                'cta_url' => ['required', 'url', 'max:2048'],
                'cta_label' => ['required', 'string', 'max:40'],
                'cta_header' => ['nullable', 'string', 'max:60'],
                'cta_footer' => ['nullable', 'string', 'max:60'],
            ],
            'image' => [
                'media' => [...$file, 'mimes:jpeg,jpg,png,webp,gif', $this->cekMimeIsi('image')],
                'caption' => ['nullable', 'string', 'max:1000'],
            ],
            'audio' => [
                'media' => [...$file, 'mimes:mp3,m4a,aac,ogg,amr,wav', $this->cekMimeIsi('audio')],
                'caption' => ['nullable', 'string', 'max:1000'],
            ],
            'video' => [
                'media' => [...$file, 'mimes:mp4,mov,3gp', $this->cekMimeIsi('video')],
                'caption' => ['nullable', 'string', 'max:1000'],
            ],
            'document' => [
                'media' => [...$file, 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt', $this->cekMimeIsi('document')],
                'caption' => ['nullable', 'string', 'max:1000'],
            ],
            default => [],
        };
    }

    /**
     * Siapkan konten + meta_payload sesuai tipe balasan. Media disimpan
     * ke storage publik agar bisa di-preview di timeline; saat kirim,
     * MetaSender mengunggahnya ke WABA dan memakai id hasil upload.
     *
     * @param  array<string, mixed>  $validated
     * @return array{0: string, 1: array<string, mixed>|null}
     */
    protected function siapkanBalasan(Request $request, string $tipe, array $validated): array
    {
        if ($tipe === 'text') {
            return [TextSanitizer::win1252((string) $validated['isi']), null];
        }

        if ($tipe === 'interactive') {
            $sanitasi = fn ($nilai) => TextSanitizer::win1252(trim((string) ($validated[$nilai] ?? '')));

            return [$sanitasi('isi'), [
                'kind' => 'interactive',
                'tipe' => 'cta_url',
                'body' => $sanitasi('isi'),
                'url' => trim((string) $validated['cta_url']),
                'label' => $sanitasi('cta_label'),
                'header' => $sanitasi('cta_header'),
                'footer' => $sanitasi('cta_footer'),
            ]];
        }

        $file = $request->file('media');

        if (! $file->isValid()) {
            abort(422, 'Media yang diunggah tidak valid.');
        }

        // Nama tersimpan memakai ekstensi hasil deteksi server (sesuai
        // MIME isi berkas), bukan ekstensi nama asli yang dikirim klien,
        // agar berkas tidak bisa disimpan dengan ekstensi mencurigakan.
        $nama = (string) Str::random(24).'.'.$this->ekstensiTerverifikasi($file, $tipe);
        $path = $file->storeAs('respon-media', $nama, 'public');

        if ($path === false) {
            abort(422, 'Media gagal disimpan.');
        }

        $caption = TextSanitizer::win1252(trim((string) ($validated['caption'] ?? '')));

        return [
            $caption !== '' ? $caption : '['.$tipe.']',
            [
                'kind' => 'media',
                'tipe' => $tipe,
                'nama' => $this->santasiNamaFile((string) $file->getClientOriginalName()),
                'mime' => $this->mimeKonten($file),
                'path' => (string) $path,
                'caption' => $caption,
            ],
        ];
    }

    /**
     * MIME isi berkas hasil finfo (server) — bukan klaim klien. Dipakai
     * untuk validasi isi dan penetapan ekstensi penyimpanan yang benar.
     */
    protected function mimeKonten(UploadedFile $file): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file->getPathname());

        return $mime ? strtolower((string) $mime) : 'application/octet-stream';
    }

    /**
     * Daftar MIME isi berkas (finfo) yang wajar untuk tiap tipe balasan.
     */
    protected function daftarMimeIsi(string $tipe): array
    {
        return match ($tipe) {
            'image' => ['image/jpeg', 'image/pjpeg', 'image/png', 'image/webp', 'image/gif'],
            'audio' => [
                'audio/mpeg', 'audio/mp3', 'audio/mp4', 'audio/x-m4a', 'audio/aac',
                'audio/x-hx-aac-adts', 'audio/ogg', 'application/ogg', 'audio/amr',
                'audio/wav', 'audio/x-wav',
            ],
            'video' => ['video/mp4', 'video/quicktime', 'video/3gpp', 'video/3gpp2'],
            'document' => [
                'application/pdf', 'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain', 'text/csv',
            ],
            default => [],
        };
    }

    /**
     * Rule validasi isi berkas: MIME terdeteksi (finfo) wajib masuk daftar
     * wajar untuk tipe terpilih — menolak berkas "tipuan" (nama/ekstensi
     * menyamar tapi isi berbeda) yang lolos validasi berbasis ekstensi.
     */
    protected function cekMimeIsi(string $tipe): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($tipe): void {
            if (! $value instanceof UploadedFile || ! $value->isValid()) {
                $fail('Berkas yang diunggah tidak valid.');

                return;
            }

            if (! in_array($this->mimeKonten($value), $this->daftarMimeIsi($tipe), true)) {
                $fail('Isi berkas tidak sesuai dengan jenis yang dipilih ('.ucfirst($tipe).').');
            }
        };
    }

    /**
     * Ekstensi kanonik untuk berkas media yang disimpan, diturunkan dari
     * MIME isi berkas hasil deteksi server (finfo) — bukan dari ekstensi
     * nama asli klien, agar berkas tidak bisa disimpan dengan ekstensi
     * mencurigakan atau menyesatkan.
     */
    protected function ekstensiTerverifikasi(UploadedFile $file, string $tipe): string
    {
        $kanonik = [
            'image' => [
                'image/jpeg' => 'jpg',
                'image/pjpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
            ],
            'audio' => [
                'audio/mpeg' => 'mp3',
                'audio/mp3' => 'mp3',
                'audio/mp4' => 'm4a',
                'audio/x-m4a' => 'm4a',
                'audio/aac' => 'aac',
                'audio/ogg' => 'ogg',
                'application/ogg' => 'ogg',
                'audio/amr' => 'amr',
                'audio/wav' => 'wav',
                'audio/x-wav' => 'wav',
            ],
            'video' => [
                'video/mp4' => 'mp4',
                'video/quicktime' => 'mov',
                'video/3gpp' => '3gp',
            ],
            'document' => [
                'application/pdf' => 'pdf',
                'application/msword' => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                'application/vnd.ms-excel' => 'xls',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                'application/vnd.ms-powerpoint' => 'ppt',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
                'text/plain' => 'txt',
                'text/csv' => 'csv',
            ],
        ];

        $mime = $this->mimeKonten($file);

        return $kanonik[$tipe][$mime]
            ?? MimeTypes::getDefault()->getExtensions($mime)[0]
            ?? 'bin';
    }

    /**
     * Ekstensi tersimpan untuk import, dipetakan dari MIME hasil deteksi
     * server terhadap isi berkas. CSV sengaja dipetakan ke 'csv' walau
     * finfo acap melaporkan text/plain / vnd.ms-excel, supaya SheetHelper
     * selalu memperlakukannya sebagai CSV.
     */
    protected function ekstensiImport(UploadedFile $file): string
    {
        $mime = $this->mimeKonten($file);

        $peta = [
            'text/csv' => 'csv',
            'application/csv' => 'csv',
            'text/comma-separated-values' => 'csv',
            'text/plain' => 'csv',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-excel' => 'xls',
        ];

        if (isset($peta[$mime])) {
            return $peta[$mime];
        }

        $tebak = strtolower((string) ($file->guessExtension() ?: ''));

        return in_array($tebak, ['xlsx', 'xls', 'csv', 'txt'], true) ? $tebak : 'xlsx';
    }

    /**
     * Nama berkas aman untuk display/unduhan & header multipart: buang
     * jalur (path traversal), karakter kontrol, lalu batasi panjang.
     */
    protected function santasiNamaFile(string $nama): string
    {
        $nama = str_replace('\\', '/', $nama);
        $nama = basename($nama);
        $nama = (string) preg_replace('/[\x00-\x1F\x7F]/u', '', $nama);
        $nama = ltrim($nama, '. ');
        $nama = mb_substr($nama, 0, 120, 'UTF-8');

        return $nama !== '' ? $nama : 'lampiran';
    }

    /**
     * Timeline percakapan versi JSON untuk polling JS (event, tanpa
     * websocket). Signature berubah bila ada pesan baru → frontend
     * mengganti isi percakapan & memicu event 'respon:baru'.
     */
    public function timeline(Request $request, string $nomor)
    {
        $noHp = PhoneFormat::toWa($nomor) ?? $nomor;
        $this->pastikanAksesNomor($request, $noHp);

        // Percakapan sedang dibuka → balasan baru ikut ditandai dibaca
        // agar badge merah di chat list tetap akurat.
        MessageReply::tandaiDibaca($noHp);

        $timeline = $this->timelineData($noHp);

        return response()->json([
            'signature' => $timeline->count().':'.($timeline->last()['waktu']?->toIso8601String() ?? '0'),
            'html' => view('admin.respon._timeline', ['timeline' => $timeline])->render(),
        ]);
    }

    /**
     * Sajikan media yang dikirim pasien (balasan masuk). Berkas diambil
     * dari Meta WhatsApp API pada akses pertama lalu di-cache ke storage
     * publik, sehingga akses berikutnya langsung dari disk. Query `unduh=1`
     * memaksa Content-Disposition attachment.
     */
    public function media(Request $request, string $nomor, MessageReply $balasan)
    {
        $noHp = PhoneFormat::toWa($nomor) ?? $nomor;
        $this->pastikanAksesNomor($request, $noHp);

        // Balasan harus milik percakapan yang sedang dibuka.
        abort_unless($balasan->no_hp === $noHp, 404);

        $relatif = $this->ambilAtauCacheMedia($balasan);

        if ($relatif === null) {
            abort(404, 'Media tidak dapat diambil dari WhatsApp.');
        }

        $unduh = $request->boolean('unduh');

        return Storage::disk('public')->response(
            $relatif,
            $this->namaMedia($balasan, $relatif),
            ['Cache-Control' => 'private, max-age=86400'],
            $unduh ? 'attachment' : 'inline',
        );
    }

    /**
     * Lokasi media masuk di storage publik: pakai cache bila sudah ada,
     * selain itu unduh dari Meta lalu simpan dan catat path-nya di payload.
     * Null bila media belum tersedia (mis. driver non-meta / media basi).
     */
    protected function ambilAtauCacheMedia(MessageReply $balasan): ?string
    {
        $payload = (array) $balasan->payload;
        $relatif = (string) ($payload['media_lokal'] ?? '');

        if ($relatif !== '' && Storage::disk('public')->exists($relatif)) {
            return $relatif;
        }

        $pesan = (array) ($payload['pesan'] ?? []);
        $mediaId = (string) ($pesan['id'] ?? '');

        if ($mediaId === '') {
            return null;
        }

        $berkas = app(MetaMedia::class)->ambilMedia($mediaId);

        if ($berkas === null) {
            return null;
        }

        [$bytes, $mime] = $berkas;
        $ekstensi = MimeTypes::getDefault()->getExtensions($mime)[0] ?? 'bin';
        $relatif = 'respon-media/masuk/'.sha1($mediaId).'.'.$ekstensi;

        Storage::disk('public')->put($relatif, $bytes);

        $balasan->update([
            'payload' => array_replace_recursive($payload, [
                'media_lokal' => $relatif,
                'media_mime' => $mime,
            ]),
        ]);

        return $relatif;
    }

    /**
     * Nama file untuk unduhan: pakai nama asli dari payload bila ada,
     * selain itu turunkan dari nama berkas tersimpan.
     */
    protected function namaMedia(MessageReply $balasan, string $relatif): string
    {
        $pesan = (array) ($balasan->payload['pesan'] ?? []);
        $tipe = (string) ($pesan['type'] ?? 'media');
        $objek = (array) ($pesan[$tipe] ?? []);
        $nama = trim((string) ($objek['filename'] ?? ''));

        if ($nama === '' && $tipe === 'document') {
            $nama = trim((string) ($objek['caption'] ?? ''));
        }

        return $nama !== '' ? $this->santasiNamaFile($nama) : 'lampiran-'.basename($relatif);
    }

    /**
     * @return Collection<int, array{arah: string, isi: string, waktu: Carbon, status?: string, jenis?: string, nama?: string, meta_payload?: array<string, mixed>|null, media_kind?: string|null, media_in?: array<string, mixed>|null}>
     */
    protected function timelineData(string $noHp): Collection
    {
        $keluar = MessageLog::query()
            ->where('penerima_no_hp', $noHp)
            ->orderBy('created_at')
            ->get()
            ->map(fn (MessageLog $log) => [
                'arah' => 'keluar',
                'isi' => $log->konten,
                'waktu' => $log->sent_at ?? $log->created_at,
                'status' => $log->status,
                'jenis' => $log->jenis,
                'meta_payload' => $log->meta_payload,
            ]);

        $masuk = MessageReply::query()
            ->where('no_hp', $noHp)
            ->orderBy('waktu_masuk')
            ->get()
            ->map(fn (MessageReply $b) => [
                'arah' => 'masuk',
                'isi' => $b->isi_pesan,
                'waktu' => $b->waktu_masuk,
                'nama' => $b->nama,
                'media_kind' => $this->tipeMediaMasuk($b),
                'media_in' => $this->rincianMediaMasuk($b, $noHp),
            ]);

        return $keluar->toBase()
            ->merge($masuk->toBase())
            ->sortBy(fn ($item) => $item['waktu']?->getTimestamp() ?? 0)
            ->values();
    }

    /**
     * Rincian media yang ditampilkan di timeline untuk balasan masuk.
     * Null bila bukan pesan media atau media id tidak tersedia.
     *
     * @return array<string, mixed>|null
     */
    protected function rincianMediaMasuk(MessageReply $balasan, string $noHp): ?array
    {
        $tipe = $this->tipeMediaMasuk($balasan);

        if ($tipe === null) {
            return null;
        }

        $pesan = (array) ($balasan->payload['pesan'] ?? []);
        $id = (string) ($pesan['id'] ?? '');

        if ($id === '') {
            return null;
        }

        $objek = (array) ($pesan[$tipe] ?? []);
        $nama = trim((string) ($objek['filename'] ?? ''));

        if ($nama === '' && $tipe === 'document') {
            $nama = trim((string) ($objek['caption'] ?? ''));
        }

        $url = route('admin.respon.media', ['nomor' => $noHp, 'balasan' => $balasan->id]);

        return [
            'kind' => $tipe,
            'mime' => (string) ($pesan['mime_type'] ?? 'application/octet-stream'),
            'nama' => $nama !== '' ? $nama : 'lampiran-'.$id,
            'url' => $url,
            'unduh' => $url.'?unduh=1',
            'lokal' => filled($balasan->payload['media_lokal'] ?? null),
        ];
    }

    /**
     * Jenis media balasan masuk (image/audio/video/document/sticker) dari
     * payload mentah webhook — dipakai render ikon lampiran di timeline.
     */
    protected function tipeMediaMasuk(MessageReply $balasan): ?string
    {
        $pesan = (array) ($balasan->payload['pesan'] ?? []);
        $tipe = (string) ($pesan['type'] ?? 'text');

        return in_array($tipe, ['image', 'audio', 'video', 'document', 'sticker'], true) ? $tipe : null;
    }

    protected function pnppUntukNomor(string $noHp): ?Pnpp
    {
        return Pnpp::query()
            ->whereNotNull('no_hp')
            ->get()
            ->first(fn (Pnpp $p) => PhoneFormat::toWa($p->no_hp) === $noHp);
    }

    /**
     * Akun poli hanya boleh melihat & membalas nomor pasien polinya.
     */
    protected function pastikanAksesNomor(Request $request, string $noHp): void
    {
        $poliId = $request->user()?->poliId();

        if ($poliId === null) {
            return;
        }

        $punya = MessageReply::query()
            ->where('no_hp', $noHp)
            ->whereHas('pnpp', fn ($pnpp) => $pnpp->whereHas('kunjungans', fn ($kunjungan) => $kunjungan->where('poli_id', $poliId)))
            ->exists();

        abort_unless($punya, 403, 'Nomor ini bukan pasien poli Anda.');
    }

    /**
     * Simpan balasan dari input manual (form di halaman "Input Manual").
     * Formatnya berbeda dari balasan webhook: nama, nrp/nip, no_hp,
     * satker, isi — disimpan ke tabel respon_manuals yang terpisah.
     */
    public function storeManual(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['nullable', 'string', 'max:255'],
            'nrp_nip' => ['nullable', 'string', 'max:64'],
            'no_hp' => ['required', 'string', 'max:32'],
            'satker' => ['nullable', 'string', 'max:255'],
            'isi' => ['required', 'string', 'max:2000'],
            'waktu' => ['nullable', 'date'],
        ]);

        $digits = MasterRegistry::normalizeDigits($validated['no_hp']);
        $noHp = $digits !== null ? PhoneFormat::toWa($digits) : null;

        if ($noHp === null) {
            return back()
                ->withInput()
                ->withErrors(['no_hp' => 'Nomor HP tidak valid.']);
        }

        ResponManual::create([
            'nama' => filled($validated['nama'] ?? null) ? $validated['nama'] : null,
            'nrp_nip' => filled($validated['nrp_nip'] ?? null) ? $validated['nrp_nip'] : null,
            'no_hp' => $noHp,
            'satker' => filled($validated['satker'] ?? null) ? $validated['satker'] : null,
            'isi' => $validated['isi'],
            'waktu' => $validated['waktu'] ?? now(),
            'sumber' => ResponManual::SUMBER_MANUAL,
        ]);

        return redirect()
            ->route('admin.respon.data')
            ->with('success', 'Balasan tersimpan sebagai input manual.');
    }

    /**
     * Hapus satu baris data respon manual/import dari halaman "Data Respon".
     */
    public function destroy(ResponManual $responManual)
    {
        $responManual->delete();

        return redirect()
            ->route('admin.respon.data')
            ->with('success', 'Data respon dihapus.');
    }

    /**
     * Upload file (xlsx/xls/csv) → parsing/pratinjau di background (queue),
     * lalu proses import memakai batch/chunk yang sama dengan data master.
     */
    public function importUpload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:4096'],
        ]);

        $token = (string) Str::uuid();
        $file = $request->file('file');
        $ekstensi = $this->ekstensiImport($file);

        $file->storeAs('imports', "{$token}.{$ekstensi}", 'local');

        Cache::put(ImportMasterJob::cacheKey($token), ['status' => 'preview_pending'], now()->addHours(2));
        PreviewImportJob::dispatch('respon', $token, $ekstensi);

        session(['respon_import_token' => $token]);

        return redirect()
            ->route('admin.respon.import')
            ->with('success', 'Pratinjau sedang diproses di background (queue). Halaman ini akan diperbarui otomatis.');
    }

    /**
     * Konfirmasi import → simpan baris (yang sudah diedit) lalu dispatch
     * satu job per batch ke queue (redis) agar tidak memberatkan proses.
     */
    public function importConfirm(Request $request)
    {
        $token = $request->input('token');
        $status = $token ? Cache::get(ImportMasterJob::cacheKey($token)) : null;
        $total = (int) ($status['total'] ?? 0);
        $totalChunks = (int) ($status['total_chunks'] ?? 0);

        if (! $token || ($status['status'] ?? null) !== 'preview_ready' || $total === 0 || $totalChunks === 0) {
            return redirect()
                ->route('admin.respon.import')
                ->withErrors(['file' => 'Data preview kosong. Silakan upload ulang.']);
        }

        $editedRows = json_decode((string) $request->input('rows'), true);
        if (is_array($editedRows) && $editedRows !== [] && count($editedRows) <= PreviewImportJob::PREVIEW_LIMIT) {
            $total = count($editedRows);
            $totalChunks = (int) ceil($total / ImportMasterJob::BATCH_SIZE);
            foreach (array_chunk($editedRows, ImportMasterJob::BATCH_SIZE) as $chunk => $rows) {
                Storage::disk('local')->put("imports/{$token}.rows.{$chunk}.json", json_encode($rows, JSON_UNESCAPED_UNICODE));
            }
        }

        Cache::put(ImportMasterJob::cacheKey($token), [
            'status' => 'pending',
            'total' => $total,
            'total_chunks' => $totalChunks,
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => [],
        ], now()->addHours(2));

        ImportMasterJob::dispatch('respon', $token, 0, $totalChunks);

        session(['respon_import_token' => $token]);

        return redirect()
            ->route('admin.respon.import')
            ->with('success', 'Import diproses di background (queue). Refresh halaman ini untuk melihat hasilnya.');
    }

    /**
     * Batalkan preview/import yang belum selesai (hapus artefak file).
     */
    public function importCancel(Request $request)
    {
        $token = $request->input('token');
        if ($token) {
            foreach (Storage::disk('local')->files('imports') as $file) {
                if (str_starts_with(basename($file), $token.'.')) {
                    Storage::disk('local')->delete($file);
                }
            }
        }

        session()->forget('respon_import_token');

        return redirect()->route('admin.respon.import');
    }

    private function importState(): array
    {
        $status = null;
        $preview = null;
        $token = session('respon_import_token');

        if ($token && Cache::has(ImportMasterJob::cacheKey($token))) {
            $status = Cache::get(ImportMasterJob::cacheKey($token));
        }

        if ($token
            && ($status['status'] ?? null) === 'preview_ready'
            && Storage::disk('local')->exists("imports/{$token}.preview.json")) {
            $preview = json_decode(Storage::disk('local')->get("imports/{$token}.preview.json"), true);
        }

        return [$status, $preview, $token];
    }
}

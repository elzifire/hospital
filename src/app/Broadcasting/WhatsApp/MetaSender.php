<?php

namespace App\Broadcasting\WhatsApp;

use App\Models\MessageLog;
use App\Broadcasting\PhoneFormat;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Pengirim WhatsApp official (Meta Cloud API). Pesan inisiasi bisnis
 * wajib bertype "template": nama & bahasa diambil dari snapshot pesan
 * (meta_template_name / meta_language), parameter body dari
 * template_params — bentuk payload sama seperti pemanggilan langsung
 * ke {base_url}/{version}/{phone_number_id}/messages.
 *
 * Kegagalan jaringan/timeout dibiarkan melempar exception supaya job
 * queue bisa mencoba ulang; penolakan API (respons gagal) dikembalikan
 * sebagai hasil gagal yang permanen.
 */
class MetaSender implements WhatsAppSender
{
    public function __construct(protected MetaMedia $media) {}

    public function kirim(MessageLog $log): HasilKirim
    {
        $config = (array) config('whatsapp.meta');

        if (blank($config['token'] ?? null) || blank($config['phone_number_id'] ?? null)) {
            return HasilKirim::gagal('meta', 'Konfigurasi WA_META_TOKEN / WA_META_PHONE_NUMBER_ID belum diisi.');
        }

        $payload = $this->payload($log);

        Log::channel('whatsapp')->debug('MetaSender::kirim', [
            'log_id' => $log->id,
            'jenis' => $log->jenis,
            'rule' => $log->rule,
            'reminder_id' => $log->reminder_id,
            'to' => $payload['to'] ?? null,
            'tujuan_asli' => $log->penerima_no_hp,
            'test_target' => config('whatsapp.test_target'),
            'konten' => (string) $log->konten,
            'meta_template_name' => (string) $log->meta_template_name,
            'template_params' => (array) ($log->template_params ?? []),
            'meta_payload' => (array) ($log->meta_payload ?? []),
            'payload' => $payload,
        ]);

        return $this->post($config, $payload);
    }

    protected function post(array $config, array $payload): HasilKirim
    {
        $url = $this->url($config);

        Log::channel('whatsapp')->info('MetaSender::post mulai', [
            'url' => $url,
            'to' => $payload['to'] ?? null,
            'type' => $payload['type'] ?? null,
        ]);

        try {
            $respons = Http::withToken((string) $config['token'])
                ->timeout((int) ($config['timeout'] ?? 15))
                ->acceptJson()
                ->post($url, $payload);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('MetaSender::post jaringan gagal', [
                'url' => $url,
                'to' => $payload['to'] ?? null,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        Log::channel('whatsapp')->info('MetaSender::post respons', [
            'url' => $url,
            'to' => $payload['to'] ?? null,
            'status' => $respons->status(),
            'body' => $respons->body(),
        ]);

        if ($respons->failed()) {
            return HasilKirim::gagal('meta', $this->galat($respons));
        }

        return HasilKirim::sukses('meta', (string) $respons->json('messages.0.id'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(MessageLog $log): array
    {
        $payload = $this->payloadAsli($log);

        // Mode uji coba: semua kiriman dialihkan ke satu nomor tujuan
        // (config whatsapp.test_target) agar bisa diverifikasi langsung.
        $testTarget = config('whatsapp.test_target');
        if (filled($testTarget)) {
            $payload['to'] = PhoneFormat::toWa((string) $testTarget);

            Log::channel('whatsapp')->warning('MetaSender::payload dialihkan ke nomor uji coba', [
                'log_id' => $log->id,
                'tujuan_asli' => $log->penerima_no_hp,
                'test_target' => $testTarget,
                'to' => $payload['to'],
            ]);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    protected function payloadAsli(MessageLog $log): array
    {
        $metaPayload = (array) ($log->meta_payload ?? []);

        // Balasan media / interactive CTA-URL dari modul Respon (berlaku
        // dalam sesi 24 jam setelah balasan masuk).
        if (($metaPayload['kind'] ?? '') === 'media') {
            return $this->payloadMedia($log, $metaPayload);
        }

        if (($metaPayload['kind'] ?? '') === 'interactive') {
            return $this->payloadInteraktif($log, $metaPayload);
        }

        $config = (array) config('whatsapp.meta');

        // Balasan langsung (modul Respon) dikirim sebagai teks bebas —
        // tanpa template, sehingga tidak rawan galat parameter template
        // (#132000 / #132012). Meta menerima teks bebas di sesi 24 jam
        // setelah balasan masuk.
        if (blank($log->meta_template_name)) {
            return [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $log->penerima_no_hp,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => (string) $log->konten,
                ],
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $log->penerima_no_hp,
            'type' => 'template',
            'template' => [
                'name' => $log->meta_template_name ?: ($config['fallback_template'] ?? null),
                'language' => ['code' => $log->meta_language ?: ($config['fallback_language'] ?? 'en_US')],
            ],
        ];

        $params = array_values((array) ($log->template_params ?? []));
        $components = [];

        // Header — kirim hanya jika template Meta memang pakai media HEADER
        // dan local template menyediakan image_url. Deteksi tipe dari
        // snapshot memakai field `format` (atau `subtype` versi lama);
        // template dengan header teks/tanpa header tidak boleh menerima
        // komponen image, itu penyebab (#132012) di template berfoto.
        //
        // Media dikirim via id hasil upload (MetaMedia) — Meta menolak
        // link yang tidak terjangkau publik dengan (#132012) "expected
        // IMAGE, received UNKNOWN". Link dipakai hanya sebagai fallback.
        $gambar = $log->template?->image_url;
        if (filled($gambar)
            && $log->template !== null
            && in_array($this->headerMediaType($log), [null, 'IMAGE'], true)) {
            $mediaId = $this->media->idHeader($log->template);

            $components[] = filled($mediaId)
                ? [
                    'type' => 'header',
                    'parameters' => [[
                        'type' => 'image',
                        'image' => ['id' => $mediaId],
                    ]],
                ]
                : [
                    'type' => 'header',
                    'parameters' => [[
                        'type' => 'image',
                        'image' => ['link' => (string) $gambar],
                    ]],
                ];
        }

        // Body — format parameter bergantung snapshot meta_components:
        // named template memerlukan parameter_name sesuai nama
        // placeholder; positional (default) sama sekali tidak boleh
        // menyertakan parameter_name. Kesalahan ini menyebabkan
        // Meta menolak dengan (#132012) Parameter format does not
        // match format in the created template.
        $isNamed = $this->isNamedTemplate($log);
        $parameters = $this->bodyParameters($log, $params, $isNamed);

        if ($parameters !== []) {
            $components[] = [
                'type' => 'body',
                'parameters' => $parameters,
            ];
        }

        if ($components !== []) {
            $payload['template']['components'] = $components;
        }

        return $payload;
    }

    /**
     * Payload pesan media (image/audio/video/document) dari modul Respon.
     * Berkas disimpan di storage lokal lalu diunggah ke WABA saat kirim;
     * kegagalan unggah (atau berkas hilang) dilempar supaya AntreanKirim
     * mencatat pesan sebagai gagal dengan alasan yang bisa dibaca.
     *
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function payloadMedia(MessageLog $log, array $meta): array
    {
        $tipe = in_array((string) ($meta['tipe'] ?? ''), ['image', 'audio', 'video', 'document'], true)
            ? (string) $meta['tipe']
            : 'image';

        $path = (string) ($meta['path'] ?? '');
        $berkas = $path !== '' ? Storage::disk('public')->get($path) : null;

        if (blank($berkas)) {
            throw new RuntimeException('Berkas media tidak ditemukan di storage ('.$path.').');
        }

        $mediaId = $this->media->unggahBerkas(
            (string) $berkas,
            (string) ($meta['nama'] ?? 'media'),
            (string) ($meta['mime'] ?? 'application/octet-stream'),
        );

        if (blank($mediaId)) {
            throw new RuntimeException('Gagal mengunggah media ke WhatsApp (Media Upload API).');
        }

        $caption = trim((string) ($meta['caption'] ?? ''));
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $log->penerima_no_hp,
            'type' => $tipe,
            $tipe => ['id' => $mediaId],
        ];

        if ($tipe === 'image' || $tipe === 'video') {
            if ($caption !== '') {
                $payload[$tipe]['caption'] = $caption;
            }
        } elseif ($tipe === 'document') {
            $payload[$tipe]['filename'] = (string) ($meta['nama'] ?? 'berkas');
            if ($caption !== '') {
                $payload[$tipe]['caption'] = $caption;
            }
        }

        return $payload;
    }

    /**
     * Payload pesan interactive tipe CTA-URL. Header & footer opsional;
     * panjang text dibatasi sesuai ketentuan WhatsApp (header 60, body
     * 1024, footer 60) dan URL harus terdaftar di WABA.
     *
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function payloadInteraktif(MessageLog $log, array $meta): array
    {
        $interactive = [
            'type' => 'cta_url',
            'body' => [
                'text' => mb_substr((string) ($meta['body'] ?? ''), 0, 1024),
            ],
            'action' => [
                'name' => 'cta_url',
                'parameters' => [
                    'display_text' => mb_substr((string) ($meta['label'] ?? 'Buka'), 0, 40),
                    'url' => (string) ($meta['url'] ?? ''),
                ],
            ],
        ];

        $header = trim((string) ($meta['header'] ?? ''));
        if ($header !== '') {
            $interactive['header'] = ['type' => 'text', 'text' => mb_substr($header, 0, 60)];
        }

        $footer = trim((string) ($meta['footer'] ?? ''));
        if ($footer !== '') {
            $interactive['footer'] = ['text' => mb_substr($footer, 0, 60)];
        }

        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $log->penerima_no_hp,
            'type' => 'interactive',
            'interactive' => $interactive,
        ];
    }

    /**
     * Bangun array parameter body: named templates mengirim
     * parameter_name, positional templates hanya text. Untuk positional,
     * jumlah parameter disesuaikan (dipotong/digenapi) supaya sesuai
     * slot template.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function bodyParameters(MessageLog $log, array $params, bool $isNamed): array
    {
        if ($isNamed) {
            $names = $this->namedBodyParams($log);
            if ($names === []) {
                return [];
            }

            $localNames = array_values($log->template->tokenParam() ?: []);
            $valuesByName = ($localNames !== [] && count($localNames) === count($params))
                ? array_combine($localNames, $params)
                : [];

            $parameters = [];
            foreach ($names as $i => $name) {
                $value = $valuesByName[$name] ?? ($params[$i] ?? '—');
                $parameters[] = ['type' => 'text', 'text' => (string) $value, 'parameter_name' => $name];
            }

            return $parameters;
        }

        $expected = $this->jumlahBodyParam($log);

        if ($expected === null) {
            $aligned = $params;
        } elseif (count($params) > $expected) {
            $aligned = array_slice($params, 0, $expected);
        } else {
            $aligned = $params;
            while (count($aligned) < $expected) {
                $aligned[] = '—';
            }
        }

        $parameters = [];
        foreach ($aligned as $nilai) {
            $parameters[] = ['type' => 'text', 'text' => (string) $nilai];
        }

        return $parameters;
    }

    /**
     * Tipe media header template Meta (IMAGE/VIDEO/DOCUMENT/GIF) dari
     * snapshot komponen; null bila template tidak punya header sama
     * sekali. Field di snapshot adalah `format` (gif dinormalisasi ke
     * IMAGE karena dikirim dengan cara yang sama).
     */
    protected function headerMediaType(MessageLog $log): ?string
    {
        $header = $this->headerMeta($log);

        if ($header === null) {
            return null;
        }

        $tipe = strtoupper((string) ($header['format'] ?? $header['subtype'] ?? ''));

        if ($tipe === 'GIF') {
            return 'IMAGE';
        }

        return $tipe === '' ? null : $tipe;
    }

    /**
     * Komponen HEADER dari snapshot meta_components — null bila template
     * tidak punya header (atau belum pernah disinkron Meta).
     *
     * @return array<string, mixed>|null
     */
    protected function headerMeta(MessageLog $log): ?array
    {
        foreach ((array) ($log->template?->meta_components ?? []) as $komponen) {
            if (strtoupper((string) ($komponen['type'] ?? '')) === 'HEADER') {
                return (array) $komponen;
            }
        }

        return null;
    }

    /**
     * Apakah template Meta memakai placeholder bernama ({{nama}}) bukan
     * posisi ({{1}}). Ditentukan dari isi snapshot meta_components; bila
     * belum pernah disinkron fallback ke meta_param_tokens lokal.
     */
    protected function isNamedTemplate(MessageLog $log): bool
    {
        foreach ((array) ($log->template?->meta_components ?? []) as $komponen) {
            $teks = (string) ($komponen['text'] ?? '');
            if ($teks !== '' && preg_match('/\{\{[a-z][a-z0-9_]*\}\}/i', $teks)) {
                return true;
            }
        }

        return filled($log->template?->meta_param_tokens ?? null);
    }

    /**
     * Nama placeholder body sesuai urutan kemunculannya di snapshot Meta
     * ({{nama}} → 'nama'). Urutan = urutan parameter yang dikirim.
     *
     * @return array<int, string>
     */
    protected function namedBodyParams(MessageLog $log): array
    {
        foreach ((array) ($log->template?->meta_components ?? []) as $komponen) {
            if (strtoupper((string) ($komponen['type'] ?? '')) !== 'BODY') {
                continue;
            }

            $teks = (string) ($komponen['text'] ?? '');
            preg_match_all('/\{\{([a-z][a-z0-9_]*)\}\}/i', $teks, $cocok, PREG_SET_ORDER);

            if ($cocok !== []) {
                return array_values(array_map(static fn ($m) => (string) $m[1], $cocok));
            }
        }

        return array_values($log->template?->tokenParam() ?? []);
    }

    /**
     * Jumlah parameter body yang diharapkan oleh template Meta: indeks
     * placeholder {{N}} terbesar pada komponen BODY hasil sinkron. Bila
     * tidak diketahui (template lokal tanpa sinkron) dibiarkan apa adanya.
     */
    protected function jumlahBodyParam(MessageLog $log): ?int
    {
        $komponen = (array) ($log->template?->meta_components ?? []);
        $maks = 0;

        foreach ($komponen as $c) {
            if (strtoupper((string) ($c['type'] ?? '')) !== 'BODY') {
                continue;
            }

            $teks = (string) ($c['text'] ?? '');
            preg_match_all('/\{\{(\d+)\}\}/', $teks, $cocok);
            foreach ($cocok[1] ?? [] as $nomor) {
                $maks = max($maks, (int) $nomor);
            }
        }

        return $maks > 0 ? $maks : null;
    }

    /**
     * @param  array<int, mixed>  $params
     * @return array<int, mixed>
     */
    protected function selarasParams(MessageLog $log, array $params): array
    {
        $expected = $this->jumlahBodyParam($log);

        if ($expected === null) {
            return $params;
        }

        if (count($params) > $expected) {
            return array_slice($params, 0, $expected);
        }

        while (count($params) < $expected) {
            $params[] = '—';
        }

        return $params;
    }

    protected function url(array $config): string
    {
        return rtrim((string) ($config['base_url'] ?? 'https://graph.facebook.com'), '/')
            .'/'.($config['version'] ?? 'v25.0')
            .'/'.(string) $config['phone_number_id']
            .'/messages';
    }

    protected function galat(Response $respons): string
    {
        $pesan = $respons->json('error.message');

        if (is_string($pesan) && $pesan !== '') {
            Log::warning('Meta Cloud API menolak pesan: '.$pesan, [
                'status' => $respons->status(),
                'body' => $respons->body(),
            ]);
            Log::channel('whatsapp')->warning('Meta Cloud API menolak pesan: '.$pesan, [
                'status' => $respons->status(),
                'body' => $respons->body(),
            ]);

            // return 'Meta Cloud API menolak pesan: '.$pesan;
            return 'Terjadi Kesalahan sistem saat mengirim pesan WhatsApp. Silakan hubungi IT untuk bantuan.';
        }

        return 'Meta Cloud API menolak pesan (HTTP '.$respons->status().'): '.Str::limit($respons->body(), 200);
    }
}

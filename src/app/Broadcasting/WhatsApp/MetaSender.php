<?php

namespace App\Broadcasting\WhatsApp;

use App\Models\MessageLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
    public function kirim(MessageLog $log): HasilKirim
    {
        $config = (array) config('whatsapp.meta');

        if (blank($config['token'] ?? null) || blank($config['phone_number_id'] ?? null)) {
            return HasilKirim::gagal('meta', 'Konfigurasi WA_META_TOKEN / WA_META_PHONE_NUMBER_ID belum diisi.');
        }

        $respons = Http::withToken((string) $config['token'])
            ->timeout((int) ($config['timeout'] ?? 15))
            ->acceptJson()
            ->post($this->url($config), $this->payload($log));

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

        // Header — kirim hanya jika template Meta memang pakai IMAGE
        // HEADER dan local template menyediakan image_url. Template
        // dengan HEADER teks atau tanpa header diabaikan supaya tidak
        // terjadi kesalahan format (#132012).
        $gambar = $log->template?->image_url;
        if (filled($gambar)) {
            $headerMeta = $this->headerMeta($log);
            if ($headerMeta === null || strtoupper((string) ($headerMeta['subtype'] ?? '')) === 'IMAGE') {
                $components[] = [
                    'type' => 'header',
                    'parameters' => [[
                        'type' => 'image',
                        'image' => ['link' => (string) $gambar],
                    ]],
                ];
            }
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

            return 'Meta Cloud API menolak pesan: '.$pesan;
        }

        return 'Meta Cloud API menolak pesan (HTTP '.$respons->status().'): '.Str::limit($respons->body(), 200);
    }
}

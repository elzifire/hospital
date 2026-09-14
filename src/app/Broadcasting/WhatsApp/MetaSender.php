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

        // Gambar sampul template (HEADER/IMAGE) — urutkan sebelum body.
        $gambar = $log->template?->image_url;
        if (filled($gambar)) {
            $components[] = [
                'type' => 'header',
                'parameters' => [[
                    'type' => 'image',
                    'image' => ['link' => (string) $gambar],
                ]],
            ];
        }

        if ($params !== []) {
            // Template Meta yang memakai placeholder bernama ({{nama}},
            // {{hari_tanggal}}, …) wajib menyertakan parameter_name sesuai
            // nama placeholder aslinya — tanpa itu Meta menolak dengan
            // "(#100) Invalid parameter — Parameter name is missing or empty".
            $namaParams = array_values($log->template?->tokenParam() ?? []);

            $parameters = [];
            foreach ($params as $i => $nilai) {
                $parameter = ['type' => 'text', 'text' => (string) $nilai];
                if (isset($namaParams[$i]) && $namaParams[$i] !== '') {
                    $parameter['parameter_name'] = $namaParams[$i];
                }
                $parameters[] = $parameter;
            }

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

<?php

namespace App\Broadcasting\WhatsApp;

use App\Models\MessageTemplate;
use App\Support\TextSanitizer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Sinkronisasi template pesan dari Meta WhatsApp (graph.facebook.com).
 *
 * Mengambil (GET) seluruh template milik WhatsApp Business Account lewat
 * endpoint /{business_account_id}/message_templates lalu me-insert/update
 * ke tabel message_templates. Template diidentifikasi lewat meta_template_id;
 * bila belum ada, dicocokkan via meta_template_name + meta_language.
 *
 * Meta status mengikuti approval: APPROVED → is_active = true, selainnya
 * false (template yang dihapus/ditolak tidak layak dipilih untuk kirim).
 */
class MetaTemplateSync
{
    public const FIELDS = 'id,name,status,category,language,components,updated_time';

    private const LIMIT = 100;

    private const MAKS_HALAMAN = 100;

    /**
     * @return array{dibuat: int, diperbarui: int, jumlah: int, error: ?string}
     */
    public function sync(?string $status = null): array
    {
        $config = config('whatsapp.meta');
        $base = rtrim((string) ($config['base_url'] ?? 'https://graph.facebook.com'), '/');
        $version = (string) ($config['version'] ?? 'v25.0');
        $waba = (string) ($config['business_account_id'] ?? '');
        $token = (string) ($config['token'] ?? '');

        Log::channel('whatsapp')->info('Sinkronisasi Meta: memuat endpoint template.', [
            'url' => "{$base}/{$version}/{$waba}/message_templates",
        ]);

        if ($waba === '') {
            return ['dibuat' => 0, 'diperbarui' => 0, 'jumlah' => 0, 'error' => 'WA_META_BUSINESS_ACCOUNT_ID belum dikonfigurasi.'];
        }

        if ($token === '') {
            return ['dibuat' => 0, 'diperbarui' => 0, 'jumlah' => 0, 'error' => 'WA_META_TOKEN belum dikonfigurasi.'];
        }

        $client = Http::withToken($token)
            ->acceptJson()
            ->timeout((int) ($config['timeout'] ?? 15));

        $dibuat = 0;
        $diperbarui = 0;
        $jumlah = 0;
        $after = null;

        for ($halaman = 1; $halaman <= self::MAKS_HALAMAN; $halaman++) {
            $respon = $client->get(
                "{$base}/{$version}/{$waba}/message_templates",
                array_filter([
                    'fields' => self::FIELDS,
                    'limit' => self::LIMIT,
                    'after' => $after,
                    'status' => $status,
                ], fn ($v) => $v !== null && $v !== ''),
            );

            if ($respon->failed()) {
                $pesan = (string) ($respon->json('error.message') ?: $respon->reason());

                Log::channel('whatsapp')->warning('Sinkronisasi Meta gagal (HTTP '.$respon->status().').', [
                    'error' => $pesan,
                    'body' => Str::limit((string) $respon->body(), 2000),
                ]);

                return ['dibuat' => $dibuat, 'diperbarui' => $diperbarui, 'jumlah' => $jumlah, 'error' => $pesan];
            }

            $itemHalaman = $respon->json('data', []);

            Log::channel('whatsapp')->info('Sinkronisasi Meta: respons halaman diterima.', [
                'halaman' => $halaman,
                'jumlah_item' => count($itemHalaman),
                'status_http' => $respon->status(),
            ]);

            foreach ($itemHalaman as $item) {
                $item = (array) $item;

                Log::channel('whatsapp')->info('Sinkronisasi Meta: template dari respons.', [
                    'name' => (string) ($item['name'] ?? ''),
                    'language' => (string) ($item['language'] ?? ''),
                    'status' => (string) ($item['status'] ?? ''),
                    'components' => array_values((array) ($item['components'] ?? [])),
                ]);

                $hasil = $this->simpan($item);
                $dibuat += $hasil === 'created' ? 1 : 0;
                $diperbarui += $hasil === 'updated' ? 1 : 0;
                $jumlah++;
            }

            $after = $respon->json('paging.cursors.after');

            if ($after === null || $after === '') {
                break;
            }
        }

        return ['dibuat' => $dibuat, 'diperbarui' => $diperbarui, 'jumlah' => $jumlah, 'error' => null];
    }

    /**
     * Insert/update satu template Meta ke message_templates.
     *
     * @return string 'created' | 'updated'
     */
    protected function simpan(array $data): string
    {
        $id = (string) ($data['id'] ?? '');
        $nama = (string) ($data['name'] ?? '');
        $bahasa = (string) ($data['language'] ?? '');
        $status = strtoupper((string) ($data['status'] ?? ''));
        $kategori = strtoupper((string) ($data['category'] ?? ''));
        $komponen = (array) ($data['components'] ?? []);
        $this->bersihkanKomponen($komponen);
        $konten = $this->teksDariKomponen($komponen);
        $diperbaruiMeta = $this->tanggal($data['updated_time'] ?? null);

        $headerMedia = $this->headerMediaFormat($komponen);
        $imageUrl = $this->unduhHeaderMedia($nama, $bahasa, $komponen);

        if ($headerMedia !== null && $imageUrl === null) {
            $templateLama = MessageTemplate::where('meta_template_name', $nama)
                ->whereNull('meta_template_id')
                ->when($bahasa !== '', fn ($q) => $q->where('meta_language', $bahasa))
                ->first();

            if (blank($templateLama->image_url ?? null)) {
                Log::channel('whatsapp')->warning('Template Meta berheader media — gambar belum terisi dan tidak ada URL media yang bisa diunduh.', [
                    'meta_template_name' => $nama,
                    'language' => $bahasa,
                    'header_format' => $headerMedia,
                ]);
            }
        }

        $template = MessageTemplate::where('meta_template_id', $id)->first()
            ?? MessageTemplate::where('meta_template_name', $nama)
                ->whereNull('meta_template_id')
                ->when($bahasa !== '', fn ($q) => $q->where('meta_language', $bahasa))
                ->first();

        $dataTemplate = [
            'meta_template_id' => $id,
            'judul' => TextSanitizer::win1252(Str::title(str_replace('_', ' ', $nama))),
            'channel' => 'WhatsApp',
            'konten' => $konten === '' ? ($template->konten ?? '') : TextSanitizer::win1252($konten),
            'meta_template_name' => $nama,
            'meta_language' => $bahasa,
            'meta_status' => $status,
            'meta_category' => $kategori,
            'meta_components' => $komponen,
            'meta_updated_at' => $diperbaruiMeta,
            'last_synced_at' => now(),
            'is_active' => $status === 'APPROVED',
        ];

        if ($imageUrl !== null) {
            $dataTemplate['image_url'] = $imageUrl;

            if ($template !== null && $template->image_url !== $imageUrl) {
                $dataTemplate['meta_media_id'] = null;
                $dataTemplate['meta_media_at'] = null;
            }
        }

        if ($template !== null) {
            $template->update($dataTemplate);

            return 'updated';
        }

        MessageTemplate::create([
            ...$dataTemplate,
            'kode' => $this->kodeBaru($nama, $bahasa),
            'deskripsi' => TextSanitizer::win1252('Tersinkron dari Meta WhatsApp'.($kategori !== '' ? " ({$kategori})" : '').'.'),
        ]);

        return 'created';
    }

    /**
     * Strimin tiap teks dalam komponen (text, example, button, header) supaya
     * aman untuk encoding database. Dimutasi langsung pada array asal.
     *
     * @param  array<int, array<string, mixed>>  $komponen
     */
    protected function bersihkanKomponen(array &$komponen): void
    {
        array_walk_recursive($komponen, static function (&$nilai): void {
            if (is_string($nilai)) {
                $nilai = TextSanitizer::win1252($nilai);
            }
        });
    }

    /**
     * Format media header dari komponen template (IMAGE/VIDEO/DOCUMENT/
     * GIF/LOCATION/TEXT) — null bila template tidak punya header sama
     * sekali. Field di respons Meta adalah `format` (fallback `subtype`).
     *
     * @param  array<int, array<string, mixed>>  $komponen
     */
    protected function headerMediaFormat(array $komponen): ?string
    {
        foreach ($komponen as $c) {
            if (($c['type'] ?? '') !== 'HEADER') {
                continue;
            }

            $format = strtoupper((string) ($c['format'] ?? $c['subtype'] ?? ''));

            return $format === '' ? null : $format;
        }

        return null;
    }

    /**
     * Unduh media header dari snapshot Meta (example.header_handle) ke
     * storage publik Laravel supaya tidak menggantung ke URL CDN Meta yang
     * berubah-ubah. Mengembalikan URL relatif (/storage/...) atau null.
     *
     * @param  array<int, array<string, mixed>>  $komponen
     */
    protected function unduhHeaderMedia(string $nama, string $bahasa, array $komponen): ?string
    {
        if ($this->headerMediaFormat($komponen) !== 'IMAGE') {
            return null;
        }

        $link = $this->headerMediaLink($komponen);

        if ($link === null) {
            return null;
        }

        try {
            $respons = Http::timeout(30)->get($link);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('MetaTemplateSync: gagal mengambil media header.', [
                'meta_template_name' => $nama,
                'link' => $link,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if ($respons->failed() || $respons->body() === '') {
            Log::channel('whatsapp')->warning('MetaTemplateSync: media header tidak bisa diunduh.', [
                'meta_template_name' => $nama,
                'link' => $link,
                'status' => $respons->status(),
            ]);

            return null;
        }

        $mime = strtolower((string) strtok((string) ($respons->header('Content-Type') ?: 'image/jpeg'), ';'));
        $ekstensi = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null,
        };

        if ($ekstensi === null) {
            Log::channel('whatsapp')->warning('MetaTemplateSync: tipe media header tidak didukung.', [
                'meta_template_name' => $nama,
                'mime' => $mime,
            ]);

            return null;
        }

        $slug = Str::lower(Str::slug($nama, '_'));
        $tujuan = 'whatsapp/templates/'.$slug.($bahasa !== '' ? '-'.$bahasa : '').'.'.$ekstensi;

        Storage::disk('public')->put($tujuan, $respons->body());

        Log::channel('whatsapp')->info('MetaTemplateSync: media header disimpan ke storage publik.', [
            'meta_template_name' => $nama,
            'image_url' => '/storage/'.$tujuan,
            'mime' => $mime,
        ]);

        return '/storage/'.$tujuan;
    }

    /**
     * URL media header dari contoh snapshot Meta (example.header_handle).
     * Bisa berupa URL CDN publik (dapat diunduh) atau media handle
     * (tidak bisa diunduh langsung) — hanya URL http(s) yang dipakai.
     *
     * @param  array<int, array<string, mixed>>  $komponen
     */
    protected function headerMediaLink(array $komponen): ?string
    {
        foreach ($komponen as $komp) {
            if (($komp['type'] ?? '') !== 'HEADER') {
                continue;
            }

            $link = trim((string) (($komp['example']['header_handle'] ?? [])[0] ?? ''));

            return preg_match('#^https?://#i', $link) === 1 ? $link : null;
        }

        return null;
    }

    /**
     * Kode internal unik & deterministik untuk template hasil sinkron.
     */
    protected function kodeBaru(string $nama, string $bahasa): string
    {
        $basis = Str::upper(Str::slug(str_replace(['_', '-'], ' ', $nama), '_'));
        $asli = 'META-'.$basis.($bahasa !== '' ? '-'.$bahasa : '');
        $kode = Str::substr($asli, 0, 50);
        $i = 2;

        while (MessageTemplate::where('kode', $kode)->exists()) {
            $kode = Str::substr($asli, 0, 48).'-'.$i;
            $i++;
        }

        return $kode;
    }

    /**
     * Teks body (fallback header) template Meta — mengandung placeholder
     * posisi {{1}}, {{2}}, … sesuai definisi Meta.
     *
     * @param  array<int, array<string, mixed>>  $komponen
     */
    protected function teksDariKomponen(array $komponen): string
    {
        foreach ($komponen as $c) {
            if (($c['type'] ?? '') === 'BODY' && filled($c['text'] ?? null)) {
                return (string) $c['text'];
            }
        }

        foreach ($komponen as $c) {
            if (($c['type'] ?? '') === 'HEADER' && filled($c['text'] ?? null)) {
                return (string) $c['text'];
            }
        }

        return '';
    }

    protected function tanggal(mixed $nilai): ?Carbon
    {
        return filled($nilai) ? Carbon::parse($nilai) : null;
    }
}

<?php

namespace App\Broadcasting\WhatsApp;

use App\Models\MessageTemplate;
use App\Support\TextSanitizer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
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

                return ['dibuat' => $dibuat, 'diperbarui' => $diperbarui, 'jumlah' => $jumlah, 'error' => $pesan];
            }

            foreach ($respon->json('data', []) as $item) {
                $hasil = $this->simpan((array) $item);
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

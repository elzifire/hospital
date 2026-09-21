<?php

namespace App\Broadcasting\WhatsApp;

use App\Models\MessageTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Registrasi (daftar) template pesan baru ke Meta WhatsApp
 * (graph.facebook.com).
 *
 * Membuat template lewat endpoint POST /{business_account_id}/message_templates
 * memakai placeholder bernama ({{token}}) supaya saat pengiriman nilai
 * parameter dipetakan via parameter_name (lihat MetaSender::namedBodyParams).
 *
 * Konfigurasi wajib dari .env: WA_META_BUSINESS_ACCOUNT_ID dan WA_META_TOKEN
 * (bagian config/whatsapp.php → meta).
 */
class MetaTemplateRegistrar
{
    /**
     * Daftarkan sebuah template lokal ke Meta.
     *
     * @return array{id?: string, status?: string, name?: string, language?: string,
     *              category?: string, components?: array<int, array<string, mixed>>,
     *              error: ?string}
     */
    public function daftar(MessageTemplate $template, string $bahasa = '', string $kategori = 'UTILITY'): array
    {
        $config = config('whatsapp.meta');
        $base = rtrim((string) ($config['base_url'] ?? 'https://graph.facebook.com'), '/');
        $version = (string) ($config['version'] ?? 'v25.0');
        $waba = (string) ($config['business_account_id'] ?? '');
        $token = (string) ($config['token'] ?? '');

        if ($waba === '') {
            return ['error' => 'WA_META_BUSINESS_ACCOUNT_ID belum dikonfigurasi di .env.'];
        }

        if ($token === '') {
            return ['error' => 'WA_META_TOKEN belum dikonfigurasi di .env.'];
        }

        $bahasa = $bahasa !== '' ? $bahasa : (string) ($config['default_language'] ?? 'en_US');
        $nama = $this->namaMeta($template->judul);
        $teksMeta = $this->kontenNamaMeta($template->konten);

        $komponenBodi = [
            'type' => 'BODY',
            'text' => $teksMeta,
        ];

        $contoh = $this->contohNilai($template->konten);
        if ($contoh !== []) {
            $komponenBodi['example'] = ['body_text' => [$contoh]];
        }

        $payload = [
            'name' => $nama,
            'language' => $bahasa,
            'category' => strtoupper($kategori),
            'components' => [$komponenBodi],
        ];

        Log::channel('whatsapp')->info('Registrasi template Meta dimulai.', [
            'judul' => $template->judul,
            'nama_meta' => $nama,
            'bahasa' => $bahasa,
            'kategori' => strtoupper($kategori),
        ]);

        $respon = Http::withToken($token)
            ->acceptJson()
            ->timeout((int) ($config['timeout'] ?? 15))
            ->post("{$base}/{$version}/{$waba}/message_templates", $payload);

        if ($respon->failed()) {
            $pesan = (string) ($respon->json('error.message') ?: $respon->reason());

            Log::channel('whatsapp')->warning('Registrasi template Meta gagal (HTTP '.$respon->status().').', [
                'judul' => $template->judul,
                'nama_meta' => $nama,
                'error' => $pesan,
                'body' => Str::limit((string) $respon->body(), 2000),
            ]);

            return ['error' => $pesan];
        }

        $id = (string) ($respon->json('id') ?? '');
        $status = strtoupper((string) ($respon->json('status') ?? 'PENDING'));

        Log::channel('whatsapp')->info('Template berhasil didaftarkan di Meta.', [
            'judul' => $template->judul,
            'nama_meta' => $nama,
            'id' => $id,
            'status' => $status,
        ]);

        return [
            'id' => $id,
            'status' => $status,
            'name' => $nama,
            'language' => $bahasa,
            'category' => strtoupper($kategori),
            'components' => [$komponenBodi],
            'error' => null,
        ];
    }

    /**
     * Nama template Meta: huruf kecil, angka, dan underscore saja
     * (aturan Meta: alfanumerik + underscore, maksimal 512 karakter).
     */
    public function namaMeta(string $judul): string
    {
        $fromJudul = strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', trim($judul)));
        $fromJudul = trim($fromJudul, '_');
        $suffix = Str::lower(Str::random(4));

        $nama = $fromJudul !== '' ? $fromJudul : $suffix;

        return Str::substr($nama, 0, 507).'_'.$suffix;
    }

    /**
     * Ubah placeholder lokal {token} menjadi placeholder bernama Meta
     * {{token}} agar nilai dikirim dengan parameter_name.
     */
    protected function kontenNamaMeta(string $konten): string
    {
        return (string) preg_replace('/\{([a-z0-9_]+)\}/i', '{{$1}}', (string) $konten);
    }

    /**
     * Contoh nilai parameter untuk validasi & approval Meta, dipetakan
     * dari definisi variabel PNPP. Urutan sesuai kemunculan token pertama
     * kali di konten.
     *
     * @return array<int, string>
     */
    protected function contohNilai(string $konten): array
    {
        preg_match_all('/\{([a-z0-9_]+)\}/i', (string) $konten, $cocok);
        $tokens = array_values(array_unique($cocok[1] ?? []));

        if ($tokens === []) {
            return [];
        }

        $contohByToken = [];
        foreach (MessageTemplate::variables() as $v) {
            $contohByToken[trim((string) $v['var'], '{}')] = $v['contoh'];
        }

        return array_map(
            static fn (string $t) => $contohByToken[$t] ?? 'Isi-'.$t,
            $tokens
        );
    }
}

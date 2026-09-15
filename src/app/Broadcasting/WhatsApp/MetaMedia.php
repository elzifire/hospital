<?php

namespace App\Broadcasting\WhatsApp;

use App\Models\MessageTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Pengunggah media header template ke Meta (Media Upload API).
 *
 * Template berfoto wajib menyertakan media saat dikirim — kalau memakai
 * `image.link`, Meta mengambil URL-nya sendiri dan menolak dengan
 * "(#132012) header: Format mismatch, expected IMAGE, received UNKNOWN"
 * bila URL tidak bisa dijangkau publik. Solusinya media diunggah dulu
 * ke WABA via POST /{phone_number_id}/media lalu dikirim pakai
 * `image.id`. Media berlaku 30 hari sehingga id di-cache di tabel
 * message_templates dan diunggah ulang otomatis bila mendekati kadaluarsa.
 */
class MetaMedia
{
    /**
     * Media hasil upload berlaku 30 hari — unggah ulang sebelum itu
     * supaya tidak kejadian id kedaluwarsa saat kirim.
     */
    private const UMUR_MEDIA_HARI = 25;

    /**
     * Ambil media id untuk image_url template: pakai cache bila masih
     * berlaku, selain itu unggah ulang. Null bila gambar tidak bisa
     * diambil/diunggah.
     */
    public function idHeader(MessageTemplate $template): ?string
    {
        if (blank($template->image_url)) {
            return null;
        }

        if (filled($template->meta_media_id)
            && $template->meta_media_at?->gt(now()->subDays(self::UMUR_MEDIA_HARI))) {
            return $template->meta_media_id;
        }

        $mediaId = $this->unggah((string) $template->image_url);

        if ($mediaId !== null) {
            $template->forceFill([
                'meta_media_id' => $mediaId,
                'meta_media_at' => now(),
            ])->save();

            Log::channel('whatsapp')->info('MetaMedia: media header tersimpan di cache.', [
                'id_template' => $template->id,
                'judul' => $template->judul,
                'meta_media_id' => $mediaId,
            ]);
        }

        return $mediaId;
    }

    /**
     * Unduh gambar dari image_url lalu unggah ke WABA; kembalikan media
     * id dari respons Meta, atau null bila gagal.
     */
    protected function unggah(string $url): ?string
    {
        $config = (array) config('whatsapp.meta');
        $base = rtrim((string) ($config['base_url'] ?? 'https://graph.facebook.com'), '/');
        $version = (string) ($config['version'] ?? 'v25.0');
        $phone = (string) ($config['phone_number_id'] ?? '');
        $token = (string) ($config['token'] ?? '');

        if ($phone === '' || $token === '') {
            Log::channel('whatsapp')->warning('MetaMedia: token / phone_number_id belum dikonfigurasi.', ['url' => $url]);

            return null;
        }

        $berkas = $this->ambilBerkas($url);

        if ($berkas === null) {
            return null;
        }

        [$bytes, $mime] = $berkas;

        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            Log::channel('whatsapp')->warning('MetaMedia: tipe MIME gambar tidak didukung.', [
                'url' => $url,
                'mime' => $mime,
            ]);

            return null;
        }

        $nama = basename((string) parse_url($url, PHP_URL_PATH)) ?: 'sampul';
        $respon = Http::asMultipart()
            ->withToken($token)
            ->timeout((int) ($config['timeout'] ?? 20))
            ->attach('file', $bytes, $nama, ['Content-Type' => $mime])
            ->post("{$base}/{$version}/{$phone}/media", [
                'messaging_product' => 'whatsapp',
            ]);

        if ($respon->failed()) {
            Log::channel('whatsapp')->warning('MetaMedia: upload media ditolak Meta.', [
                'url' => $url,
                'status' => $respon->status(),
                'body' => Str::limit((string) $respon->body(), 1000),
            ]);

            return null;
        }

        $mediaId = $respon->json('id');

        if (! is_string($mediaId) || $mediaId === '') {
            Log::channel('whatsapp')->warning('MetaMedia: respons upload tanpa media id.', [
                'url' => $url,
                'body' => Str::limit((string) $respon->body(), 1000),
            ]);

            return null;
        }

        return $mediaId;
    }

    /**
     * Ambil byte gambar yang akan diunggah. Bila image_url menunjuk ke
     * storage publik Laravel (/storage/...), berkas dibaca langsung dari
     * disk server (hemat bandwidth, tidak bergantung URL publik); selain
     * itu diunduh via HTTP lalu diperiksa MIME-nya.
     *
     * @return array{0: string, 1: string}|null — [byte, mime]
     */
    protected function ambilBerkas(string $url): ?array
    {
        if (Str::startsWith($url, '/storage/')) {
            $relatif = ltrim(Str::after($url, '/storage/'), '/');
            $disk = Storage::disk('public');

            if (! $disk->exists($relatif)) {
                Log::channel('whatsapp')->warning('MetaMedia: berkas storage publik tidak ditemukan.', ['url' => $url]);

                return null;
            }

            $bytes = $disk->get($relatif);

            if (blank($bytes)) {
                Log::channel('whatsapp')->warning('MetaMedia: berkas storage publik kosong.', ['url' => $url]);

                return null;
            }

            $path = public_path('storage/'.$relatif);
            $mime = is_file($path) ? (string) mime_content_type($path) : 'image/jpeg';

            return [(string) $bytes, strtolower($mime)];
        }

        try {
            $berkas = Http::timeout(30)->get($url);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('MetaMedia: gagal mengambil gambar.', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }

        if ($berkas->failed() || $berkas->body() === '') {
            Log::channel('whatsapp')->warning('MetaMedia: URL gambar tidak bisa diambil.', [
                'url' => $url,
                'status' => $berkas->status(),
                'body' => Str::limit((string) $berkas->body(), 300),
            ]);

            return null;
        }

        $mime = strtolower((string) strtok((string) ($berkas->header('Content-Type') ?: 'image/jpeg'), ';'));

        return [$berkas->body(), $mime];
    }
}

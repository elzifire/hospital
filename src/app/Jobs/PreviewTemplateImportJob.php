<?php

namespace App\Jobs;

use App\Models\TemplateCategory;
use App\Support\SheetHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PreviewTemplateImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 1;

    public function __construct(
        public string $token,
        public string $ext,
    ) {}

    public static function cacheKey(string $token): string
    {
        return "import_template:{$token}";
    }

    public function handle(): void
    {
        try {
            $this->setStatus(['status' => 'preview_processing']);

            $path = "imports/template_{$this->token}.{$this->ext}";
            if (! Storage::disk('local')->exists($path)) {
                throw new \RuntimeException('File upload tidak ditemukan.');
            }

            $parsed = SheetHelper::readToRows(Storage::disk('local')->path($path));

            $expectedHeaders = ['Kategori', 'Judul', 'Channel', 'Isi Pesan', 'Deskripsi', 'Status'];
            
            // Map header case-insensitively
            $actualHeaders = $parsed['headers'];
            $headerMap = [];
            foreach ($expectedHeaders as $exp) {
                foreach ($actualHeaders as $act) {
                    if (strcasecmp(trim($act), trim($exp)) === 0 || str_contains(strtolower($act), strtolower($exp))) {
                        $headerMap[$exp] = $act;
                        break;
                    }
                }
            }

            // Fallback checking required minimums
            if (! isset($headerMap['Judul']) || ! isset($headerMap['Isi Pesan'])) {
                throw new \RuntimeException('Format kolom tidak sesuai. Minimal kolom "Judul" dan "Isi Pesan" harus tersedia.');
            }

            $valid = 0;
            $invalid = 0;
            $previewRows = [];

            $categories = TemplateCategory::pluck('id', 'nama')->all();

            foreach ($parsed['rows'] as $i => $rawRow) {
                $rowNum = $i + 2;
                $kategori = trim($rawRow[$headerMap['Kategori'] ?? 'Kategori'] ?? 'Umum');
                $judul = trim($rawRow[$headerMap['Judul'] ?? 'Judul'] ?? '');
                $channel = trim($rawRow[$headerMap['Channel'] ?? 'Channel'] ?? 'WhatsApp') ?: 'WhatsApp';
                $konten = trim($rawRow[$headerMap['Isi Pesan'] ?? 'Isi Pesan'] ?? '');
                $deskripsi = trim($rawRow[$headerMap['Deskripsi'] ?? 'Deskripsi'] ?? '');
                $statusRaw = trim($rawRow[$headerMap['Status'] ?? 'Status'] ?? 'Aktif');

                $errors = [];
                if (empty($judul)) {
                    $errors[] = 'Judul template wajib diisi.';
                }
                if (empty($konten)) {
                    $errors[] = 'Isi pesan wajib diisi.';
                }

                $isActive = ! in_array(strtolower($statusRaw), ['nonaktif', 'tidak', 'false', '0', 'inaktif', 'no']);

                $isRowValid = empty($errors);
                if ($isRowValid) {
                    $valid++;
                } else {
                    $invalid++;
                }

                $previewRows[] = [
                    'row_number' => $rowNum,
                    'kategori'   => $kategori ?: 'Umum',
                    'judul'      => $judul,
                    'channel'    => $channel,
                    'konten'     => $konten,
                    'deskripsi'  => $deskripsi,
                    'is_active'  => $isActive,
                    'valid'      => $isRowValid,
                    'errors'     => $errors,
                ];
            }

            $payload = [
                'total'        => count($previewRows),
                'valid'        => $valid,
                'invalid'      => $invalid,
                'rows'         => $previewRows,
                'categories'   => array_keys($categories),
                'generated_at' => now()->toIso8601String(),
            ];

            Storage::disk('local')->put("imports/template_{$this->token}.preview.json", json_encode($payload));

            $this->setStatus([
                'status'  => 'preview_ready',
                'total'   => count($previewRows),
                'valid'   => $valid,
                'invalid' => $invalid,
            ]);
        } catch (\Throwable $e) {
            Log::error("PreviewTemplateImportJob error [{$this->token}]: " . $e->getMessage());
            $this->setStatus([
                'status' => 'preview_failed',
                'error'  => $e->getMessage(),
            ]);
        }
    }

    private function setStatus(array $data): void
    {
        Cache::put(self::cacheKey($this->token), $data, now()->addHours(2));
    }
}

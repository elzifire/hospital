<?php

namespace App\Jobs;

use App\Models\MessageTemplate;
use App\Models\TemplateCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportTemplateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(
        public string $token,
    ) {}

    public static function cacheKey(string $token): string
    {
        return "import_template:{$token}";
    }

    public function handle(): void
    {
        $path = "imports/template_{$this->token}.rows.json";

        try {
            $this->setStatus(['status' => 'processing']);

            if (! Storage::disk('local')->exists($path)) {
                throw new \RuntimeException('File data import tidak ditemukan.');
            }

            $rows = json_decode(Storage::disk('local')->get($path), true);
            if (! is_array($rows)) {
                throw new \RuntimeException('Data import tidak valid.');
            }

            $imported = 0;
            $failed = 0;
            $errors = [];

            // Category cache
            $categories = TemplateCategory::all()->keyBy(fn ($c) => strtolower($c->nama));

            foreach ($rows as $row) {
                try {
                    $catName = trim($row['kategori'] ?? 'Umum') ?: 'Umum';
                    $catKey = strtolower($catName);

                    if (! isset($categories[$catKey])) {
                        $newCat = TemplateCategory::create([
                            'nama'      => $catName,
                            'slug'      => Str::slug($catName),
                            'warna'     => 'sky',
                            'deskripsi' => "Kategori otomatis dibuat dari import file.",
                            'is_active' => true,
                        ]);
                        $categories[$catKey] = $newCat;
                    }

                    $category = $categories[$catKey];

                    MessageTemplate::create([
                        'template_category_id' => $category->id,
                        'judul'                => trim($row['judul']),
                        'channel'              => trim($row['channel'] ?? 'WhatsApp') ?: 'WhatsApp',
                        'konten'               => trim($row['konten']),
                        'deskripsi'            => trim($row['deskripsi'] ?? ''),
                        'is_active'            => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    ]);

                    $imported++;
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = "Baris " . ($row['row_number'] ?? '?') . ": " . $e->getMessage();
                }
            }

            $this->setStatus([
                'status'   => 'completed',
                'imported' => $imported,
                'failed'   => $failed,
                'errors'   => array_slice($errors, 0, 10),
            ]);

            // Clean up files
            Storage::disk('local')->delete([
                $path,
                "imports/template_{$this->token}.preview.json",
            ]);
        } catch (\Throwable $e) {
            Log::error("ImportTemplateJob error [{$this->token}]: " . $e->getMessage());
            $this->setStatus([
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ]);
        }
    }

    private function setStatus(array $data): void
    {
        Cache::put(self::cacheKey($this->token), $data, now()->addHours(2));
    }
}

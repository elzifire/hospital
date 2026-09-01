<?php

namespace App\Jobs;

use App\Support\MasterRegistry;
use App\Support\SheetHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PreviewImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public string $entity,
        public string $token,
        public string $ext,
    ) {
        //
    }

    public function handle(): void
    {
        $config = MasterRegistry::config($this->entity);

        try {
            $this->setStatus(['status' => 'preview_processing']);

            $path = "imports/{$this->token}.{$this->ext}";

            if (! Storage::disk('local')->exists($path)) {
                throw new \RuntimeException('File upload tidak ditemukan.');
            }

            $parsed = SheetHelper::readToRows(Storage::disk('local')->path($path));

            $missing = array_diff($config['headers'], $parsed['headers']);
            $extra   = array_diff($parsed['headers'], $config['headers']);

            if ($missing !== [] || $extra !== []) {
                throw new \RuntimeException('Format kolom tidak sesuai. Unduh template untuk melihat susunan kolom yang benar.');
            }

            $valid = 0;
            $invalid = 0;
            $previewRows = [];

            foreach ($parsed['rows'] as $row) {
                $result = ($config['parse'])($row);
                $ok = empty($result['errors']);
                $ok ? $valid++ : $invalid++;

                $values = [];
                foreach ($config['headers'] as $header) {
                    $values[$header] = $row[$header] ?? '';
                }

                $previewRows[] = [
                    'values' => $values,
                    'errors' => $result['errors'],
                ];
            }

            $preview = [
                'total'   => $valid + $invalid,
                'valid'   => $valid,
                'invalid' => $invalid,
                'rows'    => $previewRows,
            ];

            Storage::disk('local')->put("imports/{$this->token}.preview.json", json_encode($preview));

            $this->setStatus([
                'status'  => 'preview_ready',
                'total'   => $preview['total'],
                'valid'   => $valid,
                'invalid' => $invalid,
            ]);
        } catch (\Throwable $e) {
            Log::error('Preview import gagal', [
                'entity' => $this->entity,
                'token'  => $this->token,
                'error'  => $e->getMessage(),
            ]);

            $this->setStatus([
                'status'  => 'preview_failed',
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function setStatus(array $data): void
    {
        Cache::put(ImportMasterJob::cacheKey($this->token), $data, now()->addHours(2));
    }
}

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

    public const PREVIEW_LIMIT = 500;

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

            $valid = 0;
            $invalid = 0;
            $total = 0;
            $previewPath = Storage::disk('local')->path("imports/{$this->token}.preview.json");
            $previewFile = fopen($previewPath, 'wb');
            if ($previewFile === false) {
                throw new \RuntimeException('File preview tidak dapat dibuat.');
            }

            fwrite($previewFile, '{"total":0,"valid":0,"invalid":0,"rows":[');
            $firstPreviewRow = true;
            $headers = [];
            $batchRows = [];
            $batchNumber = 0;

            SheetHelper::readInChunks(Storage::disk('local')->path($path), function (array $fileHeaders, ?array $row) use ($config, &$headers, &$valid, &$invalid, &$total, &$firstPreviewRow, &$batchRows, &$batchNumber, $previewFile): void {
                if ($row === null) {
                    $headers = $fileHeaders;
                    $missing = array_diff($config['headers'], $headers);
                    $extra = array_diff($headers, $config['headers']);
                    if ($missing !== [] || $extra !== []) {
                        throw new \RuntimeException('Format kolom tidak sesuai. Unduh template untuk melihat susunan kolom yang benar.');
                    }

                    return;
                }

                $result = ($config['parse'])($row);
                $ok = empty($result['errors']);
                $ok ? $valid++ : $invalid++;
                $total++;

                $values = [];
                foreach ($config['headers'] as $header) {
                    $values[$header] = $row[$header] ?? '';
                }

                $previewRow = [
                    'values' => $values,
                    'errors' => $result['errors'],
                ];
                if ($total <= self::PREVIEW_LIMIT) {
                    if (! $firstPreviewRow) {
                        fwrite($previewFile, ',');
                    }
                    fwrite($previewFile, json_encode($previewRow, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    $firstPreviewRow = false;
                }
                $batchRows[] = $values;

                if (count($batchRows) >= ImportMasterJob::BATCH_SIZE) {
                    Storage::disk('local')->put("imports/{$this->token}.rows.{$batchNumber}.json", json_encode($batchRows, JSON_UNESCAPED_UNICODE));
                    $batchRows = [];
                    $batchNumber++;
                }
            }, SheetHelper::CHUNK_SIZE);

            if ($batchRows !== []) {
                Storage::disk('local')->put("imports/{$this->token}.rows.{$batchNumber}.json", json_encode($batchRows, JSON_UNESCAPED_UNICODE));
                $batchNumber++;
            }

            fwrite($previewFile, ']}');
            fclose($previewFile);

            $this->setStatus([
                'status' => 'preview_ready',
                'total' => $total,
                'total_chunks' => $batchNumber,
                'valid' => $valid,
                'invalid' => $invalid,
            ]);
        } catch (\Throwable $e) {
            Log::error('Preview import gagal', [
                'entity' => $this->entity,
                'token' => $this->token,
                'error' => $e->getMessage(),
            ]);

            $this->setStatus([
                'status' => 'preview_failed',
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function setStatus(array $data): void
    {
        Cache::put(ImportMasterJob::cacheKey($this->token), $data, now()->addHours(2));
    }
}

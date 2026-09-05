<?php

namespace App\Jobs;

use App\Support\MasterRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportMasterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const BATCH_SIZE = 250;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        public string $entity,
        public string $token,
        public int $chunk = 0,
        public ?int $totalChunks = null,
    ) {
        //
    }

    public static function cacheKey(string $token): string
    {
        return "import:{$token}";
    }

    public function handle(): void
    {
        $config = MasterRegistry::config($this->entity);
        $modelClass = $config['model'];
        $sync = $config['sync'] ?? null;
        $resolve = $config['resolve'] ?? null;

        $path = "imports/{$this->token}.rows.json";

        try {
            $this->updateStatus(['status' => 'processing']);

            if (! Storage::disk('local')->exists($path)) {
                throw new \RuntimeException('File import tidak ditemukan.');
            }

            $rows = json_decode(Storage::disk('local')->get($path), true);

            if (! is_array($rows)) {
                throw new \RuntimeException('Data import tidak valid.');
            }

            $totalChunks = $this->totalChunks ?? (int) ceil(count($rows) / self::BATCH_SIZE);
            $batchRows = array_slice($rows, $this->chunk * self::BATCH_SIZE, self::BATCH_SIZE);
            $created = 0;
            $updated = 0;
            $failed  = 0;
            $errorSamples = [];

            foreach ($batchRows as $row) {
                $result = ($config['parse'])($row);

                if (! empty($result['errors'])) {
                    $failed++;
                    if (count($errorSamples) < 20) {
                        $errorSamples[] = [
                            'row'    => $row,
                            'errors' => $result['errors'],
                        ];
                    }
                    continue;
                }

                if ($resolve !== null) {
                    $result = $resolve($result);
                }

                try {
                    $model = $modelClass::updateOrCreate($result['unique'], $result['data']);

                    if ($model->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $updated++;
                    }

                    if ($sync !== null) {
                        $sync($model, $result['relations']);
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    if (count($errorSamples) < 20) {
                        $errorSamples[] = [
                            'row'    => $row,
                            'errors' => [$e->getMessage()],
                        ];
                    }
                }
            }

            $status = $this->updateStatus([
                'processed' => count($batchRows),
                'created'   => $created,
                'updated'   => $updated,
                'failed'    => $failed,
                'errors'    => $errorSamples,
            ]);

            if ($this->chunk + 1 < $totalChunks) {
                self::dispatch($this->entity, $this->token, $this->chunk + 1, $totalChunks);
                return;
            }

            Storage::disk('local')->delete($path);
            foreach (Storage::disk('local')->files('imports') as $file) {
                if (str_starts_with(basename($file), $this->token . '.')) {
                    Storage::disk('local')->delete($file);
                }
            }

            $this->setStatus([
                'status'  => 'completed',
                'created' => $status['created'],
                'updated' => $status['updated'],
                'failed'  => $status['failed'],
                'errors'  => $status['errors'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Import master gagal', [
                'entity' => $this->entity,
                'token'  => $this->token,
                'error'  => $e->getMessage(),
            ]);

            $this->setStatus([
                'status'  => 'failed',
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function updateStatus(array $delta): array
    {
        return Cache::lock(self::cacheKey($this->token) . ':lock', 10)->block(5, function () use ($delta): array {
            $status = Cache::get(self::cacheKey($this->token), []);
            $status['status'] = $delta['status'] ?? $status['status'] ?? 'processing';
            $status['processed'] = ($status['processed'] ?? 0) + ($delta['processed'] ?? 0);
            $status['created'] = ($status['created'] ?? 0) + ($delta['created'] ?? 0);
            $status['updated'] = ($status['updated'] ?? 0) + ($delta['updated'] ?? 0);
            $status['failed'] = ($status['failed'] ?? 0) + ($delta['failed'] ?? 0);
            $status['errors'] = array_slice(array_merge($status['errors'] ?? [], $delta['errors'] ?? []), 0, 20);
            Cache::put(self::cacheKey($this->token), $status, now()->addHours(2));

            return $status;
        });
    }

    private function setStatus(array $data): void
    {
        Cache::put(self::cacheKey($this->token), array_merge(Cache::get(self::cacheKey($this->token), []), $data), now()->addHours(2));
    }
}

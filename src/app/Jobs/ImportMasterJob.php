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

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        public string $entity,
        public string $token,
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

        $path = "imports/{$this->token}.rows.json";

        try {
            $this->setStatus(['status' => 'processing']);

            if (! Storage::disk('local')->exists($path)) {
                throw new \RuntimeException('File import tidak ditemukan.');
            }

            $rows = json_decode(Storage::disk('local')->get($path), true);

            if (! is_array($rows)) {
                throw new \RuntimeException('Data import tidak valid.');
            }

            $created = 0;
            $updated = 0;
            $failed  = 0;
            $errorSamples = [];

            foreach ($rows as $row) {
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

            Storage::disk('local')->delete($path);

            $this->setStatus([
                'status'  => 'completed',
                'created' => $created,
                'updated' => $updated,
                'failed'  => $failed,
                'errors'  => $errorSamples,
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

    private function setStatus(array $data): void
    {
        Cache::put(self::cacheKey($this->token), $data, now()->addHours(2));
    }
}

<?php

namespace App\Logging;

use Closure;
use Spatie\Activitylog\Contracts\LoggablePipe;
use Spatie\Activitylog\EventLogBag;

/**
 * Masking nilai kolom sensitif sebelum disimpan ke tabel `activity_log`.
 *
 * Bekerja pada bag perubahan (attributes & old) yang sudah disusun Spatie,
 * lalu mengganti nilai kolom yang tercantum pada
 * `LogsDataChanges::getActivitylogMaskedColumns()` dengan masker.
 *
 * Strategi masking:
 *  - 'full'  : seluruh karakter diganti `*` (alamat, ttl, dll).
 *  - 'last4' : semua karakter diganti `*`, hanya 4 karakter terakhir
 *              yang dipertahankan supaya tetap bisa dikenali (nik, no_hp).
 */
class MaskSensitiveDataPipe implements LoggablePipe
{
    public function handle(EventLogBag $event, Closure $next): EventLogBag
    {
        $model = $event->model;

        if (! method_exists($model, 'getActivitylogMaskedColumns')) {
            return $next($event);
        }

        $masked = $model->getActivitylogMaskedColumns();

        foreach (['attributes', 'old'] as $part) {
            if (empty($event->changes[$part])) {
                continue;
            }

            foreach ($event->changes[$part] as $column => $value) {
                if (! array_key_exists($column, $masked)) {
                    continue;
                }

                $event->changes[$part][$column] = $this->mask($value, $masked[$column]);
            }
        }

        return $next($event);
    }

    protected function mask(mixed $value, string $strategy): mixed
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return $value;
        }

        $value = (string) $value;

        if ($strategy === 'last4' && mb_strlen($value) > 4) {
            return str_repeat('*', mb_strlen($value) - 4).mb_substr($value, -4);
        }

        return str_repeat('*', mb_strlen($value));
    }
}

<?php

namespace App\Models\Concerns;

use App\Logging\MaskSensitiveDataPipe;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Tambahkan trail mening ketika sebuah record berubah (create/update/delete).
 *
 * Merekam "before & after" tiap kolom yang berubah ke tabel `activity_log`:
 *  - kolom apa saja yang diawasi diambil dari $fillable model (kecuali yang
 *    dikecualikan lewat $activitylogIgnoredColumns).
 *  - causer otomatis user yang sedang login (Spatie); kalau tidak ada user
 *    (mis. dari queue/cron), causer dibiarkan null.
 */
trait LogsDataChanges
{
    use LogsActivity;

    /**
     * Kolom yang TIDAK diawasi — kolom internal sistem/sensitif secara default.
     *
     * @var list<string>
     */
    protected array $activitylogIgnoredColumns = [
        'password',
        'remember_token',
        'api_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Kolom sensitif yang nilai aslinya di-masking sebelum masuk log.
     *
     * Format: 'nama_kolom' => strategi masking ('full' | 'last4').
     * Kolom yang identik untuk semua model hanya perlu diisi di sini; model
     * bisa menambahkan kolom spesifik lewat properti $activitylogMaskedColumns.
     *
     * @var array<string, string>
     */
    protected array $activitylogGlobalMaskedColumns = [
        'password' => 'full',
        'remember_token' => 'full',
        'api_token' => 'full',
        'nik' => 'last4',
        'nip' => 'last4',
        'no_hp' => 'last4',
        'no_wa' => 'last4',
        'telepon' => 'last4',
        'email' => 'last4',
        'ttl' => 'full',
        'alamat' => 'full',
        'no_bpjs' => 'last4',
        'tanggal_lahir' => 'last4',
        'no_rekam_medis' => 'last4',
    ];

    /**
     * Tambahan kolom sensitif spesifik per model.
     *
     * @var array<string, string>
     */
    protected array $activitylogMaskedColumns = [];

    protected static function bootLogsDataChanges(): void
    {
        static::$changesPipes[] = app(MaskSensitiveDataPipe::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        $kolom = array_values(array_diff($this->getFillable(), $this->activitylogIgnoredColumns));

        return LogOptions::defaults()
            ->logOnly($kolom)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('model')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'dibuat',
                'updated' => 'diubah',
                'deleted' => 'dihapus',
                'restored' => 'dipulihkan',
                default => $eventName,
            });
    }

    /**
     * Daftar kolom yang harus di-masking beserta strateginya.
     */
    public function getActivitylogMaskedColumns(): array
    {
        return array_merge($this->activitylogGlobalMaskedColumns, $this->activitylogMaskedColumns);
    }
}

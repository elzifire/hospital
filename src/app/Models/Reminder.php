<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reminder extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Status penjadwalan: selesai/tidak_datang bisa tercatat otomatis
     * (kunjungan tersambung / lewat tanpa kunjungan) maupun manual
     * (jaga-jaga petugas lupa mencatat kunjungan). "jadwal_ulang" adalah
     * status terminasi sebuah jadwal yang diganti jadwal baru (reschedule)
     * — otomatis tidak dipakai sebagai bahan pesan/saran follow up.
     */
    public const STATUS = ['terjadwal', 'selesai', 'tidak_datang', 'dibatalkan', 'jadwal_ulang'];

    protected $fillable = [
        'pnpp_id',
        'poli_id',
        'dokter_id',
        'message_template_id',
        'tanggal',
        'jam',
        'home_visit',
        'status',
        'created_by',
        'catatan',
        'vars_kustom',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jam' => 'datetime:H:i',
            'home_visit' => 'boolean',
            'vars_kustom' => 'array',
        ];
    }

    public function pnpp(): BelongsTo
    {
        return $this->belongsTo(Pnpp::class);
    }

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class);
    }

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class);
    }

    /**
     * Template yang dipilih langsung di form penjadwalan (opsional) —
     * menggantikan template default rule saat generate pesan.
     */
    public function messageTemplate(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'message_template_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Realisasi kunjungan dari penjadwalan ini (maksimal satu).
     */
    public function kunjungan(): HasOne
    {
        return $this->hasOne(Kunjungan::class);
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(MessageLog::class);
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return blank($status) ? $query : $query->where('status', $status);
    }

    /**
     * Jadwal tepat N hari dari sekarang (rule H-N: H-7, H-1).
     */
    public function scopeDueIn(Builder $query, int $hari): Builder
    {
        return $query->whereDate('tanggal', today()->addDays($hari)->toDateString());
    }

    /**
     * Jadwal hari ini (rule hari-H).
     */
    public function scopeHariIni(Builder $query): Builder
    {
        return $query->whereDate('tanggal', today()->toDateString());
    }

    /**
     * Terjadwal namun tanggalnya sudah lewat tanpa kunjungan —
     * kandidat "tidak datang" (BroadcastService::sweepStatus).
     */
    public function scopeTerlambatTanpaKunjungan(Builder $query): Builder
    {
        return $query->where('status', 'terjadwal')
            ->whereDate('tanggal', '<', today()->toDateString())
            ->whereDoesntHave('kunjungan');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RegisterPnpp extends Model
{
    use HasFactory;

    protected $table = 'register_pnpp';

    public const STATUS_BELUM_DISETUJUI = 'belum disetujui';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS = [
        self::STATUS_BELUM_DISETUJUI,
        self::STATUS_DISETUJUI,
    ];

    public const STATUS_LABEL = [
        self::STATUS_BELUM_DISETUJUI => 'Belum Disetujui',
        self::STATUS_DISETUJUI => 'Disetujui',
    ];

    protected $fillable = [
        'nama',
        'nik',
        'nip',
        'jabatan',
        'satker_id',
        'unit',
        'ttl',
        'alamat',
        'no_hp',
        'rencana_tanggal_kunjungan',
        'rencana_jam_kunjungan',
        'tujuan_lainnya',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'rencana_tanggal_kunjungan' => 'date',
        ];
    }

    public function satker(): BelongsTo
    {
        return $this->belongsTo(Satker::class);
    }

    public function polis(): BelongsToMany
    {
        return $this->belongsToMany(Poli::class, 'register_pnpp_poli')
            ->withPivot('approved_at', 'approved_by')
            ->orderBy('polis.nama');
    }

    /**
     * Poli tujuan yang sudah disetujui (approved_at terisi).
     */
    public function approvedPolis(): BelongsToMany
    {
        return $this->belongsToMany(Poli::class, 'register_pnpp_poli')
            ->withPivot('approved_at', 'approved_by')
            ->wherePivotNotNull('approved_at')
            ->orderBy('polis.nama');
    }

    public function tujuanKunjungans(): BelongsToMany
    {
        return $this->belongsToMany(TujuanKunjungan::class, 'register_pnpp_tujuan_kunjungan');
    }
}

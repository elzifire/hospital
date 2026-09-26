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
        'satker_lainnya',
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

    /**
     * Benar bila asal pendaftar dipetakan ke satker cadangan "Satker Lainnya"
     * (nama yang diketik manual tidak ada di data master).
     */
    public function isSatkerLainnya(): bool
    {
        return $this->satker !== null && $this->satker->isLainnya();
    }

    /**
     * Nama satker siap tampil: "Satker Lainnya" bila dipetakan ke satker
     * cadangan — beserta nama asli bila sempat diketik oleh pendaftar.
     */
    public function satkerNamaTampil(): string
    {
        if ($this->isSatkerLainnya()) {
            return filled($this->satker_lainnya)
                ? 'Satker Lainnya · '.$this->satker_lainnya
                : Satker::NAMA_LAINNYA;
        }

        return $this->satker?->nama ?? '—';
    }

    /**
     * Nama asli yang diketik pendaftar saat satker tidak ada di master.
     */
    public function satkerNamaAsli(): ?string
    {
        return $this->isSatkerLainnya() && filled($this->satker_lainnya)
            ? $this->satker_lainnya
            : null;
    }
}

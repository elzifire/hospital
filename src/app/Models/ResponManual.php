<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Balasan pasien yang dicatat manual atau diimpor dari Excel/CSV
 * (sumber: 'manual' | 'import'). Terpisah dari MessageReply (balasan
 * otomatis via webhook) — lihat tab "Data Respon" di modul Respon.
 */
class ResponManual extends Model
{
    use HasFactory;

    public const SUMBER_MANUAL = 'manual';

    public const SUMBER_IMPORT = 'import';

    protected $fillable = [
        'nama',
        'nrp_nip',
        'no_hp',
        'satker',
        'isi',
        'waktu',
        'sumber',
    ];

    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
        ];
    }
}

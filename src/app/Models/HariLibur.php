<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    use HasFactory;

    public const SUMBER_MANUAL = 'manual';
    public const SUMBER_GOOGLE_CALENDAR = 'google_calendar';

    /** Asal data hari libur (flagging untuk sinkronasi). */
    public const SUMBER = [self::SUMBER_MANUAL, self::SUMBER_GOOGLE_CALENDAR];

    protected $fillable = [
        'tanggal',
        'nama',
        'poli_id',
        'sumber',
        'event_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function poli()
    {
        return $this->belongsTo(Poli::class);
    }
}
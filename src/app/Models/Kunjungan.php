<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kunjungan extends Model
{
    use HasFactory;

    protected $fillable = [
        'pnpp_id',
        'reminder_id',
        'poli_id',
        'tanggal_kunjungan',
        'keluhan',
        'diagnosa',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kunjungan' => 'date',
        ];
    }

    public function pnpp(): BelongsTo
    {
        return $this->belongsTo(Pnpp::class);
    }

    /**
     * Penjadwalan yang direalisasikan kunjungan ini (bila berasal
     * dari Digital Reminder; kunjungan manual nilainya null).
     */
    public function reminder(): BelongsTo
    {
        return $this->belongsTo(Reminder::class);
    }

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class);
    }
}

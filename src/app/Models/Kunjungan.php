<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kunjungan extends Model
{
    use HasFactory;

    protected $fillable = [
        'pnpp_id',
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

    public function pnpp()
    {
        return $this->belongsTo(Pnpp::class);
    }
}

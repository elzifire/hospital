<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dokter extends Model
{
    use HasFactory;

    protected $fillable = [
        'poli_id',
        'nama',
        'spesialisasi',
    ];

    public function poli()
    {
        return $this->belongsTo(Poli::class);
    }

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class);
    }
}

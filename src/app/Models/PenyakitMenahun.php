<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenyakitMenahun extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama',
    ];

    public function pnpps()
    {
        return $this->belongsToMany(
            Pnpp::class,
            'pnpp_penyakit',
            'penyakit_menahun_id',
            'pnpp_id'
        )->withPivot('keterangan');
    }
}
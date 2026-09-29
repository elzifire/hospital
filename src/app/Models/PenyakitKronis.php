<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogsDataChanges;

class PenyakitKronis extends Model
{
    use HasFactory, LogsDataChanges;

    protected $fillable = [
        'kode',
        'nama',
    ];

    public function pnpps()
    {
        return $this->belongsToMany(
            Pnpp::class,
            'pnpp_penyakit',
            'penyakit_kronis_id',
            'pnpp_id'
        )->withPivot('keterangan');
    }
}

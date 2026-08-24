<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Satker extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama',
    ];

    public function pnpps()
    {
        return $this->hasMany(Pnpp::class);
    }
}

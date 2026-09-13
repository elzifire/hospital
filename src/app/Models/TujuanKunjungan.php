<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TujuanKunjungan extends Model
{
    use HasFactory;

    protected $fillable = ['nama'];

    public function registerPnpps(): BelongsToMany
    {
        return $this->belongsToMany(RegisterPnpp::class, 'register_pnpp_tujuan_kunjungan');
    }
}

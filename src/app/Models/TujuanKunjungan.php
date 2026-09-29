<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Concerns\LogsDataChanges;

class TujuanKunjungan extends Model
{
    use HasFactory, LogsDataChanges;

    protected $fillable = ['nama'];

    public function registerPnpps(): BelongsToMany
    {
        return $this->belongsToMany(RegisterPnpp::class, 'register_pnpp_tujuan_kunjungan');
    }
}

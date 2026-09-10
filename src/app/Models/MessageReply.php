<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'pnpp_id',
        'no_hp',
        'nama',
        'isi_pesan',
        'waktu_masuk',
        'driver',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'waktu_masuk' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function pnpp(): BelongsTo
    {
        return $this->belongsTo(Pnpp::class);
    }
}

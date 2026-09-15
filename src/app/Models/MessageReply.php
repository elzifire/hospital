<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'waktu_masuk' => 'datetime',
            'payload' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function pnpp(): BelongsTo
    {
        return $this->belongsTo(Pnpp::class);
    }

    public function scopeBelumDibaca(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Tandai seluruh balasan masuk untuk nomor ini sebagai sudah dibaca
     * (dipanggil saat petugas membuka percakapan).
     */
    public static function tandaiDibaca(string $noHp): void
    {
        static::query()
            ->where('no_hp', $noHp)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}

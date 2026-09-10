<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastRule extends Model
{
    use HasFactory;

    public const JENIS = ['outreach', 'follow_up'];

    /**
     * Aturan kirim: jarak hari dari tanggal jadwal + deteksi tidak datang.
     * outreach: h-7, h-1 · follow_up: h-1, h, tidak_datang.
     */
    public const RULE = ['h-7', 'h-1', 'h', 'tidak_datang'];

    protected $fillable = [
        'jenis',
        'rule',
        'message_template_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'message_template_id');
    }

    public function scopeJenis(Builder $query, string $jenis): Builder
    {
        return $query->where('jenis', $jenis);
    }
}

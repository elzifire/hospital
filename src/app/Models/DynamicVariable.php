<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DynamicVariable extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama',
        'sumber_data',
        'contoh',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function ($item) {
            $kode = trim($item->kode);
            if (! str_starts_with($kode, '{')) {
                $kode = '{' . $kode;
            }
            if (! str_ends_with($kode, '}')) {
                $kode = $kode . '}';
            }
            $item->kode = $kode;
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

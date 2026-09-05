<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TemplateCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'slug',
        'warna',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $base = Str::slug($category->nama);
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = "{$base}-" . $i++;
                }
                $category->slug = $slug;
            }
        });
    }

    public function templates(): HasMany
    {
        return $this->hasMany(MessageTemplate::class, 'template_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

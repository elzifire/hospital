<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MessageTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_category_id',
        'judul',
        'kode',
        'channel',
        'konten',
        'deskripsi',
        'is_active',
        'dipakai_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'dipakai_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($template) {
            if (empty($template->kode)) {
                $template->kode = 'TMP-' . strtoupper(Str::random(6));
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TemplateCategory::class, 'template_category_id');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (blank($search)) {
            return $query;
        }

        $term = '%' . trim($search) . '%';

        return $query->where(function ($q) use ($term) {
            $q->where('judul', 'ILIKE', $term)
                ->orWhere('konten', 'ILIKE', $term)
                ->orWhere('kode', 'ILIKE', $term)
                ->orWhereHas('category', function ($catQuery) use ($term) {
                    $catQuery->where('nama', 'ILIKE', $term);
                });
        });
    }

    public function scopeFilterCategory(Builder $query, $categoryId): Builder
    {
        if (blank($categoryId)) {
            return $query;
        }

        return $query->where('template_category_id', $categoryId);
    }

    public function scopeFilterChannel(Builder $query, ?string $channel): Builder
    {
        if (blank($channel)) {
            return $query;
        }

        return $query->where('channel', $channel);
    }

    public function scopeFilterStatus(Builder $query, $status): Builder
    {
        if ($status === null || $status === '') {
            return $query;
        }

        return $query->where('is_active', filter_var($status, FILTER_VALIDATE_BOOLEAN));
    }
}

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
        'meta_template_id',
        'meta_template_name',
        'meta_language',
        'meta_param_tokens',
        'meta_status',
        'meta_category',
        'meta_components',
        'meta_updated_at',
        'last_synced_at',
        'konten',
        'deskripsi',
        'image_url',
        'is_active',
        'dipakai_count',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'dipakai_count' => 'integer',
            'meta_param_tokens' => 'array',
            'meta_components' => 'array',
            'meta_updated_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($template) {
            if (empty($template->kode)) {
                $template->kode = 'TMP-'.strtoupper(Str::random(6));
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TemplateCategory::class, 'template_category_id');
    }

    /**
     * Urutan token yang menjadi parameter template Meta ({{1}}, {{2}},
     * …): pakai meta_param_tokens bila diisi, selain itu urutan
     * kemunculan pertama token di konten.
     *
     * @return array<int, string>
     */
    public function tokenParam(): array
    {
        if (filled($this->meta_param_tokens)) {
            return array_values((array) $this->meta_param_tokens);
        }

        preg_match_all('/\{([a-z_]+)\}/i', (string) $this->konten, $cocok);

        return array_values(array_unique($cocok[1] ?? []));
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (blank($search)) {
            return $query;
        }

        $term = '%'.trim($search).'%';

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

    /**
     * Filter status persetujuan template Meta (APPROVED, PENDING, dst.).
     */
    public function scopeMetaStatus(Builder $query, ?string $status): Builder
    {
        return blank($status) ? $query : $query->where('meta_status', strtoupper($status));
    }
}

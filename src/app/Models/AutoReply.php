<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'kategori',
        'nama',
        'cara_cocok',
        'pola',
        'isi',
        'aktif',
        'prioritas',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'prioritas' => 'integer',
        ];
    }

    // ── Konstanta ──────────────────────────────────────────────

    public const CARA_COCOK = ['semua', 'sama', 'mulai', 'mengandung', 'akhiri'];

    public const KATEGORI = [
        'umum' => 'Umum',
        'operasional' => 'Operasional',
        'jadwal_dokter' => 'Jadwal Dokter',
        'company_profile' => 'Company Profile',
        'kontak_admin' => 'Kontak Admin',
    ];

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    public function scopeUrut(Builder $query): Builder
    {
        return $query->orderBy('prioritas')->orderBy('id');
    }

    public function scopeByKategori(Builder $query, ?string $kategori): Builder
    {
        if ($kategori === null || $kategori === '') {
            return $query;
        }

        return $query->where('kategori', $kategori);
    }

    // ── Logic ──────────────────────────────────────────────────

    /**
     * Cek apakah pesan masuk cocok dengan aturan ini.
     */
    public function cocok(string $pesan): bool
    {
        $pesan = trim(mb_strtolower($pesan));

        if ($this->cara_cocok === 'semua') {
            return true;
        }

        $pola = trim(mb_strtolower((string) $this->pola));

        if ($pola === '') {
            return false;
        }

        return match ($this->cara_cocok) {
            'sama' => $pesan === $pola,
            'mulai' => str_starts_with($pesan, $pola),
            'akhiri' => str_ends_with($pesan, $pola),
            default => str_contains($pesan, $pola),
        };
    }

    // ── Helper ─────────────────────────────────────────────────

    public static function kategoriOptions(): array
    {
        return self::KATEGORI;
    }

    public static function caraCocokLabels(): array
    {
        return [
            'semua' => 'Semua Pesan',
            'sama' => 'Sama Persis',
            'mulai' => 'Diawali',
            'mengandung' => 'Mengandung',
            'akhiri' => 'Diakhiri',
        ];
    }
}

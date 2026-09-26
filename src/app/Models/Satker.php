<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Satker extends Model
{
    use HasFactory;

    /**
     * Satker cadangan untuk asal yang di luar data master (ketikan manual
     * pada form pendaftaran yang tidak cocok dengan satker terdaftar).
     */
    public const KODE_LAINNYA = 'LAINNYA';

    public const NAMA_LAINNYA = 'Satker Lainnya';

    protected $fillable = [
        'kode',
        'nama',
    ];

    public function pnpps()
    {
        return $this->hasMany(Pnpp::class);
    }

    /**
     * Ambil (atau buat bila belum ada) satker cadangan "Satker Lainnya".
     */
    public static function lainnya(): self
    {
        return static::firstOrCreate(
            ['kode' => static::KODE_LAINNYA],
            ['nama' => static::NAMA_LAINNYA],
        );
    }

    /**
     * Benar bila ini satker cadangan "Satker Lainnya".
     */
    public function isLainnya(): bool
    {
        return $this->kode !== null
            && mb_strtolower($this->kode) === mb_strtolower(static::KODE_LAINNYA);
    }
}

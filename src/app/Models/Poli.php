<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poli extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama',
        'jam_buka',
        'jam_tutup',
    ];

    protected function casts(): array
    {
        return [
            'jam_buka' => 'datetime:H:i',
            'jam_tutup' => 'datetime:H:i',
        ];
    }

    /**
     * Poli dianggap buka 24 jam selama jam buka & tutup tidak diisi.
     */
    public function buka24Jam(): bool
    {
        return $this->jam_buka === null && $this->jam_tutup === null;
    }

    /**
     * Label jam layanan, mis. "08:00–12:00" atau "24 Jam".
     */
    public function jamLayanan(): string
    {
        if ($this->buka24Jam()) {
            return '24 Jam';
        }

        $buka = $this->jam_buka?->format('H:i') ?? '00:00';
        $tutup = $this->jam_tutup?->format('H:i') ?? '23:59';

        return "{$buka}–{$tutup}";
    }

    public function dokters()
    {
        return $this->hasMany(Dokter::class);
    }

    public function userDetails()
    {
        return $this->hasMany(UserDetail::class);
    }
}

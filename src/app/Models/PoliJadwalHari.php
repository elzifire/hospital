<?php

namespace App\Models;

use App\Models\Concerns\LogsDataChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Jadwal layanan poli per hari — tiap baris = satu hari dalam seminggu
 * (Senin–Minggu). Ini menimpa pola lama di tabel polis (jam_buka/jam_tutup
 * plus kolom hari_*): selama sebuah poli punya baris di tabel ini, baris
 * inilah yang dipakai untuk menilai hari buka & jam layanan.
 *
 * Bila jam_buka/jam_tutup dikosongkan di suatu hari, poli dianggap buka
 * 24 jam pada hari itu. `buka = false` artinya poli tutup pada hari tsb.
 */
class PoliJadwalHari extends Model
{
    use HasFactory, LogsDataChanges;

    protected $table = 'poli_jadwal_hari';

    protected $fillable = [
        'poli_id',
        'hari',
        'buka',
        'jam_buka',
        'jam_tutup',
    ];

    protected function casts(): array
    {
        return [
            'buka' => 'boolean',
            'jam_buka' => 'datetime:H:i',
            'jam_tutup' => 'datetime:H:i',
        ];
    }

    public function poli()
    {
        return $this->belongsTo(Poli::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poli extends Model
{
    use HasFactory;

    /** Nama hari dalam urutan seminggu (untuk tampilan & form). */
    public const DAFTAR_HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    /** Map nama hari -> kolom boolean di tabel polis. */
    public const KOLOM_HARI = [
        'Senin' => 'hari_senin',
        'Selasa' => 'hari_selasa',
        'Rabu' => 'hari_rabu',
        'Kamis' => 'hari_kamis',
        'Jumat' => 'hari_jumat',
        'Sabtu' => 'hari_sabtu',
        'Minggu' => 'hari_minggu',
    ];

    protected $fillable = [
        'kode',
        'nama',
        'jam_buka',
        'jam_tutup',
        ...self::KOLOM_HARI,
    ];

    protected function casts(): array
    {
        return [
            'jam_buka' => 'datetime:H:i',
            'jam_tutup' => 'datetime:H:i',
            ...array_fill_keys(self::KOLOM_HARI, 'boolean'),
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

    public function hariLiburs()
    {
        return $this->hasMany(HariLibur::class);
    }

    /**
     * Daftar nama hari tempat poli buka (yang diceklis aktif).
     *
     * @return list<string>
     */
    public function hariTercentang(): array
    {
        $tercentang = [];

        foreach (self::KOLOM_HARI as $nama => $kolom) {
            if ($this->{$kolom}) {
                $tercentang[] = $nama;
            }
        }

        return $tercentang;
    }

    /**
     * Poli dianggap buka setiap hari bila tidak ada hari yang diceklis.
     */
    public function bukaSetiapHari(): bool
    {
        return $this->hariTercentang() === [];
    }

    /**
     * Label hari layanan, mis. "Senin, Rabu, Jumat" atau "Setiap Hari".
     */
    public function hariLayanan(): string
    {
        if ($this->bukaSetiapHari()) {
            return 'Setiap Hari';
        }

        return implode(', ', $this->hariTercentang());
    }

    /**
     * Jadwal utuh poli, mis. "Senin, Rabu, Jumat · 09:00–17:00" atau
     * "Setiap Hari · 24 Jam".
     */
    public function jadwalRingkas(): string
    {
        return "{$this->hariLayanan()} · {$this->jamLayanan()}";
    }

    /**
     * Apakah poli buka pada tanggal yang diberikan.
     */
    public function hariBuka(\Carbon\Carbon $tanggal): bool
    {
        if ($this->bukaSetiapHari()) {
            return true;
        }

        $nama = ['1' => 'Senin', '2' => 'Selasa', '3' => 'Rabu', '4' => 'Kamis', '5' => 'Jumat', '6' => 'Sabtu', '7' => 'Minggu'][(string) $tanggal->isoWeekday()] ?? 'Senin';

        return (bool) $this->{self::KOLOM_HARI[$nama]};
    }
}

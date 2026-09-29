<?php

namespace App\Models;

use App\Models\Concerns\LogsDataChanges;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poli extends Model
{
    use HasFactory, LogsDataChanges;

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
     * Nama hari Indonesia sebuah tanggal (1 = Senin ... 7 = Minggu).
     */
    public static function namaHari(Carbon $tanggal): string
    {
        $daftar = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        return $daftar[$tanggal->isoWeekday() - 1];
    }

    /**
     * Jadwal per hari (baris tabel poli_jadwal_hari).
     */
    public function jadwalHari()
    {
        return $this->hasMany(PoliJadwalHari::class);
    }

    /**
     * Apakah poli memakai jadwal per hari (tabel poli_jadwal_hari) —
     * bila ya, baris tsb menimpa pola lama jam_buka/jam_tutup + hari_*.
     */
    public function hasJadwalPerHari(): bool
    {
        if ($this->relationLoaded('jadwalHari')) {
            return $this->jadwalHari->isNotEmpty();
        }

        return $this->jadwalHari()->exists();
    }

    /**
     * Jadwal efektif poli untuk 7 hari dalam seminggu, dengan urutan tetap
     * Senin–Minggu. Kalau poli memakai jadwal per hari, baris tabel
     * poli_jadwal_hari jadi sumbernya; kalau tidak, data lama
     * (jam_buka/jam_tutup + hari_*) diturunkan ke bentuk yang sama.
     *
     * Bentuk tiap entri:
     *   ['buka' => bool, 'jam_buka' => 'H:i'|null, 'jam_tutup' => 'H:i'|null]
     * buka=true tapi jam kosong = poli buka 24 jam pada hari itu.
     *
     * @return array<string, array{buka: bool, jam_buka: ?string, jam_tutup: ?string}>
     */
    public function jadwalPerHari(): array
    {
        if ($this->hasJadwalPerHari()) {
            if (! $this->relationLoaded('jadwalHari')) {
                $this->load('jadwalHari');
            }

            $rows = $this->jadwalHari->keyBy('hari');

            $hasil = [];
            foreach (self::DAFTAR_HARI as $hari) {
                $row = $rows->get($hari);

                $hasil[$hari] = $row !== null ? [
                    'buka' => (bool) $row->buka,
                    'jam_buka' => $row->jam_buka?->format('H:i'),
                    'jam_tutup' => $row->jam_tutup?->format('H:i'),
                ] : ['buka' => false, 'jam_buka' => null, 'jam_tutup' => null];
            }

            return $hasil;
        }

        // ---- Fallback: pola lama. Hari tanpa diceklis = buka setiap hari. ----
        $buka = $this->jam_buka?->format('H:i');
        $tutup = $this->jam_tutup?->format('H:i');
        $tercentang = [];

        foreach (self::KOLOM_HARI as $nama => $kolom) {
            if ($this->{$kolom}) {
                $tercentang[] = $nama;
            }
        }

        $bukaSetiapHari = $tercentang === [];

        $hasil = [];
        foreach (self::DAFTAR_HARI as $hari) {
            $hasil[$hari] = [
                'buka' => $bukaSetiapHari || in_array($hari, $tercentang, true),
                'jam_buka' => $buka,
                'jam_tutup' => $tutup,
            ];
        }

        return $hasil;
    }

    /**
     * Jadwal efektif satu hari tertentu (lewati null jika hari tidak dikenal).
     *
     * @return array{buka: bool, jam_buka: ?string, jam_tutup: ?string}
     */
    public function jadwalPada(Carbon $tanggal): array
    {
        return $this->jadwalPerHari()[self::namaHari($tanggal)] ?? ['buka' => false, 'jam_buka' => null, 'jam_tutup' => null];
    }

    /** Relasi dokter. */
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
     * Daftar nama hari tempat poli buka berdasarkan jadwal efektif.
     *
     * @return list<string>
     */
    public function hariTercentang(): array
    {
        $jadwal = $this->jadwalPerHari();
        $tercentang = [];

        foreach (self::DAFTAR_HARI as $hari) {
            if ($jadwal[$hari]['buka']) {
                $tercentang[] = $hari;
            }
        }

        return $tercentang;
    }

    /**
     * Poli dianggap buka setiap hari bila ketujuh hari terbuka.
     */
    public function bukaSetiapHari(): bool
    {
        return count($this->hariTercentang()) === count(self::DAFTAR_HARI);
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
     * Apakah poli buka 24 jam (tidak ada jam buka & tutup sama sekali) —
     * hanya bermakna pada pola lama; pada jadwal per hari, 24 jam berarti
     * hari tsb tidak mengisi jam buka/tutup.
     */
    public function buka24Jam(): bool
    {
        return $this->jam_buka === null && $this->jam_tutup === null;
    }

    /**
     * Label jam layanan pola lama, mis. "08:00–12:00" atau "24 Jam".
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

    /**
     * Jadwal utuh poli.
     *  - pola lama  : "Senin, Rabu, Jumat · 09:00–17:00" / "Setiap Hari · 24 Jam".
     *  - jadwal per hari : ringkasan per hari yang lebih informatif,
     *    mis. "Sen–Jum 08:00–12:00 · Sab 09:00–13:00 · Minggu Tutup".
     */
    public function jadwalRingkas(): string
    {
        if ($this->hasJadwalPerHari()) {
            return $this->jadwalPerHariRingkas();
        }

        return "{$this->hariLayanan()} · {$this->jamLayanan()}";
    }

    /**
     * Ringkasan jadwal per hari dengan pengelompokan hari yang bersebelahan
     * dan berjam sama, mis. "Sen–Jum 08:00–12:00 · Sab–Ming 09:00–13:00".
     */
    public function jadwalPerHariRingkas(): string
    {
        $pendek = ['Senin' => 'Sen', 'Selasa' => 'Sel', 'Rabu' => 'Rab', 'Kamis' => 'Kam', 'Jumat' => 'Jum', 'Sabtu' => 'Sab', 'Minggu' => 'Min'];
        $jadwal = $this->jadwalPerHari();

        $segmen = [];
        $terakhir = null;

        foreach (self::DAFTAR_HARI as $hari) {
            $h = $jadwal[$hari];
            $label = $h['buka']
                ? (($h['jam_buka'] && $h['jam_tutup']) ? "{$h['jam_buka']}–{$h['jam_tutup']}" : '24 Jam')
                : 'Tutup';

            if ($terakhir !== null && $terakhir['label'] === $label) {
                $terakhir['hari'][] = $hari;
            } else {
                if ($terakhir !== null) {
                    $segmen[] = $terakhir;
                }
                $terakhir = ['hari' => [$hari], 'label' => $label];
            }
        }

        if ($terakhir !== null) {
            $segmen[] = $terakhir;
        }

        $bagian = [];
        foreach ($segmen as $s) {
            $awal = $pendek[$s['hari'][0]] ?? $s['hari'][0];

            if (count($s['hari']) === 1) {
                $nama = $s['label'] === 'Tutup' ? "{$awal} Tutup" : "{$awal} {$s['label']}";
            } else {
                $akhir = $pendek[$s['hari'][count($s['hari']) - 1]] ?? $s['hari'][count($s['hari']) - 1];
                $nama = $s['label'] === 'Tutup' ? "{$awal}–{$akhir} Tutup" : "{$awal}–{$akhir} {$s['label']}";
            }

            $bagian[] = $nama;
        }

        return implode(' · ', $bagian);
    }

    /**
     * Apakah poli buka pada tanggal yang diberikan (berdasarkan jadwal efektif).
     */
    public function hariBuka(Carbon $tanggal): bool
    {
        return (bool) $this->jadwalPada($tanggal)['buka'];
    }
}

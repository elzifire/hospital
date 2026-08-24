<?php

namespace App\Support;

use App\Models\Dokter;
use App\Models\Jadwal;
use App\Models\PenyakitKronis;
use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\Satker;
use DateTimeImmutable;

/**
 * Registri konfigurasi import/export untuk semua data master.
 *
 * Setiap entitas mendefinisikan:
 *  - label        : nama tampilan
 *  - model        : class model Eloquent
 *  - eager        : relasi yang dimuat saat export
 *  - headers      : nama kolom CSV (harus sama urutannya dengan sample/toRow)
 *  - sample       : 1 baris contoh untuk template
 *  - toRow        : callback (Model) => array baris untuk export
 *  - parse        : callback (array $row) => ['data','unique','relations','errors']
 *  - sync         : callback opsional (Model, relations) untuk relasi many-to-many
 */
class MasterRegistry
{
    public static function entities(): array
    {
        return ['pnpp', 'satker', 'penyakit', 'poli', 'dokter', 'jadwal'];
    }

    public static function has(string $entity): bool
    {
        return array_key_exists($entity, self::configs());
    }

    public static function label(string $entity): string
    {
        return self::configs()[$entity]['label'];
    }

    public static function config(string $entity): array
    {
        return self::configs()[$entity];
    }

    /**
     * Tipe input per kolom untuk pratinjau (edit langsung).
     *  - text        : input teks bebas
     *  - select      : dropdown pilihan tunggal
     *  - multiselect : pilihan ganda (mirip select2, nilai dipisah koma)
     */
    public static function fields(string $entity): array
    {
        $select = fn (array $options) => ['type' => 'select', 'options' => array_values($options)];
        $multi  = fn (array $options) => ['type' => 'multiselect', 'options' => array_values($options)];
        $text   = ['type' => 'text'];

        return match ($entity) {
            'pnpp' => [
                'Nama'            => $text,
                'NIP'             => $text,
                'No. BPJS'        => $text,
                'Satker'          => $select(Satker::orderBy('nama')->pluck('nama')->all()),
                'No. HP'          => $text,
                'Tanggal Lahir'   => $text,
                'Jenis Kelamin'   => $select(['L', 'P']),
                'Penyakit Kronis' => $multi(PenyakitKronis::orderBy('nama')->pluck('nama')->all()),
            ],
            'dokter' => [
                'Nama'         => $text,
                'Poli'         => $select(Poli::orderBy('nama')->pluck('nama')->all()),
                'Spesialisasi' => $text,
            ],
            'jadwal' => [
                'Poli'        => $select(Poli::orderBy('nama')->pluck('nama')->all()),
                'Dokter'      => $select(Dokter::orderBy('nama')->pluck('nama')->all()),
                'Hari'        => $select(Jadwal::HARI),
                'Jam Mulai'   => $text,
                'Jam Selesai' => $text,
            ],
            default => collect(self::config($entity)['headers'])
                ->mapWithKeys(fn ($h) => [$h => $text])
                ->all(),
        };
    }

    public static function configs(): array
    {
        return [
            'satker' => [
                'label'   => 'Satker',
                'model'   => Satker::class,
                'eager'   => [],
                'headers' => ['Kode', 'Nama'],
                'sample'  => ['DINKES', 'Dinas Kesehatan'],
                'toRow'   => fn (Satker $m) => [$m->kode ?? '', $m->nama],
                'parse'   => fn (array $r) => self::parseKodeNama($r),
            ],

            'penyakit' => [
                'label'   => 'Penyakit Kronis',
                'model'   => PenyakitKronis::class,
                'eager'   => [],
                'headers' => ['Kode', 'Nama'],
                'sample'  => ['HTN', 'Hipertensi'],
                'toRow'   => fn (PenyakitKronis $m) => [$m->kode ?? '', $m->nama],
                'parse'   => fn (array $r) => self::parseKodeNama($r),
            ],

            'poli' => [
                'label'   => 'Poli',
                'model'   => Poli::class,
                'eager'   => [],
                'headers' => ['Kode', 'Nama'],
                'sample'  => ['GIGI', 'Poli Gigi'],
                'toRow'   => fn (Poli $m) => [$m->kode ?? '', $m->nama],
                'parse'   => fn (array $r) => self::parseKodeNama($r),
            ],

            'dokter' => [
                'label'   => 'Dokter',
                'model'   => Dokter::class,
                'eager'   => ['poli'],
                'headers' => ['Nama', 'Poli', 'Spesialisasi'],
                'sample'  => ['dr. Rina Pratiwi', 'Poli Umum', 'Dokter Umum'],
                'toRow'   => fn (Dokter $m) => [$m->nama, $m->poli?->nama ?? '', $m->spesialisasi ?? ''],
                'parse'   => fn (array $r) => self::parseDokter($r),
            ],

            'jadwal' => [
                'label'   => 'Jadwal',
                'model'   => Jadwal::class,
                'eager'   => ['dokter.poli'],
                'headers' => ['Poli', 'Dokter', 'Hari', 'Jam Mulai', 'Jam Selesai'],
                'sample'  => ['Poli Umum', 'dr. Rina Pratiwi', 'Senin', '08:00', '12:00'],
                'toRow'   => fn (Jadwal $m) => [
                    $m->dokter?->poli?->nama ?? '',
                    $m->dokter?->nama ?? '',
                    $m->hari,
                    $m->jam_mulai->format('H:i'),
                    $m->jam_selesai->format('H:i'),
                ],
                'parse'   => fn (array $r) => self::parseJadwal($r),
            ],

            'pnpp' => [
                'label'   => 'PNPP',
                'model'   => Pnpp::class,
                'eager'   => ['satker', 'penyakit'],
                'headers' => ['Nama', 'NIP', 'No. BPJS', 'Satker', 'No. HP', 'Tanggal Lahir', 'Jenis Kelamin', 'Penyakit Kronis'],
                'sample'  => ['Budi Santoso', '198501012010011001', '0001234567890', 'Dinas Kesehatan', '081234567890', '1985-01-01', 'L', 'Hipertensi, Diabetes Melitus'],
                'toRow'   => fn (Pnpp $m) => [
                    $m->nama,
                    $m->nip ?? '',
                    $m->no_bpjs ?? '',
                    $m->satker?->nama ?? '',
                    $m->no_hp ?? '',
                    $m->tanggal_lahir?->format('Y-m-d') ?? '',
                    $m->jenis_kelamin,
                    $m->penyakit->pluck('nama')->implode(', '),
                ],
                'parse'   => fn (array $r) => self::parsePnpp($r),
                'sync'    => fn (Pnpp $model, array $relations) => $model->penyakit()->sync($relations['penyakit'] ?? []),
            ],
        ];
    }

    // ------------------------------------------------------------------
    // Parser per entitas
    // ------------------------------------------------------------------

    private static function parseKodeNama(array $row): array
    {
        $kode  = self::field($row, 'Kode');
        $nama  = self::field($row, 'Nama');
        $errors = [];

        if ($nama === '') {
            $errors[] = 'Nama wajib diisi';
        }

        return [
            'data'      => ['kode' => $kode !== '' ? $kode : null, 'nama' => $nama],
            'unique'    => $kode !== '' ? ['kode' => $kode] : ['nama' => $nama],
            'relations' => [],
            'errors'    => $errors,
        ];
    }

    private static function parseDokter(array $row): array
    {
        $nama      = self::field($row, 'Nama');
        $poliName  = self::field($row, 'Poli');
        $spesialis = self::field($row, 'Spesialisasi');
        $errors    = [];

        $poli = null;

        if ($nama === '') {
            $errors[] = 'Nama wajib diisi';
        }

        if ($poliName === '') {
            $errors[] = 'Poli wajib diisi';
        } else {
            $poli = Poli::where('nama', $poliName)->first();
            if (! $poli) {
                $errors[] = "Poli \"{$poliName}\" tidak ditemukan";
            }
        }

        return [
            'data'      => [
                'poli_id'      => $poli?->id,
                'nama'         => $nama,
                'spesialisasi' => $spesialis !== '' ? $spesialis : null,
            ],
            'unique'    => ['nama' => $nama],
            'relations' => [],
            'errors'    => $errors,
        ];
    }

    private static function parseJadwal(array $row): array
    {
        $dokterName = self::field($row, 'Dokter');
        $hari       = self::normalizeHari(self::field($row, 'Hari'));
        $jamMulai   = self::normalizeTime(self::field($row, 'Jam Mulai'));
        $jamSelesai = self::normalizeTime(self::field($row, 'Jam Selesai'));
        $errors     = [];

        $dokter = null;

        if ($dokterName === '') {
            $errors[] = 'Dokter wajib diisi';
        } else {
            $dokter = Dokter::where('nama', $dokterName)->first();
            if (! $dokter) {
                $errors[] = "Dokter \"{$dokterName}\" tidak ditemukan";
            }
        }

        if ($hari === null) {
            $errors[] = 'Hari tidak valid (Senin–Minggu)';
        }
        if ($jamMulai === null) {
            $errors[] = 'Jam Mulai tidak valid (format HH:mm)';
        }
        if ($jamSelesai === null) {
            $errors[] = 'Jam Selesai tidak valid (format HH:mm)';
        }
        if ($jamMulai !== null && $jamSelesai !== null && $jamSelesai <= $jamMulai) {
            $errors[] = 'Jam Selesai harus lebih besar dari Jam Mulai';
        }

        return [
            'data'      => [
                'dokter_id'   => $dokter?->id,
                'hari'        => $hari,
                'jam_mulai'   => $jamMulai,
                'jam_selesai' => $jamSelesai,
            ],
            'unique'    => ['dokter_id' => $dokter?->id, 'hari' => $hari, 'jam_mulai' => $jamMulai],
            'relations' => [],
            'errors'    => $errors,
        ];
    }

    private static function parsePnpp(array $row): array
    {
        $nama        = self::field($row, 'Nama');
        $nip         = self::field($row, 'NIP');
        $noBpjs      = self::field($row, 'No. BPJS');
        $satkerName  = self::field($row, 'Satker');
        $noHp        = self::field($row, 'No. HP');
        $tglLahir    = self::normalizeDate(self::field($row, 'Tanggal Lahir'));
        $jk          = self::normalizeJk(self::field($row, 'Jenis Kelamin'));
        $penyakitRaw = self::field($row, 'Penyakit Kronis');
        $errors      = [];

        $satkerId = null;

        if ($nama === '') {
            $errors[] = 'Nama wajib diisi';
        }

        if ($satkerName !== '') {
            $satker = Satker::where('nama', $satkerName)->first();
            if (! $satker) {
                $errors[] = "Satker \"{$satkerName}\" tidak ditemukan";
            } else {
                $satkerId = $satker->id;
            }
        }

        if (self::field($row, 'Tanggal Lahir') !== '' && $tglLahir === null) {
            $errors[] = 'Tanggal Lahir tidak valid (format YYYY-MM-DD)';
        }

        if ($jk === null) {
            $errors[] = 'Jenis Kelamin tidak valid (gunakan L atau P)';
        }

        if ($nip !== '' && ! preg_match('/^\d{18}$/', $nip)) {
            $errors[] = 'NIP harus 18 digit angka (pastikan kolom NIP di Excel berformat Text, bukan angka/notasi ilmiah)';
        }

        if ($noBpjs !== '' && ! preg_match('/^\d{13}$/', $noBpjs)) {
            $errors[] = 'No. BPJS harus 13 digit angka (pastikan kolom No. BPJS di Excel berformat Text)';
        }

        $penyakitIds = [];
        $penyakitNames = array_values(array_filter(array_map('trim', preg_split('/[,;]/', $penyakitRaw))));
        foreach ($penyakitNames as $name) {
            $penyakit = PenyakitKronis::where('nama', $name)->first();
            if (! $penyakit) {
                $errors[] = "Penyakit \"{$name}\" tidak ditemukan";
            } else {
                $penyakitIds[] = $penyakit->id;
            }
        }

        $unique = $nip !== ''
            ? ['nip' => $nip]
            : ($noBpjs !== '' ? ['no_bpjs' => $noBpjs] : ['nama' => $nama, 'tanggal_lahir' => $tglLahir]);

        return [
            'data'      => [
                'nama'          => $nama,
                'nip'           => $nip !== '' ? $nip : null,
                'no_bpjs'       => $noBpjs !== '' ? $noBpjs : null,
                'satker_id'     => $satkerId,
                'no_hp'         => $noHp !== '' ? $noHp : null,
                'tanggal_lahir' => $tglLahir,
                'jenis_kelamin' => $jk,
            ],
            'unique'    => $unique,
            'relations' => ['penyakit' => $penyakitIds],
            'errors'    => $errors,
        ];
    }

    // ------------------------------------------------------------------
    // Helper normalisasi
    // ------------------------------------------------------------------

    private static function field(array $row, string $key): string
    {
        return trim((string) ($row[$key] ?? ''));
    }

    private static function normalizeDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private static function normalizeTime(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $time = DateTimeImmutable::createFromFormat('H:i', $value);

        return ($time && $time->format('H:i') === $value) ? $value : null;
    }

    private static function normalizeHari(?string $value): ?string
    {
        $value = ucfirst(strtolower(trim((string) $value)));

        return in_array($value, Jadwal::HARI, true) ? $value : null;
    }

    private static function normalizeJk(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));

        return match (true) {
            in_array($value, ['l', 'lk', 'laki-laki', 'pria', 'male', 'm'], true) => 'L',
            in_array($value, ['p', 'pr', 'perempuan', 'wanita', 'female', 'f'], true) => 'P',
            default => null,
        };
    }
}

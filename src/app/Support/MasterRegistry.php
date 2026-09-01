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
                'Nama'               => $text,
                'NIP/NRP'            => $text,
                'Status Kepegawaian' => $select(['Anggota Polri', 'PNS', 'TNI', 'ASN Polri']),
                'Pangkat'            => $text,
                'Jabatan'            => $text,
                'Satker'             => $select(Satker::orderBy('nama')->pluck('nama')->all()),
                'Satuan Kerja'       => $text,
                'Bagian'             => $text,
                'Email'              => $text,
                'Alamat'             => $text,
                'No. BPJS'           => $text,
                'No. HP'             => $text,
                'Tanggal Lahir'      => $text,
                'Jenis Kelamin'      => $select(['L', 'P']),
                'Status Aktif'       => $select(['aktif', 'nonaktif']),
                'Penyakit Kronis'    => $multi(PenyakitKronis::orderBy('nama')->pluck('nama')->all()),
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
                'headers' => ['Nama', 'NIP/NRP', 'Status Kepegawaian', 'Pangkat', 'Jabatan', 'Satker', 'Satuan Kerja', 'Bagian', 'Email', 'Alamat', 'No. BPJS', 'No. HP', 'Tanggal Lahir', 'Jenis Kelamin', 'Status Aktif', 'Penyakit Kronis'],
                'sample'  => ['Budi Santoso', '198501012010011001', 'Anggota Polri', 'Bripka', 'Bintara', 'Dinas Kesehatan', 'Dinas Kesehatan', 'Bagian Umum', 'budi@contoh.id', 'Jl. Merdeka No. 1', '0001234567890', '081234567890', '1985-01-01', 'L', 'aktif', 'Hipertensi, Diabetes Melitus'],
                'toRow'   => fn (Pnpp $m) => [
                    $m->nama,
                    $m->nip ?? '',
                    $m->status_kepegawaian ?? '',
                    $m->pangkat ?? '',
                    $m->jabatan ?? '',
                    $m->satker?->nama ?? '',
                    $m->satuan_kerja ?? '',
                    $m->bagian ?? '',
                    $m->email ?? '',
                    $m->alamat ?? '',
                    $m->no_bpjs ?? '',
                    $m->no_hp ?? '',
                    $m->tanggal_lahir?->format('Y-m-d') ?? '',
                    $m->jenis_kelamin ?? '',
                    $m->status_aktif ?? 'aktif',
                    $m->penyakit->pluck('nama')->implode(', '),
                ],
                'parse'   => fn (array $r) => self::parsePnpp($r),
                'resolve' => fn (array $result) => self::resolvePnppSatker($result),
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
        $nip         = self::field($row, 'NIP/NRP');
        $statusKep   = self::field($row, 'Status Kepegawaian');
        $pangkat     = self::field($row, 'Pangkat');
        $jabatan     = self::field($row, 'Jabatan');
        $satkerName  = self::field($row, 'Satker');
        $satuanKerja = self::field($row, 'Satuan Kerja');
        $bagian      = self::field($row, 'Bagian');
        $email       = self::field($row, 'Email');
        $alamat      = self::field($row, 'Alamat');
        $noBpjs      = self::normalizeDigits(self::field($row, 'No. BPJS'));
        $noHp        = self::normalizePhone(self::field($row, 'No. HP'));
        $tglLahir    = self::normalizeDate(self::field($row, 'Tanggal Lahir'));
        $jk          = self::normalizeJk(self::field($row, 'Jenis Kelamin'));
        $statusAktif = self::field($row, 'Status Aktif');
        $penyakitRaw = self::field($row, 'Penyakit Kronis');
        $errors      = [];

        if ($nama === '') {
            $errors[] = 'Nama wajib diisi';
        }

        if ($nip === '') {
            $errors[] = 'NIP/NRP wajib diisi';
        }

        $satkerId = null;
        if ($satkerName !== '') {
            $satker = Satker::where('nama', $satkerName)->first();
            if ($satker) {
                $satkerId = $satker->id;
            }
        }

        $finalSatuanKerja = $satuanKerja !== '' ? $satuanKerja : ($satkerName !== '' ? $satkerName : null);

        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email tidak valid';
        }

        if (self::field($row, 'Tanggal Lahir') !== '' && $tglLahir === null) {
            $errors[] = 'Tanggal Lahir tidak valid (format YYYY-MM-DD)';
        }

        if ($noBpjs !== null && strlen($noBpjs) !== 13) {
            $errors[] = 'No. BPJS harus 13 digit angka (pastikan kolom berformat Text, bukan angka/notasi ilmiah)';
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

        $statusAktifValue = in_array($statusAktif, ['aktif', 'nonaktif'], true) ? $statusAktif : 'aktif';

        return [
            'data'      => [
                'nama'               => $nama,
                'nip'                => $nip !== '' ? $nip : null,
                'status_kepegawaian' => $statusKep !== '' ? $statusKep : null,
                'pangkat'            => $pangkat !== '' ? $pangkat : null,
                'jabatan'            => $jabatan !== '' ? $jabatan : null,
                'satuan_kerja'       => $finalSatuanKerja,
                'bagian'             => $bagian !== '' ? $bagian : null,
                'email'              => $email !== '' ? $email : null,
                'alamat'             => $alamat !== '' ? $alamat : null,
                'no_bpjs'            => $noBpjs,
                'satker_id'          => $satkerId,
                'no_hp'              => $noHp,
                'tanggal_lahir'      => $tglLahir,
                'jenis_kelamin'      => $jk,
                'status_aktif'       => $statusAktifValue,
            ],
            'unique'    => ['nip' => $nip],
            'relations' => ['penyakit' => $penyakitIds, 'satker_name' => $satkerName],
            'errors'    => $errors,
        ];
    }

    private static function resolvePnppSatker(array $result): array
    {
        $name = $result['relations']['satker_name'] ?? null;

        if ($name !== null && $name !== '') {
            $satker = Satker::firstOrCreate(['nama' => $name], ['nama' => $name]);
            $result['data']['satker_id'] = $satker->id;

            if (empty($result['data']['satuan_kerja'])) {
                $result['data']['satuan_kerja'] = $name;
            }
        }

        return $result;
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

    /**
     * Normalisasi nilai numerik (NIP/NRP, No. BPJS) menjadi string digit murni.
     * Menangani notasi ilmiah (E+) & float dari Excel secara best-effort.
     */
    public static function normalizeDigits(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (stripos($value, 'e') !== false) {
            $value = sprintf('%.0f', (float) $value);
        } elseif (is_numeric($value) && str_contains($value, '.')) {
            $value = sprintf('%.0f', (float) $value);
        }

        $digits = preg_replace('/\D/', '', $value);

        return $digits !== '' ? $digits : null;
    }

    /**
     * Normalisasi nomor telepon: strip non-digit, ubah 62… menjadi 0…,
     * dan pastikan diawali 0.
     */
    public static function normalizePhone(?string $value): ?string
    {
        $digits = self::normalizeDigits($value);
        if ($digits === null) {
            return null;
        }

        if (str_starts_with($digits, '62')) {
            $digits = '0' . substr($digits, 2);
        } elseif (! str_starts_with($digits, '0')) {
            $digits = '0' . $digits;
        }

        return $digits;
    }
}

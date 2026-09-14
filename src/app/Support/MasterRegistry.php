<?php

namespace App\Support;

use App\Broadcasting\PhoneFormat;
use App\Models\Dokter;
use App\Models\Jadwal;
use App\Models\PenyakitKronis;
use App\Models\PenyakitMenahun;
use App\Models\Pnpp;
use App\Models\Poli;
use App\Models\ResponManual;
use App\Models\Satker;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Registri konfigurasi import/export untuk semua data master.
 *
 * Setiap entitas mendefinisikan:
 *  - label        : nama tampilan
 *  - permission   : permission fitur yang mengunci import/export entitas
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
        return ['pnpp', 'satker', 'penyakit', 'penyakit-menahun', 'poli', 'dokter', 'jadwal'];
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
        $multi = fn (array $options) => ['type' => 'multiselect', 'options' => array_values($options)];
        $text = ['type' => 'text'];

        return match ($entity) {
            'pnpp' => [
                'Nama' => $text,
                'NIP/NRP' => $text,
                'Status Kepegawaian' => $select(['Anggota Polri', 'PNS', 'TNI', 'ASN Polri']),
                'Pangkat' => $text,
                'Jabatan' => $text,
                'Satker' => $select(Satker::orderBy('nama')->pluck('nama')->all()),
                'Satuan Kerja' => $text,
                'Bagian' => $text,
                'Email' => $text,
                'Alamat' => $text,
                'No. BPJS' => $text,
                'No. HP' => $text,
                'Tanggal Lahir' => $text,
                'Jenis Kelamin' => $select(['L', 'P']),
                'Status Aktif' => $select(['aktif', 'nonaktif']),
                'Penyakit Kronis' => $multi(PenyakitKronis::orderBy('nama')->pluck('nama')->all()),
                'Penyakit Menahun' => $multi(PenyakitMenahun::orderBy('nama')->pluck('nama')->all()),
            ],
            'dokter' => [
                'Nama' => $text,
                'Poli' => $select(Poli::orderBy('nama')->pluck('nama')->all()),
                'Spesialisasi' => $text,
            ],
            'jadwal' => [
                'Poli' => $select(Poli::orderBy('nama')->pluck('nama')->all()),
                'Dokter' => $select(Dokter::orderBy('nama')->pluck('nama')->all()),
                'Hari' => $select(Jadwal::HARI),
                'Jam Mulai' => $text,
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
                'label' => 'Satker',
                'permission' => 'manage satker',
                'model' => Satker::class,
                'eager' => [],
                'headers' => ['Kode', 'Nama'],
                'sample' => ['DINKES', 'Dinas Kesehatan'],
                'toRow' => fn (Satker $m) => [$m->kode ?? '', $m->nama],
                'parse' => fn (array $r) => self::parseKodeNama($r),
            ],

            'penyakit' => [
                'label' => 'Penyakit Kronis',
                'permission' => 'manage penyakit',
                'model' => PenyakitKronis::class,
                'eager' => [],
                'headers' => ['Kode', 'Nama'],
                'sample' => ['HTN', 'Hipertensi'],
                'toRow' => fn (PenyakitKronis $m) => [$m->kode ?? '', $m->nama],
                'parse' => fn (array $r) => self::parseKodeNama($r),
            ],

            'penyakit-menahun' => [
                'label' => 'Penyakit Menahun',
                'permission' => 'manage penyakit',
                'model' => PenyakitMenahun::class,
                'eager' => [],
                'headers' => ['Kode', 'Nama'],
                'sample' => ['GINJAL', 'Gagal Ginjal Kronis'],
                'toRow' => fn (PenyakitMenahun $m) => [$m->kode ?? '', $m->nama],
                'parse' => fn (array $r) => self::parseKodeNama($r),
            ],

            'poli' => [
                'label' => 'Poli',
                'permission' => 'manage poli',
                'model' => Poli::class,
                'eager' => [],
                'headers' => ['Kode', 'Nama'],
                'sample' => ['GIGI', 'Poli Gigi'],
                'toRow' => fn (Poli $m) => [$m->kode ?? '', $m->nama],
                'parse' => fn (array $r) => self::parseKodeNama($r),
            ],

            'dokter' => [
                'label' => 'Dokter',
                'permission' => 'manage dokter',
                'model' => Dokter::class,
                'eager' => ['poli'],
                'headers' => ['Nama', 'Poli', 'Spesialisasi'],
                'sample' => ['dr. Rina Pratiwi', 'Poli Umum', 'Dokter Umum'],
                'toRow' => fn (Dokter $m) => [$m->nama, $m->poli?->nama ?? '', $m->spesialisasi ?? ''],
                'parse' => fn (array $r) => self::parseDokter($r),
            ],

            'jadwal' => [
                'label' => 'Jadwal',
                'permission' => 'manage jadwal',
                'model' => Jadwal::class,
                'eager' => ['dokter.poli'],
                'headers' => ['Poli', 'Dokter', 'Hari', 'Jam Mulai', 'Jam Selesai'],
                'sample' => ['Poli Umum', 'dr. Rina Pratiwi', 'Senin', '08:00', '12:00'],
                'toRow' => fn (Jadwal $m) => [
                    $m->dokter?->poli?->nama ?? '',
                    $m->dokter?->nama ?? '',
                    $m->hari,
                    $m->jam_mulai->format('H:i'),
                    $m->jam_selesai->format('H:i'),
                ],
                'parse' => fn (array $r) => self::parseJadwal($r),
            ],

            'pnpp' => [
                'label' => 'PNPP',
                'permission' => 'manage pnpp',
                'model' => Pnpp::class,
                'eager' => ['satker', 'penyakit', 'penyakitMenahun'],
                'headers' => ['Nama', 'NIP/NRP', 'Status Kepegawaian', 'Pangkat', 'Jabatan', 'Satker', 'Satuan Kerja', 'Bagian', 'Email', 'Alamat', 'No. BPJS', 'No. HP', 'Tanggal Lahir', 'Jenis Kelamin', 'Status Aktif', 'Penyakit Kronis', 'Penyakit Menahun'],
                'sample' => ['Budi Santoso', '198501012010011001', 'Anggota Polri', 'Bripka', 'Bintara', 'Dinas Kesehatan', 'Dinas Kesehatan', 'Bagian Umum', 'budi@contoh.id', 'Jl. Merdeka No. 1', '0001234567890', '081234567890', '1985-01-01', 'L', 'aktif', 'Hipertensi, Diabetes Melitus', 'Gagal Ginjal Kronis'],
                'toRow' => fn (Pnpp $m) => [
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
                    $m->penyakitMenahun->pluck('nama')->implode(', '),
                ],
                'parse' => fn (array $r) => self::parsePnpp($r),
                'resolve' => fn (array $result) => self::resolvePnppSatker($result),
                'sync' => function (Pnpp $model, array $relations): void {
                    $model->penyakit()->sync($relations['penyakit'] ?? []);
                    $model->penyakitMenahun()->sync($relations['penyakit_menahun'] ?? []);
                },
            ],

            'respon' => [
                'label' => 'Respon',
                'permission' => 'manage respon',
                'model' => ResponManual::class,
                'eager' => [],
                'headers' => ['Nama', 'NRP/NIP', 'No. HP', 'Satker', 'Isi'],
                'sample' => ['Budi Santoso', '198501012010011001', '081234567890', 'Dinas Kesehatan', 'Baik, saya hadir kontrol.'],
                'toRow' => fn (ResponManual $m) => [
                    $m->nama ?? '',
                    $m->nrp_nip ?? '',
                    $m->no_hp ?? '',
                    $m->satker ?? '',
                    $m->isi ?? '',
                ],
                'parse' => fn (array $row) => self::parseResponManual($row),
            ],
        ];
    }

    // ------------------------------------------------------------------
    // Parser per entitas
    // ------------------------------------------------------------------

    private static function parseKodeNama(array $row): array
    {
        $kode = self::field($row, 'Kode');
        $nama = self::field($row, 'Nama');
        $errors = [];

        if ($nama === '') {
            $errors[] = 'Nama wajib diisi';
        }

        return [
            'data' => ['kode' => $kode !== '' ? $kode : null, 'nama' => $nama],
            'unique' => $kode !== '' ? ['kode' => $kode] : ['nama' => $nama],
            'relations' => [],
            'errors' => $errors,
        ];
    }

    private static function parseDokter(array $row): array
    {
        $nama = self::field($row, 'Nama');
        $poliName = self::field($row, 'Poli');
        $spesialis = self::field($row, 'Spesialisasi');
        $errors = [];

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
            'data' => [
                'poli_id' => $poli?->id,
                'nama' => $nama,
                'spesialisasi' => $spesialis !== '' ? $spesialis : null,
            ],
            'unique' => ['nama' => $nama],
            'relations' => [],
            'errors' => $errors,
        ];
    }

    private static function parseJadwal(array $row): array
    {
        $dokterName = self::field($row, 'Dokter');
        $hari = self::normalizeHari(self::field($row, 'Hari'));
        $jamMulai = self::normalizeTime(self::field($row, 'Jam Mulai'));
        $jamSelesai = self::normalizeTime(self::field($row, 'Jam Selesai'));
        $errors = [];

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
            'data' => [
                'dokter_id' => $dokter?->id,
                'hari' => $hari,
                'jam_mulai' => $jamMulai,
                'jam_selesai' => $jamSelesai,
            ],
            'unique' => ['dokter_id' => $dokter?->id, 'hari' => $hari, 'jam_mulai' => $jamMulai],
            'relations' => [],
            'errors' => $errors,
        ];
    }

    private static function parsePnpp(array $row): array
    {
        $nama = self::field($row, 'Nama');
        $nip = self::field($row, 'NIP/NRP');
        $statusKep = self::field($row, 'Status Kepegawaian');
        $pangkat = self::field($row, 'Pangkat');
        $jabatan = self::field($row, 'Jabatan');
        $satkerName = self::field($row, 'Satker');
        $satuanKerja = self::field($row, 'Satuan Kerja');
        $bagian = self::field($row, 'Bagian');
        $email = self::field($row, 'Email');
        $alamat = self::field($row, 'Alamat');
        $noBpjs = self::normalizeDigits(self::field($row, 'No. BPJS'));
        $noHp = self::normalizePhone(self::field($row, 'No. HP'));
        $tglLahir = self::normalizeDate(self::field($row, 'Tanggal Lahir'));
        $jk = self::normalizeJk(self::field($row, 'Jenis Kelamin'));
        $statusAktif = self::field($row, 'Status Aktif');
        $penyakitRaw = self::field($row, 'Penyakit Kronis');
        $menahunRaw = self::field($row, 'Penyakit Menahun');
        $errors = [];

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

        $penyakitMenahunIds = [];
        $penyakitMenahunNames = array_values(array_filter(array_map('trim', preg_split('/[,;]/', $menahunRaw))));
        foreach ($penyakitMenahunNames as $name) {
            $penyakitMenahun = PenyakitMenahun::where('nama', $name)->first();
            if (! $penyakitMenahun) {
                $errors[] = "Penyakit menahun \"{$name}\" tidak ditemukan";
            } else {
                $penyakitMenahunIds[] = $penyakitMenahun->id;
            }
        }

        $statusAktifValue = in_array($statusAktif, ['aktif', 'nonaktif'], true) ? $statusAktif : 'aktif';

        return [
            'data' => [
                'nama' => $nama,
                'nip' => $nip !== '' ? $nip : null,
                'status_kepegawaian' => $statusKep !== '' ? $statusKep : null,
                'pangkat' => $pangkat !== '' ? $pangkat : null,
                'jabatan' => $jabatan !== '' ? $jabatan : null,
                'satuan_kerja' => $finalSatuanKerja,
                'bagian' => $bagian !== '' ? $bagian : null,
                'email' => $email !== '' ? $email : null,
                'alamat' => $alamat !== '' ? $alamat : null,
                'no_bpjs' => $noBpjs,
                'satker_id' => $satkerId,
                'no_hp' => $noHp,
                'tanggal_lahir' => $tglLahir,
                'jenis_kelamin' => $jk,
                'status_aktif' => $statusAktifValue,
            ],
            'unique' => ['nip' => $nip],
            'relations' => [
                'penyakit' => $penyakitIds,
                'penyakit_menahun' => $penyakitMenahunIds,
                'satker_name' => $satkerName,
            ],
            'errors' => $errors,
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
    // Parser respon (balasan pasien — input manual & import Excel)
    // ------------------------------------------------------------------

    private static function parseResponManual(array $row): array
    {
        $nama = self::field($row, 'Nama');
        $nrpNip = self::field($row, 'NRP/NIP');
        $satker = self::field($row, 'Satker');
        $isi = self::field($row, 'Isi');
        $waktuRaw = (string) ($row['Waktu Masuk'] ?? '');
        $noHp = self::responWaPhone(self::field($row, 'No. HP'));
        $errors = [];

        if ($noHp === null) {
            $errors[] = 'No. HP wajib diisi';
        }

        if ($isi === '') {
            $errors[] = 'Isi wajib diisi';
        }

        $waktu = self::parseResponWaktu($waktuRaw);
        if ($waktuRaw !== '' && $waktu === null) {
            $errors[] = 'Waktu Masuk tidak valid (format YYYY-MM-DD HH:MM)';
        }

        return [
            'data' => [
                'nama' => $nama !== '' ? $nama : null,
                'nrp_nip' => $nrpNip !== '' ? $nrpNip : null,
                'no_hp' => $noHp,
                'satker' => $satker !== '' ? $satker : null,
                'isi' => $isi,
                'waktu' => $waktu ?? now(),
                'sumber' => ResponManual::SUMBER_IMPORT,
            ],
            'unique' => ['no_hp' => $noHp, 'isi' => $isi, 'sumber' => ResponManual::SUMBER_IMPORT],
            'relations' => [],
            'errors' => $errors,
        ];
    }

    /**
     * No. HP menjadi string digit lalu dikonversi ke format WA (628…),
     * konsisten dengan data balasan dari webhook.
     */
    private static function responWaPhone(?string $value): ?string
    {
        $digits = self::normalizeDigits($value);
        if ($digits === null) {
            return null;
        }

        return PhoneFormat::toWa($digits);
    }

    private static function parseResponWaktu(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        // Sel tanggal Excel sering terbaca sebagai bilangan seri (mis. 46259.5).
        if (is_numeric($value) && (float) $value > 30000) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
            } catch (\Throwable) {
                return null;
            }
        }

        foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d', 'd/m/Y H:i', 'd/m/Y', 'd-m-Y H:i', 'd-m-Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) {
                return Carbon::instance($date);
            }
        }

        return null;
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
            $digits = '0'.substr($digits, 2);
        } elseif (! str_starts_with($digits, '0')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }
}

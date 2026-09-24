<?php

namespace App\Services;

use App\Models\Pnpp;
use App\Models\RegisterPnpp;
use Illuminate\Support\Collection;

/**
 * Pencocokan & sinkronisasi data pendaftaran PNPP dengan tabel pnpps.
 *
 * Kunci pencocokan berurutan sesuai keputusan user: NIP → NIK → No. HP →
 * nama (+ satker). Baris pnpp yang soft-deleted diabaikan saat mencocok,
 * sehingga pendaftar yang sama bisa "baru" kembali bila datanya dihapus.
 */
class PencocokPnpp
{
    /**
     * Cari PNPP yang sudah cocok dengan data pendaftar (atau null bila baru).
     */
    public static function cari(RegisterPnpp $register): ?Pnpp
    {
        foreach (self::kunciPrioritas($register) as $kolom => $nilai) {
            $pnpp = Pnpp::where($kolom, trim($nilai))->first();
            if ($pnpp) {
                return $pnpp;
            }
        }

        return null;
    }

    /**
     * Data kolom pnpps yang diisi dari pendaftar.
     *
     * @return array<string, mixed>
     */
    public static function dataDariRegister(RegisterPnpp $register): array
    {
        return [
            'nama' => $register->nama,
            'nik' => $register->nik,
            'nip' => $register->nip,
            'jabatan' => $register->jabatan,
            'bagian' => $register->unit,
            'alamat' => $register->alamat,
            'no_hp' => $register->no_hp,
            'satker_id' => $register->satker_id,
        ];
    }

    /**
     * Sinkronkan data diri pendaftar ke tabel pnpps: buat baru bila belum
     * ada, atau perbarui bila sudah ada. NIP/NIK hanya diisi bila kolom lama
     * kosong (mencegah tabrakan unique index pnpps).
     */
    public static function sinkronkan(RegisterPnpp $register): Pnpp
    {
        $pnpp = self::cari($register);

        if ($pnpp) {
            $pnpp->fill(self::dataDariRegister($register));

            if (blank($pnpp->nip) && ! blank($register->nip)) {
                $pnpp->nip = $register->nip;
            }
            if (blank($pnpp->nik) && ! blank($register->nik)) {
                $pnpp->nik = $register->nik;
            }

            $pnpp->save();

            return $pnpp;
        }

        return Pnpp::create(self::dataDariRegister($register) + [
            'status_aktif' => 'aktif',
        ]);
    }

    /**
     * Tandai sekumpulan pendaftar mana yang sudah ada di database pnpp.
     * Query dibatch per kunci agar tidak N+1 saat render daftar.
     *
     * @return array<int, array{ada: bool, pnpp_id: int|null}>
     */
    public static function tandaiKoleksi(Collection $registers): array
    {
        $hasil = [];
        foreach ($registers as $r) {
            $hasil[$r->id] = ['ada' => false, 'pnpp_id' => null];
        }

        if ($registers->isEmpty()) {
            return $hasil;
        }

        // 1) Beri nilai berdasar NIP.
        self::tandaiDariKolomKunci($registers, $hasil, 'nip');
        // 2) Sisa berdasar NIK.
        self::tandaiDariKolomKunci($registers, $hasil, 'nik');
        // 3) Sisa berdasar No. HP.
        self::tandaiDariKolomKunci($registers, $hasil, 'no_hp');
        // 4) Sisa berdasar nama (+ satker).
        self::tandaiDariNama($registers, $hasil);

        return $hasil;
    }

    /**
     * Kandidat kunci pencocokan berurutan: nip, nik, no_hp (semua opsional).
     *
     * @return array<string, string>
     */
    protected static function kunciPrioritas(RegisterPnpp $register): array
    {
        return [
            'nip' => $register->nip,
            'nik' => $register->nik,
            'no_hp' => $register->no_hp,
        ];
    }

    /**
     * Tandai pendaftar yang cocok via satu kolom kunci (batch whereIn).
     *
     * @param  Collection<int, RegisterPnpp>  $registers
     * @param  array<int, array{ada: bool, pnpp_id: int|null}>  $hasil
     */
    protected static function tandaiDariKolomKunci(Collection $registers, array &$hasil, string $kolom): void
    {
        $sisa = $registers->filter(fn (RegisterPnpp $r) => ! $hasil[$r->id]['ada'] && ! blank($r->{$kolom}));

        if ($sisa->isEmpty()) {
            return;
        }

        $nilaiKeRegister = [];
        foreach ($sisa as $r) {
            $nilaiKeRegister[trim($r->{$kolom})] = $r->id;
        }

        foreach (Pnpp::whereIn($kolom, array_keys($nilaiKeRegister))->get(['id', $kolom]) as $pnpp) {
            $idReg = $nilaiKeRegister[trim($pnpp->{$kolom})] ?? null;
            if ($idReg !== null && ! $hasil[$idReg]['ada']) {
                $hasil[$idReg] = ['ada' => true, 'pnpp_id' => (int) $pnpp->id];
            }
        }
    }

    /**
     * Tandai sisa pendaftar yang cocok berdasarkan nama (+ satker_id).
     *
     * @param  Collection<int, RegisterPnpp>  $registers
     * @param  array<int, array{ada: bool, pnpp_id: int|null}>  $hasil
     */
    protected static function tandaiDariNama(Collection $registers, array &$hasil): void
    {
        $sisa = $registers->filter(fn (RegisterPnpp $r) => ! $hasil[$r->id]['ada']);

        if ($sisa->isEmpty()) {
            return;
        }

        $nama = $sisa->pluck('nama')->unique()->values()->all();

        $pnppByNama = Pnpp::whereIn('nama', $nama)
            ->get(['id', 'nama', 'satker_id'])
            ->groupBy('nama');

        foreach ($sisa as $r) {
            $kandidat = $pnppByNama->get($r->nama) ?? collect();

            $pnpp = $kandidat->first(
                fn ($p) => (int) $p->satker_id === (int) $r->satker_id,
            ) ?? $kandidat->first();

            if ($pnpp) {
                $hasil[$r->id] = ['ada' => true, 'pnpp_id' => (int) $pnpp->id];
            }
        }
    }
}
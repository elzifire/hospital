<?php

namespace App\Broadcasting;

use App\Models\Pnpp;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;

/**
 * Pengganti token template pesan di sisi server, per penerima.
 *
 * Token yang dikenali:
 *  {nama} {nip} {satker} {obat}  → data pasien PNPP
 *  {poli} {dokter} {tanggal} {jam} → dari meta (payload alur kirim)
 *  alias ({nama_pasien}, {nip_pasien}, {nomor_hp}, …) → ikut data PNPP
 *
 * Penulisan token bisa {token}, {{token}}, atau {{ token }} — semuanya
 * dinormalisasi jadi {token} sebelum dirender. Token yang tidak dikenal
 * / nilainya kosong dibiarkan apa adanya agar mudah terdeteksi dan
 * diperbaiki.
 */
class MessageRenderer
{
    /**
     * Alias token per-pasien → kunci token dasar. Dipakai server saat
     * render dan juga dibawa ke form (data pasien & tokenPribadi) supaya
     * pratinjau dan kiriman selalu konsisten.
     *
     * @return array<string, string>
     */
    public static function aliasPnpp(): array
    {
        return [
            'nama_pasien' => 'nama',
            'nama_lengkap' => 'nama',
            'nip_pasien' => 'nip',
            'satker_pasien' => 'satker',
            'nomor_hp' => 'no_hp',
            'no_hp_pasien' => 'no_hp',
            'nama_pnpp' => 'nama',
        ];
    }

    public function render(string $konten, ?Pnpp $pnpp = null, array $meta = [], array $kustom = []): string
    {
        $nilai = [
            'nama' => $pnpp?->nama,
            'nama_pnpp' => $pnpp?->nama,
            'nip' => $pnpp?->nip,
            'nip_pnpp' => $pnpp?->nip,
            'satker' => $pnpp?->satker?->nama,
            'satker_pnpp' => $pnpp?->satker?->nama,
            'no_hp' => $pnpp?->no_hp,
            'obat' => $pnpp ? $pnpp->penyakit->pluck('nama')->implode(', ') : null,
            'poli' => $this->nilaiMeta($meta, 'poli'),
            'instalasi' => $this->nilaiMeta($meta, 'instalasi') ?? $this->nilaiMeta($meta, 'poli'),
            'dokter' => $this->nilaiMeta($meta, 'dokter'),
            'tanggal' => $this->tanggal($meta),
            'jam' => $this->nilaiMeta($meta, 'jam'),
            'hari_tanggal' => $this->tanggal($meta),
            'waktu_kunjungan' => $this->nilaiMeta($meta, 'jam'),
            'poli_layanan' => $this->nilaiMeta($meta, 'poli'),
        ];

        // Override manual (form kirim/ubah): nilai yang diisi petugas
        // menang atas data pasien & meta untuk token mana pun.
        foreach ($kustom as $kunci => $isi) {
            if (is_scalar($isi) && trim((string) $isi) !== '') {
                $nilai[strtolower((string) $kunci)] = (string) $isi;
            }
        }

        // Alias per-pasien menurun dari nilai token dasar final; alias
        // yang di-override manual tidak diganggu.
        foreach (static::aliasPnpp() as $alias => $asli) {
            $nilai[$alias] ??= $nilai[$asli] ?? null;
        }

        // Normalisasi penulisan rapi {{ token }} / {{token}} → {token}.
        $konten = (string) preg_replace('/\{\{\s*([a-z_]+)\s*\}\}/i', '{$1}', $konten);

        return (string) preg_replace_callback('/\{+([a-z_]+)\}+/i', function (array $cocok) use ($nilai) {
            $kunci = strtolower($cocok[1]);
            $isi = $nilai[$kunci] ?? null;

            return ($isi !== null && $isi !== '') ? (string) $isi : $cocok[0];
        }, $konten);
    }

    /**
     * Nilai meta: array digabung dengan koma, string dipakai apa adanya.
     */
    protected function nilaiMeta(array $meta, string $kunci): ?string
    {
        $isi = $meta[$kunci] ?? null;

        if (is_array($isi)) {
            return $isi !== [] ? implode(', ', $isi) : null;
        }

        $isi = trim((string) $isi);

        return $isi !== '' ? $isi : null;
    }

    /**
     * Format tanggal meta ke bentuk Indonesia (mis. "Kamis, 10 September 2026");
     * nilai yang bukan tanggal dipakai apa adanya.
     */
    protected function tanggal(array $meta): ?string
    {
        $isi = $this->nilaiMeta($meta, 'tanggal');

        if ($isi === null) {
            return null;
        }

        try {
            return Carbon::parse($isi)->locale('id')->translatedFormat('l, j F Y');
        } catch (InvalidFormatException) {
            return $isi;
        }
    }
}

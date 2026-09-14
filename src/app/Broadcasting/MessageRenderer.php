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
 *
 * Token yang tidak dikenal / nilainya kosong dibiarkan apa adanya
 * agar mudah terdeteksi dan diperbaiki.
 */
class MessageRenderer
{
    public function render(string $konten, ?Pnpp $pnpp = null, array $meta = [], array $kustom = []): string
    {
        $nilai = [
            'nama' => $pnpp?->nama,
            'nip' => $pnpp?->nip,
            'satker' => $pnpp?->satker?->nama,
            'obat' => $pnpp ? $pnpp->penyakit->pluck('nama')->implode(', ') : null,
            'poli' => $this->nilaiMeta($meta, 'poli'),
            'instalasi' => $this->nilaiMeta($meta, 'instalasi') ?? $this->nilaiMeta($meta, 'poli'),
            'dokter' => $this->nilaiMeta($meta, 'dokter'),
            'tanggal' => $this->tanggal($meta),
            'jam' => $this->nilaiMeta($meta, 'jam'),
            // Alias token template pengingat kunjungan — turunan data jadwal
            // Digital Reminder (poli/dokter/tanggal/jam dari penjadwalan).
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

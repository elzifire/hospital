<?php

namespace App\Support;

use Normalizer;

/**
 * Sanitasi teks agar aman untuk database ber-encoding Windows-1252/Latin1.
 *
 * Karakter di luar jangkauan Windows-1252 — misal emotikon ✅ (U+2705, UTF-8
 * 0xe2 0x9c 0x85) — akan ditolak Postgres dengan SQLSTATE 22P05. Sebelum
 * insert/update, teks bebas (template, konten pesan, input publik) dilewatkan
 * ke sini: simbol umum diterjemahkan ke padanan teksnya, sisanya diganti.
 */
class TextSanitizer
{
    /** @var array<string, string> */
    private static ?array $peta = null;

    public static function win1252(string $nilai, string $pengganti = ' '): string
    {
        if ($nilai === '') {
            return '';
        }

        $nilai = self::normalize($nilai);

        $hasil = preg_replace_callback(
            '/./u',
            static fn (array $c) => self::resolusi($c[0], $pengganti),
            $nilai,
        );

        return self::rapikan((string) $hasil);
    }

    private static function resolusi(string $char, string $pengganti): string
    {
        self::$peta ??= [
            // Emotikon/simbol umum → padanan teks agar pesan tetap terbaca.
            '✅' => '[OK]', '☑' => '[v]', '✔' => 'v', '✖' => 'x', '❌' => '[x]',
            '❗' => '!', '❓' => '?', '⭐' => '*', '★' => '*', '✦' => '*',
            '➡' => '->', '⬅' => '<-', '⬆' => '^', '⬇' => 'v',
            '⚠' => '!', '🚨' => '[!]', '🎉' => '[selamat]', '🥳' => '[selamat]',
            '❤' => '<3', '🌐' => '[web]', '📱' => '[hp]', '📞' => '[telepon]',
            // Pengubah tak terlihat / format → dihapus diam-diam.
            "\u{200D}" => '', "\u{FE0F}" => '', "\u{00AD}" => '',
        ];

        if (isset(self::$peta[$char])) {
            return self::$peta[$char];
        }

        // Tetap pertahankan karakter yang terwakili Windows-1252
        // (termasuk é, è, €, ™, …, —, “”). Sisanya diganti.
        if (@iconv('UTF-8', 'WINDOWS-1252', $char) !== false) {
            return $char;
        }

        return $pengganti;
    }

    private static function normalize(string $nilai): string
    {
        if (function_exists('normalizer_normalize')) {
            $ternormalisasi = normalizer_normalize($nilai, Normalizer::FORM_C);
            if (is_string($ternormalisasi)) {
                return $ternormalisasi;
            }
        }

        return $nilai;
    }

    private static function rapikan(string $hasil): string
    {
        $hasil = preg_replace('/[ \t\x{00A0}]+/u', ' ', $hasil);

        return trim((string) $hasil);
    }
}

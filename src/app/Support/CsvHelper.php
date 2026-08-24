<?php

namespace App\Support;

class CsvHelper
{
    /**
     * Parse file CSV menjadi daftar baris asosiatif (key = nama kolom header).
     * Menghilangkan UTF-8 BOM dan baris kosong.
     *
     * @return array{headers: array<int,string>, rows: array<int, array<string,string>>}
     */
    public static function parseFile(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return ['headers' => [], 'rows' => []];
        }

        $headers = null;
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            // Baris kosong (hasil fgetcsv pada baris kosong = [null]).
            if ($line === [null]) {
                continue;
            }

            if ($headers === null) {
                // Bersihkan BOM dari header pertama.
                $line[0] = self::stripBom($line[0]);
                $headers = array_map('trim', $line);
                continue;
            }

            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = trim($line[$i] ?? '');
            }

            // Lewati baris yang seluruhnya kosong.
            if (! array_filter($row, fn ($v) => $v !== '')) {
                continue;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return ['headers' => $headers ?? [], 'rows' => $rows];
    }

    /**
     * Bangun isi file CSV (dengan UTF-8 BOM agar kompatibel Excel).
     *
     * @param  array<int, array<int,string>>  $rows
     * @param  array<int, string>  $headers
     */
    public static function build(array $rows, array $headers): string
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, $headers);

        foreach ($rows as $row) {
            fputcsv($stream, array_map(fn ($v) => (string) $v, $row));
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }

    private static function stripBom(string $value): string
    {
        if (str_starts_with($value, "\xEF\xBB\xBF")) {
            return substr($value, 3);
        }

        return $value;
    }
}

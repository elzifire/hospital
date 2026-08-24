<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Helper baca/tulis file Excel (.xlsx/.xls) dan CSV via PhpSpreadsheet.
 */
class SheetHelper
{
    /**
     * Baca file menjadi daftar baris asosiatif (key = nama kolom header).
     * Mendukung .xlsx, .xls, dan .csv.
     *
     * @return array{headers: array<int,string>, rows: array<int, array<string,string>>}
     */
    public static function readToRows(string $path): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === 'csv' || $ext === 'txt') {
            return CsvHelper::parseFile($path);
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        $data = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        if (empty($data)) {
            return ['headers' => [], 'rows' => []];
        }

        $headers = array_map(fn ($v) => trim((string) ($v ?? '')), array_shift($data));

        $rows = [];
        foreach ($data as $line) {
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = trim((string) ($line[$i] ?? ''));
            }

            if (! array_filter($row, fn ($v) => $v !== '')) {
                continue;
            }

            $rows[] = $row;
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Bangun isi file sesuai format.
     *
     * @param  array<int, array<int,string>>  $rows
     * @param  array<int, string>  $headers
     */
    public static function content(array $rows, array $headers, string $format): string
    {
        return $format === 'xlsx'
            ? self::xlsxContent($rows, $headers)
            : CsvHelper::build($rows, $headers);
    }

    /**
     * Bangun isi file Excel (.xlsx) sebagai binary string.
     *
     * @param  array<int, array<int,string>>  $rows
     * @param  array<int, string>  $headers
     */
    public static function xlsxContent(array $rows, array $headers): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $all = [$headers];
        foreach ($rows as $row) {
            $all[] = array_values((array) $row);
        }

        $sheet->fromArray($all, null, 'A1');

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(max(1, count($headers)));

        // Gaya header: tebal.
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);

        // Lebar kolom otomatis.
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        $writer->save($tmp);

        $content = file_get_contents($tmp);
        @unlink($tmp);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $content;
    }
}

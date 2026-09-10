<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Helper baca/tulis file Excel (.xlsx/.xls) dan CSV via PhpSpreadsheet.
 */
class SheetHelper
{
    public const CHUNK_SIZE = 250;

    /**
     * Baca spreadsheet per potongan baris agar workbook besar tidak dimuat penuh ke RAM.
     * Callback menerima headers sekali, lalu setiap baris data.
     *
     * @return array{headers: array<int,string>, rows: int}
     */
    public static function readInChunks(string $path, callable $callback, int $chunkSize = self::CHUNK_SIZE): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === 'csv' || $ext === 'txt') {
            $handle = fopen($path, 'r');
            if ($handle === false) {
                return ['headers' => [], 'rows' => 0];
            }

            $headers = null;
            $rowNumber = 1;
            $dataRows = 0;
            while (($line = fgetcsv($handle)) !== false) {
                if ($line === [null]) {
                    continue;
                }
                if ($headers === null) {
                    $line[0] = self::stripBom((string) ($line[0] ?? ''));
                    $headers = array_map('trim', $line);
                    $callback($headers, null, 1);

                    continue;
                }

                $rowNumber++;
                $row = self::mapSpreadsheetRow($headers, $line);
                if ($row === null) {
                    continue;
                }
                $dataRows++;
                $callback($headers, $row, $rowNumber);
            }
            fclose($handle);

            return ['headers' => $headers ?? [], 'rows' => $dataRows];
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $headers = [];
        $dataRows = 0;
        $rowNumber = 2;
        $firstChunk = true;

        for ($start = 2; ; $start += $chunkSize) {
            $reader->setReadFilter(new SpreadsheetChunkReadFilter($start, $start + $chunkSize - 1));
            $spreadsheet = $reader->load($path);
            $data = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            if ($firstChunk) {
                $headerRow = $data[0] ?? [];
                $headers = array_map(fn ($value) => trim((string) ($value ?? '')), $headerRow);
                $callback($headers, null, 1);
                $firstChunk = false;
            }

            $foundData = false;
            foreach ($data as $index => $line) {
                if ($index === 0) {
                    continue;
                }
                $currentRow = $start + $index - 1;
                $row = self::mapSpreadsheetRow($headers, $line);
                if ($row === null) {
                    continue;
                }
                $foundData = true;
                $dataRows++;
                $rowNumber = $currentRow;
                $callback($headers, $row, $rowNumber);
            }

            if (! $foundData && count($data) <= 1) {
                break;
            }
        }

        return ['headers' => $headers, 'rows' => $dataRows];
    }

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

    private static function mapSpreadsheetRow(array $headers, array $line): ?array
    {
        $row = [];
        foreach ($headers as $i => $header) {
            $row[$header] = trim((string) ($line[$i] ?? ''));
        }

        return array_filter($row, fn ($value) => $value !== '') ? $row : null;
    }

    private static function stripBom(string $value): string
    {
        return str_starts_with($value, "\xEF\xBB\xBF") ? substr($value, 3) : $value;
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
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $all = [$headers];
        foreach ($rows as $row) {
            $all[] = array_values((array) $row);
        }

        $sheet->fromArray($all, null, 'A1');

        $lastCol = Coordinate::stringFromColumnIndex(max(1, count($headers)));

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

<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ImportExportService
{
    /**
     * Kolom wajib per tipe import.
     */
    private const REQUIRED_COLUMNS = [
        'alumni' => [
            'nim', 'full_name', 'email',
            'study_program_id', 'graduation_year_id', 'gpa',
        ],
    ];

    /**
     * Header kolom template Excel per tipe.
     */
    private const TEMPLATE_HEADERS = [
        'alumni' => [
            'A' => ['label' => 'NIM *',           'example' => '20210001'],
            'B' => ['label' => 'Nama Lengkap *',   'example' => 'Ahmad Fauzan'],
            'C' => ['label' => 'Email *',           'example' => 'ahmad@email.com'],
            'D' => ['label' => 'ID Program Studi *','example' => '1'],
            'E' => ['label' => 'ID Tahun Lulus *', 'example' => '1'],
            'F' => ['label' => 'IPK *',             'example' => '3.75'],
            'G' => ['label' => 'NIK',               'example' => '3509010101010001'],
            'H' => ['label' => 'Tempat Lahir',      'example' => 'Lumajang'],
            'I' => ['label' => 'Tanggal Lahir',     'example' => '2000-01-01'],
            'J' => ['label' => 'Jenis Kelamin',     'example' => 'M'],
            'K' => ['label' => 'Agama',             'example' => 'Islam'],
            'L' => ['label' => 'Alamat Jalan',      'example' => 'Jl. Ahmad Yani No.1'],
            'M' => ['label' => 'Kelurahan',         'example' => 'Citrodiwangsan'],
            'N' => ['label' => 'Kecamatan',         'example' => 'Lumajang'],
            'O' => ['label' => 'Kota/Kabupaten',   'example' => 'Lumajang'],
            'P' => ['label' => 'Provinsi',          'example' => 'Jawa Timur'],
            'Q' => ['label' => 'Kode Pos',          'example' => '67311'],
            'R' => ['label' => 'No. Telepon',       'example' => '08123456789'],
            'S' => ['label' => 'Predikat Kelulusan','example' => 'Cumlaude'],
            'T' => ['label' => 'Judul Skripsi',     'example' => 'Analisis...'],
            'U' => ['label' => 'URL LinkedIn',      'example' => 'https://linkedin.com/in/...'],
        ],
    ];

    /**
     * Parse file Excel menjadi array of rows (array asosiatif).
     * Baris pertama dianggap sebagai header.
     *
     * @return array<int, array<string, string>>
     */
    public function parseExcel(UploadedFile $file): array
    {
        $data = Excel::toArray([], $file);

        if (empty($data) || empty($data[0])) {
            return [];
        }

        $sheet = $data[0];
        // Baris pertama = header
        $rawHeaders = array_shift($sheet);
        $headers    = array_map(fn ($h) => Str::snake(trim((string) $h)), $rawHeaders);

        $rows = [];
        foreach ($sheet as $index => $row) {
            // Skip baris kosong
            if (empty(array_filter($row, fn ($v) => $v !== null && $v !== ''))) {
                continue;
            }

            $assoc = [];
            foreach ($headers as $colIdx => $headerKey) {
                $assoc[$headerKey] = isset($row[$colIdx]) ? trim((string) $row[$colIdx]) : null;
            }

            $assoc['_row_number'] = $index + 2; // +2 karena header di baris 1
            $rows[]               = $assoc;
        }

        return $rows;
    }

    /**
     * Validasi rows hasil parseExcel.
     *
     * @param  array<int, array<string, mixed>> $rows
     * @param  string                           $type  'alumni'
     * @return array{valid: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    public function validateRows(array $rows, string $type): array
    {
        $required = self::REQUIRED_COLUMNS[$type] ?? [];
        $valid    = [];
        $errors   = [];

        foreach ($rows as $row) {
            $rowNum    = $row['_row_number'] ?? '?';
            $rowErrors = [];

            foreach ($required as $col) {
                if (empty($row[$col])) {
                    $rowErrors[] = "Kolom '{$col}' wajib diisi";
                }
            }

            // Validasi format email
            if (!empty($row['email']) && !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = "Format email tidak valid";
            }

            // Validasi IPK
            if (!empty($row['gpa'])) {
                $gpa = (float) $row['gpa'];
                if ($gpa < 0 || $gpa > 4.0) {
                    $rowErrors[] = "IPK harus antara 0.00 – 4.00";
                }
            }

            if (!empty($rowErrors)) {
                $errors[] = "Baris {$rowNum}: " . implode(', ', $rowErrors);
            } else {
                // Buang meta key sebelum masuk valid
                $cleanRow = collect($row)->except('_row_number')->toArray();
                $valid[]  = $cleanRow;
            }
        }

        return ['valid' => $valid, 'errors' => $errors];
    }

    /**
     * Generate template Excel untuk diunduh.
     * Simpan di storage/app/private/templates/{type}_import_template.xlsx.
     *
     * @return string  path relatif di storage
     */
    public function generateTemplate(string $type): string
    {
        $headers   = self::TEMPLATE_HEADERS[$type] ?? [];
        $path      = "templates/{$type}_import_template.xlsx";
        $storagePath = storage_path('app/private/' . $path);

        // Buat direktori jika belum ada
        if (!is_dir(dirname($storagePath))) {
            mkdir(dirname($storagePath), 0755, true);
        }

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');

        // Baris 1: Label kolom (bold, background hijau muda)
        // Baris 2: Contoh data (italic, warna abu)
        $col = 1;
        foreach ($headers as $colLetter => $info) {
            $sheet->setCellValueByColumnAndRow($col, 1, $info['label']);
            $sheet->setCellValueByColumnAndRow($col, 2, $info['example']);

            // Style header
            $cellHeader = $colLetter . '1';
            $sheet->getStyle($cellHeader)->getFont()->setBold(true);
            $sheet->getStyle($cellHeader)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D4EDDA');
            $sheet->getStyle($cellHeader)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Style example
            $cellExample = $colLetter . '2';
            $sheet->getStyle($cellExample)->getFont()->setItalic(true);
            $sheet->getStyle($cellExample)->getFont()->getColor()->setRGB('6C757D');

            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            $col++;
        }

        // Freeze baris header
        $sheet->freezePane('A3');

        $writer = new XlsxWriter($spreadsheet);
        $writer->save($storagePath);

        return $path;
    }

    /**
     * Export data ke Excel.
     * Dipanggil dari GenerateReportExport job.
     *
     * @param  array<int, array<string, mixed>> $data
     * @param  array<string, string>            $headers  kolom => label
     * @param  string                           $storagePath  path lengkap di filesystem
     */
    public function exportToExcel(array $data, array $headers, string $storagePath): void
    {
        if (!is_dir(dirname($storagePath))) {
            mkdir(dirname($storagePath), 0755, true);
        }

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        // Header row
        $col = 1;
        foreach ($headers as $label) {
            $sheet->setCellValueByColumnAndRow($col++, 1, $label);
        }
        $headerRange = 'A1:' . $sheet->getHighestColumn() . '1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('343A40');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setRGB('FFFFFF');

        // Data rows
        $row = 2;
        foreach ($data as $record) {
            $col = 1;
            foreach (array_keys($headers) as $key) {
                $sheet->setCellValueByColumnAndRow($col++, $row, $record[$key] ?? '');
            }
            $row++;
        }

        // Auto-size semua kolom
        foreach (range(1, count($headers)) as $colIdx) {
            $sheet->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
        }

        $writer = new XlsxWriter($spreadsheet);
        $writer->save($storagePath);
    }
}

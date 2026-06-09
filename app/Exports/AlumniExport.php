<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * AlumniExport
 * Export data alumni ke Excel menggunakan Laravel Excel.
 * Dipanggil dari GenerateReportExport job.
 */
class AlumniExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    /**
     * @param  array<string>         $headings  Header kolom
     * @param  array<array<mixed>>   $rows      Data rows
     */
    public function __construct(
        private readonly array $headings,
        private readonly array $rows,
    ) {}

    /**
     * @return array<array<mixed>>
     */
    public function array(): array
    {
        return $this->rows;
    }

    /**
     * @return array<string>
     */
    public function headings(): array
    {
        return $this->headings;
    }

    /**
     * Style baris header (bold).
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * AlumniExport
 *
 * Digunakan oleh GenerateReportExport job.
 * Implements Maatwebsite\Excel concerns untuk file .xlsx yang rapi.
 */
class AlumniExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly Collection $rows
    ) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    /**
     * @return array<string>
     */
    public function headings(): array
    {
        return [
            'NIM',
            'Nama Lengkap',
            'Jenis Kelamin',
            'Program Studi',
            'Angkatan',
            'IPK',
            'Predikat',
            'Email',
            'Telepon',
            'Kota',
            'Provinsi',
            'Status Survei',
            'Tanggal Daftar',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Baris 1 (heading) → bold
            1 => ['font' => ['bold' => true]],
        ];
    }
}

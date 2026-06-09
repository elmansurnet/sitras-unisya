<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\GraduationYear;
use App\Models\StudyProgram;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithFirstRowAsHeader;

class ImportExportService
{
    /**
     * Kolom wajib di file Excel import alumni.
     */
    private const REQUIRED_COLUMNS = [
        'nim', 'full_name', 'gender', 'study_program_code', 'graduation_year',
    ];

    /**
     * Parse file Excel ke array of associative array.
     *
     * @param UploadedFile $file
     * @return Collection<int, array>
     */
    public function parseExcel(UploadedFile $file): Collection
    {
        $importer = new class implements ToCollection, WithFirstRowAsHeader {
            public Collection $rows;

            public function collection(Collection $collection): void
            {
                $this->rows = $collection;
            }
        };

        Excel::import($importer, $file);

        return $importer->rows ?? collect();
    }

    /**
     * Validasi baris dari Excel.
     *
     * @param  Collection $rows
     * @return array{ valid: array, errors: array }
     */
    public function validateRows(Collection $rows): array
    {
        $valid  = [];
        $errors = [];

        // Cache data referensi untuk efisiensi
        $studyPrograms   = StudyProgram::pluck('id', 'code')->toArray();
        $graduationYears = GraduationYear::pluck('id', 'year')->toArray();
        $existingNims    = Alumni::withTrashed()->pluck('nim')->toArray();

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // baris Excel (header = row 1)
            $row    = array_map(fn ($v) => trim((string) $v), $row->toArray());
            $rowErrors = [];

            // Cek kolom wajib
            foreach (self::REQUIRED_COLUMNS as $col) {
                if (empty($row[$col])) {
                    $rowErrors[] = "Kolom `{$col}` wajib diisi.";
                }
            }

            // Validasi NIM unik
            if (!empty($row['nim']) && in_array($row['nim'], $existingNims, true)) {
                $rowErrors[] = "NIM `{$row['nim']}` sudah terdaftar.";
            }

            // Validasi gender
            if (!empty($row['gender']) && !in_array(strtoupper($row['gender']), ['L', 'P'], true)) {
                $rowErrors[] = "Kolom `gender` harus berisi L atau P.";
            }

            // Resolusi study_program_id
            $studyProgramId = null;
            if (!empty($row['study_program_code'])) {
                $studyProgramId = $studyPrograms[$row['study_program_code']] ?? null;
                if (!$studyProgramId) {
                    $rowErrors[] = "Kode prodi `{$row['study_program_code']}` tidak ditemukan.";
                }
            }

            // Resolusi graduation_year_id
            $graduationYearId = null;
            if (!empty($row['graduation_year'])) {
                $graduationYearId = $graduationYears[(int) $row['graduation_year']] ?? null;
                if (!$graduationYearId) {
                    $rowErrors[] = "Tahun lulus `{$row['graduation_year']}` tidak ditemukan.";
                }
            }

            if ($rowErrors) {
                $errors[] = [
                    'row'    => $rowNum,
                    'nim'    => $row['nim'] ?? '-',
                    'errors' => $rowErrors,
                ];
                continue;
            }

            $valid[] = [
                'nim'                => $row['nim'],
                'full_name'          => $row['full_name'],
                'gender'             => strtoupper($row['gender']),
                'study_program_id'   => $studyProgramId,
                'graduation_year_id' => $graduationYearId,
                'email'              => $row['email']              ?? null,
                'phone'              => $row['phone']              ?? null,
                'birth_place'        => $row['birth_place']        ?? null,
                'birth_date'         => $row['birth_date']         ?? null,
                'gpa'                => isset($row['gpa']) && $row['gpa'] !== ''
                                        ? (float) $row['gpa']
                                        : null,
                'thesis_title'       => $row['thesis_title']       ?? null,
                'address_city'       => $row['address_city']       ?? null,
                'address_province'   => $row['address_province']   ?? null,
                'survey_status'      => 'belum_disurvei',
            ];

            // Tambah NIM ke existing list agar duplikat dalam batch tertangkap
            $existingNims[] = $row['nim'];
        }

        return compact('valid', 'errors');
    }

    /**
     * Insert batch alumni yang sudah tervalidasi.
     * Setiap alumni dibuat beserta user-nya.
     */
    public function batchInsert(array $rows, string $batchId): void
    {
        foreach ($rows as $row) {
            $user = \App\Models\User::create([
                'name'      => $row['full_name'],
                'email'     => $row['email'] ?? null,
                'phone'     => $row['phone'] ?? null,
                'role'      => 'alumni',
                'is_active' => true,
            ]);

            Alumni::create(array_merge($row, [
                'user_id'      => $user->id,
                'import_batch' => $batchId,
            ]));
        }
    }

    /**
     * Generate template Excel kosong untuk import.
     * Return path file di storage private.
     */
    public function generateTemplate(): string
    {
        $export = new class implements FromCollection, WithHeadings {
            public function collection(): Collection
            {
                return collect([[
                    'A001',
                    'Contoh Nama Alumni',
                    'L',
                    'PAI',
                    '2023',
                    'alumni@email.com',
                    '08123456789',
                    'Lumajang',
                    '2000-01-01',
                    '3.75',
                    'Judul Tugas Akhir',
                    'Lumajang',
                    'Jawa Timur',
                ]]);
            }

            public function headings(): array
            {
                return [
                    'nim',
                    'full_name',
                    'gender',
                    'study_program_code',
                    'graduation_year',
                    'email',
                    'phone',
                    'birth_place',
                    'birth_date',
                    'gpa',
                    'thesis_title',
                    'address_city',
                    'address_province',
                ];
            }
        };

        $filename = 'templates/alumni-import-template.xlsx';
        Excel::store($export, $filename, 'private');

        return $filename;
    }

    /**
     * Export alumni ke Excel.
     * Return path file di storage private.
     *
     * @param  array|\Illuminate\Support\Collection $alumni
     */
    public function exportExcel(iterable $alumni): string
    {
        $export = new class ($alumni) implements FromCollection, WithHeadings {
            public function __construct(private readonly iterable $alumni) {}

            public function collection(): Collection
            {
                return collect($this->alumni)->map(fn ($a) => [
                    $a->nim,
                    $a->full_name,
                    $a->gender,
                    $a->studyProgram?->code,
                    $a->studyProgram?->name,
                    $a->graduationYear?->year,
                    $a->email,
                    $a->user?->phone ?? $a->phone,
                    $a->gpa !== null ? (float) $a->gpa : null,
                    $a->survey_status,
                    $a->address_city,
                    $a->address_province,
                    $a->created_at?->toDateString(),
                ]);
            }

            public function headings(): array
            {
                return [
                    'NIM', 'Nama Lengkap', 'Jenis Kelamin',
                    'Kode Prodi', 'Nama Prodi', 'Tahun Lulus',
                    'Email', 'Telepon', 'IPK',
                    'Status Survei', 'Kota', 'Provinsi', 'Tanggal Daftar',
                ];
            }
        };

        $filename = 'exports/alumni-export-' . now()->format('Ymd-His') . '.xlsx';
        Excel::store($export, $filename, 'private');

        return $filename;
    }
}

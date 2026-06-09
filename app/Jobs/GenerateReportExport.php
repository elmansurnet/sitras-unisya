<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Repositories\AlumniRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * GenerateReportExport
 * Generate file Excel untuk export alumni/report dan simpan ke storage/app/private/exports/
 * Dispatch dari AlumniService::export()
 *
 * Dispatch contoh:
 *   GenerateReportExport::dispatch('alumni', $filters, 'exports/file.xlsx', $userId)
 *       ->onQueue('default');
 */
class GenerateReportExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 300; // 5 menit untuk dataset besar

    /**
     * @param  string              $type    Tipe export: alumni | employer | survey
     * @param  array<string,mixed> $filters Filter data
     * @param  string              $path    Path output relatif ke storage/app/private/
     * @param  int                 $userId  user_id yang request export
     */
    public function __construct(
        private readonly string $type,
        private readonly array  $filters,
        private readonly string $path,
        private readonly int    $userId,
    ) {
        $this->onQueue('default');
    }

    /**
     * Eksekusi job.
     */
    public function handle(AlumniRepository $alumniRepo): void
    {
        try {
            match ($this->type) {
                'alumni' => $this->exportAlumni($alumniRepo),
                default  => throw new \InvalidArgumentException("Unknown export type: {$this->type}"),
            };

            AuditLog::record(
                action   : 'export',
                module   : $this->type,
                modelId  : null,
                oldValues: null,
                newValues: [
                    'path'    => $this->path,
                    'filters' => $this->filters,
                    'user_id' => $this->userId,
                ],
            );
        } catch (\Throwable $e) {
            Log::error('GenerateReportExport failed', [
                'type'  => $this->type,
                'path'  => $this->path,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Export alumni ke Excel menggunakan Laravel Excel.
     */
    private function exportAlumni(AlumniRepository $alumniRepo): void
    {
        $data = $alumniRepo->allWithFilters($this->filters);

        // Konversi ke array rows untuk export
        $rows = $data->map(fn ($alumni) => [
            $alumni->nim,
            $alumni->full_name,
            $alumni->gender,
            $alumni->studyProgram?->name,
            $alumni->graduationYear?->year,
            $alumni->gpa,
            $alumni->graduation_predicate,
            $alumni->user?->email,
            $alumni->user?->phone,
            $alumni->address_city,
            $alumni->address_province,
            $alumni->survey_status,
        ])->toArray();

        $headers = [
            'NIM', 'Nama Lengkap', 'Jenis Kelamin', 'Program Studi',
            'Angkatan', 'IPK', 'Predikat', 'Email', 'Telepon',
            'Kota', 'Provinsi', 'Status Survei',
        ];

        // Simpan ke private storage
        Excel::store(
            new \App\Exports\AlumniExport($headers, $rows),
            $this->path,
            'private'
        );
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateReportExport FAILED', [
            'type'  => $this->type,
            'path'  => $this->path,
            'error' => $exception->getMessage(),
        ]);
    }
}

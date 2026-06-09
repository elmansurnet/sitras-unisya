<?php

namespace App\Jobs;

use App\Models\Alumni;
use App\Models\AuditLog;
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
 *
 * Job untuk generate file Excel export di background queue.
 * Dikirim ke queue 'default'.
 *
 * Supported types: 'alumni', 'employer', 'survey_response' (lebih lanjut di sesi berikutnya).
 */
class GenerateReportExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function __construct(
        public readonly string $type,
        public readonly array  $filters,
        public readonly string $path,
        public readonly int    $userId,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        match ($this->type) {
            'alumni'   => $this->exportAlumni(),
            default    => Log::warning('GenerateReportExport: unknown type', ['type' => $this->type]),
        };
    }

    private function exportAlumni(): void
    {
        $query = Alumni::query()->with(['studyProgram.faculty', 'graduationYear', 'user']);

        if (!empty($this->filters['study_program_id'])) {
            $query->where('study_program_id', $this->filters['study_program_id']);
        }
        if (!empty($this->filters['graduation_year_id'])) {
            $query->where('graduation_year_id', $this->filters['graduation_year_id']);
        }
        if (!empty($this->filters['survey_status'])) {
            $query->where('survey_status', $this->filters['survey_status']);
        }
        if (!empty($this->filters['gender'])) {
            $query->where('gender', $this->filters['gender']);
        }

        $rows = $query->get()->map(fn(Alumni $a) => [
            'NIM'                  => $a->nim,
            'Nama Lengkap'         => $a->full_name,
            'Jenis Kelamin'        => $a->gender === 'L' ? 'Laki-laki' : 'Perempuan',
            'Program Studi'        => $a->studyProgram?->name,
            'Angkatan'             => $a->graduationYear?->year,
            'IPK'                  => $a->gpa,
            'Predikat'             => $a->graduation_predicate,
            'Email'                => $a->user?->email,
            'Telepon'              => $a->phone,
            'Kota'                 => $a->address_city,
            'Provinsi'             => $a->address_province,
            'Status Survei'        => $a->survey_status,
            'Tanggal Daftar'       => $a->created_at?->format('Y-m-d'),
        ]);

        // Tulis ke disk private menggunakan Laravel Excel (maatwebsite/excel)
        // Disk private sesuai aturan file upload: storage/app/private/
        Excel::store(
            new \App\Exports\AlumniExport($rows),
            $this->path,
            'private',
        );

        AuditLog::record(
            action   : 'export',
            module   : 'alumni',
            modelId  : null,
            oldValues: null,
            newValues: ['path' => $this->path, 'filters' => $this->filters, 'count' => $rows->count()],
            modelType: Alumni::class,
        );

        Log::info('GenerateReportExport: alumni export done', [
            'path'  => $this->path,
            'count' => $rows->count(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateReportExport: permanently failed', [
            'type'  => $this->type,
            'path'  => $this->path,
            'error' => $exception->getMessage(),
        ]);
    }
}

<?php

namespace App\Jobs;

use App\Models\Alumni;
use App\Services\ImportExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * GenerateReportExport
 *
 * Queue job untuk generate file export Alumni ke Excel secara async.
 * Queue: default
 * Retry: 3 kali, backoff: 30 detik
 *
 * Digunakan oleh AlumniService::export() dan di sesi 5A oleh ReportService.
 */
class GenerateReportExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30;

    /**
     * @param  string               $type     'alumni' | (future: 'employer', 'survey')
     * @param  array<string, mixed> $filters  Filter query yang sama seperti findWithFilters
     * @param  string               $path     Path relatif di storage/app/private/{path}
     * @param  int                  $userId   ID user yang merequest export
     */
    public function __construct(
        public readonly string $type,
        public readonly array  $filters,
        public readonly string $path,
        public readonly int    $userId,
    ) {}

    public function handle(ImportExportService $exportService): void
    {
        $storagePath = storage_path('app/private/' . $this->path);

        try {
            match ($this->type) {
                'alumni' => $this->exportAlumni($exportService, $storagePath),
                default  => throw new \InvalidArgumentException("Unknown export type: {$this->type}"),
            };

            Log::info('[GenerateReportExport] Export selesai', [
                'type'    => $this->type,
                'path'    => $this->path,
                'user_id' => $this->userId,
            ]);
        } catch (\Throwable $e) {
            Log::error('[GenerateReportExport] Export gagal', [
                'type'    => $this->type,
                'path'    => $this->path,
                'user_id' => $this->userId,
                'error'   => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // ─── Private export handlers ───────────────────────────────────────────

    private function exportAlumni(ImportExportService $exportService, string $storagePath): void
    {
        $query = Alumni::with([
            'studyProgram.faculty',
            'graduationYear',
            'user:id,email',
        ]);

        // Apply filters yang sama seperti AlumniRepository::applyFilters
        if (!empty($this->filters['search'])) {
            $search = '%' . $this->filters['search'] . '%';
            $query->where(fn ($q) =>
                $q->where('nim', 'like', $search)
                  ->orWhere('full_name', 'like', $search)
            );
        }
        if (!empty($this->filters['study_program_id'])) {
            $query->where('study_program_id', $this->filters['study_program_id']);
        }
        if (!empty($this->filters['graduation_year_id'])) {
            $query->where('graduation_year_id', $this->filters['graduation_year_id']);
        }
        if (isset($this->filters['is_active'])) {
            $query->where('is_active', (bool) $this->filters['is_active']);
        }

        $records = $query->orderBy('full_name')->get();

        $headers = [
            'nim'                  => 'NIM',
            'full_name'            => 'Nama Lengkap',
            'email_user'           => 'Email',
            'study_program_name'   => 'Program Studi',
            'faculty_name'         => 'Fakultas',
            'graduation_year'      => 'Tahun Lulus',
            'gpa'                  => 'IPK',
            'graduation_predicate' => 'Predikat',
            'address_city'         => 'Kota',
            'address_province'     => 'Provinsi',
            'phone'                => 'No. Telepon',
            'is_active'            => 'Status',
        ];

        $data = $records->map(fn (Alumni $a) => [
            'nim'                  => $a->nim,
            'full_name'            => $a->full_name,
            'email_user'           => $a->user?->email ?? '',
            'study_program_name'   => $a->studyProgram?->name ?? '',
            'faculty_name'         => $a->studyProgram?->faculty?->name ?? '',
            'graduation_year'      => $a->graduationYear?->year ?? '',
            'gpa'                  => $a->gpa,
            'graduation_predicate' => $a->graduation_predicate ?? '',
            'address_city'         => $a->address_city ?? '',
            'address_province'     => $a->address_province ?? '',
            'phone'                => $a->phone ?? '',
            'is_active'            => $a->is_active ? 'Aktif' : 'Nonaktif',
        ])->toArray();

        $exportService->exportToExcel($data, $headers, $storagePath);
    }
}

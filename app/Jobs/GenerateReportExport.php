<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Services\ImportExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateReportExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Antrian: default
     * Job ini digunakan untuk generate laporan Excel di background.
     */
    public string $queue = 'default';

    /**
     * Maksimal percobaan ulang jika job gagal.
     */
    public int $tries = 3;

    /**
     * Timeout eksekusi (detik).
     */
    public int $timeout = 300; // 5 menit

    /**
     * @param string $reportType  Jenis laporan: 'alumni' | 'employer' | 'survey'
     * @param array  $filters     Filter laporan (study_program_id, graduation_year_id, dll.)
     * @param int    $requestedBy ID user yang meminta export
     * @param string $notifyEmail Email untuk notifikasi selesai (opsional)
     */
    public function __construct(
        public readonly string $reportType,
        public readonly array  $filters,
        public readonly int    $requestedBy,
        public readonly string $notifyEmail = '',
    ) {}

    public function handle(ImportExportService $importExport): void
    {
        Log::info('[GenerateReportExport] Mulai generate laporan.', [
            'type'         => $this->reportType,
            'requested_by' => $this->requestedBy,
        ]);

        $filePath = match ($this->reportType) {
            'alumni'   => $this->generateAlumniReport($importExport),
            default    => throw new \InvalidArgumentException(
                "Report type '{$this->reportType}' belum didukung."
            ),
        };

        AuditLog::record(
            action: 'export',
            module: 'Report',
            modelId: null,
            newValues: [
                'report_type' => $this->reportType,
                'file_path'   => $filePath,
                'filters'     => $this->filters,
            ],
        );

        // Notifikasi via email jika diminta (implementasi di sesi 4A)
        // if ($this->notifyEmail) { ... }

        Log::info('[GenerateReportExport] Laporan selesai.', ['path' => $filePath]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[GenerateReportExport] Job gagal.', [
            'type'    => $this->reportType,
            'error'   => $e->getMessage(),
            'user_id' => $this->requestedBy,
        ]);
    }

    private function generateAlumniReport(ImportExportService $importExport): string
    {
        $alumni = \App\Models\Alumni::with([
            'studyProgram:id,name,code',
            'graduationYear:id,year,semester',
        ])
        ->when(!empty($this->filters['study_program_id']), fn ($q) =>
            $q->where('study_program_id', $this->filters['study_program_id'])
        )
        ->when(!empty($this->filters['graduation_year_id']), fn ($q) =>
            $q->where('graduation_year_id', $this->filters['graduation_year_id'])
        )
        ->when(!empty($this->filters['survey_status']), fn ($q) =>
            $q->where('survey_status', $this->filters['survey_status'])
        )
        ->get();

        return $importExport->exportExcel($alumni);
    }
}

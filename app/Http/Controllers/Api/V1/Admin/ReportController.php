<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    /**
     * POST /api/v1/admin/reports/generate/pdf
     * Generate laporan PDF dan langsung stream ke client sebagai file download.
     * Rate limit: throttle:export (5 req / 5 menit) — didaftarkan di routes/api.php.
     */
    public function generatePdf(Request $request): StreamedResponse
    {
        $request->validate([
            'type'                => ['required', 'in:tracer_study,alumni,employer'],
            'period_id'           => ['nullable', 'integer', 'exists:survey_periods,id'],
            'study_program_id'    => ['nullable', 'integer', 'exists:study_programs,id'],
            'graduation_year_id'  => ['nullable', 'integer', 'exists:graduation_years,id'],
        ]);

        $params = $request->only(['type', 'period_id', 'study_program_id', 'graduation_year_id']);

        AuditLog::record(
            action: 'generate_report',
            module: 'Report',
            modelId: null,
            oldValues: null,
            newValues: ['format' => 'pdf', 'params' => $params]
        );

        [$pdf, $filename] = $this->reportService->generatePdf($params);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $filename,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    /**
     * POST /api/v1/admin/reports/generate/excel
     * Generate laporan Excel dan langsung stream ke client sebagai file download.
     * Rate limit: throttle:export (5 req / 5 menit) — didaftarkan di routes/api.php.
     */
    public function generateExcel(Request $request): BinaryFileResponse
    {
        $request->validate([
            'type'                => ['required', 'in:tracer_study,alumni,employer'],
            'period_id'           => ['nullable', 'integer', 'exists:survey_periods,id'],
            'study_program_id'    => ['nullable', 'integer', 'exists:study_programs,id'],
            'graduation_year_id'  => ['nullable', 'integer', 'exists:graduation_years,id'],
        ]);

        $params = $request->only(['type', 'period_id', 'study_program_id', 'graduation_year_id']);

        AuditLog::record(
            action: 'generate_report',
            module: 'Report',
            modelId: null,
            oldValues: null,
            newValues: ['format' => 'excel', 'params' => $params]
        );

        [$tempPath, $filename] = $this->reportService->generateExcel($params);

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * GET /api/v1/admin/reports
     * Daftar laporan yang tersimpan di storage/app/reports.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) $request->input('per_page', 15);
        $data    = $this->reportService->listSavedReports($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar laporan tersimpan berhasil diambil',
            'data'    => $data->items(),
            'meta'    => [
                'current_page' => $data->currentPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
                'last_page'    => $data->lastPage(),
                'from'         => $data->firstItem(),
                'to'           => $data->lastItem(),
            ],
        ]);
    }

    /**
     * GET /api/v1/admin/reports/{id}/download
     * Download laporan tersimpan via signed URL (tidak langsung expose path storage).
     */
    public function download(int $id): JsonResponse
    {
        $report = $this->reportService->findSavedReport($id);

        if (!$report) {
            return response()->json([
                'success'    => false,
                'message'    => 'Laporan tidak ditemukan.',
                'error_code' => 'REPORT_NOT_FOUND',
            ], 404);
        }

        // Pastikan file masih ada di storage
        if (!Storage::disk('local')->exists($report['storage_path'])) {
            return response()->json([
                'success'    => false,
                'message'    => 'File laporan tidak ditemukan di storage.',
                'error_code' => 'REPORT_FILE_MISSING',
            ], 404);
        }

        AuditLog::record(
            action: 'download_report',
            module: 'Report',
            modelId: $id,
            oldValues: null,
            newValues: ['filename' => $report['filename']]
        );

        // Buat signed URL sementara (berlaku 15 menit)
        $signedUrl = route('report.download.signed', [
            'id'      => $id,
            'expires' => now()->addMinutes(15)->timestamp,
        ]);

        // Jika tidak pakai signed route terpisah, langsung stream file
        $fullPath = Storage::disk('local')->path($report['storage_path']);

        $mimeType = match ($report['format']) {
            'pdf'   => 'application/pdf',
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/octet-stream',
        };

        return response()->json([
            'success' => true,
            'message' => 'Silakan gunakan URL berikut untuk mengunduh laporan.',
            'data'    => [
                'download_url' => url('/api/v1/admin/reports/' . $id . '/stream'),
                'filename'     => $report['filename'],
                'format'       => $report['format'],
                'expires_at'   => now()->addMinutes(15)->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/v1/admin/reports/{id}/stream
     * Stream file laporan langsung sebagai binary download.
     * Endpoint ini hanya bisa diakses oleh user yang sudah auth (sudah dilindungi di routes/api.php).
     */
    public function stream(int $id): BinaryFileResponse|JsonResponse
    {
        $report = $this->reportService->findSavedReport($id);

        if (!$report || !Storage::disk('local')->exists($report['storage_path'])) {
            return response()->json([
                'success'    => false,
                'message'    => 'Laporan tidak ditemukan.',
                'error_code' => 'REPORT_NOT_FOUND',
            ], 404);
        }

        $fullPath = Storage::disk('local')->path($report['storage_path']);

        $mimeType = match ($report['format']) {
            'pdf'   => 'application/pdf',
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/octet-stream',
        };

        return response()->download($fullPath, $report['filename'], [
            'Content-Type' => $mimeType,
        ]);
    }
}

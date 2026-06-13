<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    /**
     * GET /api/v1/admin/dashboard/summary
     * Ringkasan dashboard: total alumni, employer, periode aktif, statistik ketenagakerjaan, aktivitas terbaru.
     */
    public function summary(Request $request): JsonResponse
    {
        $data = $this->dashboardService->getSummary();

        AuditLog::record(
            action: 'view',
            module: 'Dashboard',
            modelId: null,
            oldValues: null,
            newValues: null
        );

        return response()->json([
            'success' => true,
            'message' => 'Data ringkasan dashboard berhasil diambil',
            'data'    => $data,
        ]);
    }

    /**
     * GET /api/v1/admin/dashboard/employment-stats
     * Statistik ketenagakerjaan dengan filter opsional.
     *
     * Query params: period_id, graduation_year_id, study_program_id
     */
    public function employmentStats(Request $request): JsonResponse
    {
        $request->validate([
            'period_id'           => ['nullable', 'integer', 'exists:survey_periods,id'],
            'graduation_year_id'  => ['nullable', 'integer', 'exists:graduation_years,id'],
            'study_program_id'    => ['nullable', 'integer', 'exists:study_programs,id'],
        ]);

        $data = $this->dashboardService->getEmploymentStats(
            periodId:         $request->integer('period_id')          ?: null,
            graduationYearId: $request->integer('graduation_year_id') ?: null,
            studyProgramId:   $request->integer('study_program_id')   ?: null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Statistik ketenagakerjaan berhasil diambil',
            'data'    => $data,
        ]);
    }

    /**
     * GET /api/v1/admin/dashboard/alumni-map
     * Koordinat sebaran alumni per kota/provinsi.
     *
     * Query params: graduation_year_id, study_program_id
     */
    public function alumniMap(Request $request): JsonResponse
    {
        $request->validate([
            'graduation_year_id' => ['nullable', 'integer', 'exists:graduation_years,id'],
            'study_program_id'   => ['nullable', 'integer', 'exists:study_programs,id'],
        ]);

        $data = $this->dashboardService->getAlumniMap(
            graduationYearId: $request->integer('graduation_year_id') ?: null,
            studyProgramId:   $request->integer('study_program_id')   ?: null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Data peta sebaran alumni berhasil diambil',
            'data'    => $data,
        ]);
    }
}

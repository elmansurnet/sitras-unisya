<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\AlumniWorkHistory;
use App\Models\AuditLog;
use App\Models\Employer;
use App\Models\SurveyPeriod;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Ringkasan utama dashboard admin.
     * Sesuai 05_API.md §7.1
     */
    public function getSummary(): array
    {
        $totalAlumni    = Alumni::whereNull('deleted_at')->count();
        $totalEmployers = Employer::whereNull('deleted_at')->count();

        $activePeriod = SurveyPeriod::where('status', 'active')->latest()->first();

        $activePeriodData = null;
        if ($activePeriod) {
            $totalInPeriod = DB::table('alumni_survey_period')
                ->where('survey_period_id', $activePeriod->id)
                ->count();

            $completed = SurveyResponse::where('survey_period_id', $activePeriod->id)
                ->where('respondent_type', 'alumni')
                ->where('status', 'selesai')
                ->count();

            $pending = $totalInPeriod > 0 ? $totalInPeriod - $completed : 0;

            $responseRate = $totalInPeriod > 0
                ? round(($completed / $totalInPeriod) * 100, 1)
                : 0.0;

            $activePeriodData = [
                'id'                   => $activePeriod->id,
                'name'                 => $activePeriod->name,
                'response_rate'        => $responseRate,
                'responses_completed'  => $completed,
                'responses_pending'    => $pending,
                'end_date'             => $activePeriod->end_date,
            ];
        }

        // Statistik ketenagakerjaan dari work_histories (is_current=1)
        $employmentStats = $this->countEmploymentCategories();

        // Aktivitas terbaru dari audit_logs
        $recentActivities = AuditLog::with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($log) => [
                'action'      => $log->action,
                'description' => $this->buildActivityDescription($log),
                'created_at'  => $log->created_at
                    ? $log->created_at->setTimezone('Asia/Makassar')->toIso8601String()
                    : null,
            ])
            ->toArray();

        return [
            'total_alumni'          => $totalAlumni,
            'total_employers'       => $totalEmployers,
            'active_survey_period'  => $activePeriodData,
            'employment_stats'      => $employmentStats,
            'recent_activities'     => $recentActivities,
        ];
    }

    /**
     * Statistik ketenagakerjaan detail.
     * Sesuai 05_API.md §7.2
     *
     * @param int|null $periodId
     * @param int|null $graduationYearId
     * @param int|null $studyProgramId
     */
    public function getEmploymentStats(
        ?int $periodId = null,
        ?int $graduationYearId = null,
        ?int $studyProgramId = null
    ): array {
        // Base query: alumni yang sudah submit survei
        $alumniQuery = Alumni::whereNull('deleted_at')
            ->where('survey_status', 'selesai');

        if ($graduationYearId) {
            $alumniQuery->where('graduation_year_id', $graduationYearId);
        }
        if ($studyProgramId) {
            $alumniQuery->where('study_program_id', $studyProgramId);
        }
        if ($periodId) {
            $alumniQuery->whereHas('surveyPeriods', fn ($q) => $q->where('survey_periods.id', $periodId));
        }

        $alumniIds    = $alumniQuery->pluck('id');
        $totalAlumni  = $alumniIds->count();

        if ($totalAlumni === 0) {
            return $this->emptyEmploymentStats();
        }

        // Hanya ambil pekerjaan current dari alumni yang qualified
        $workQuery = AlumniWorkHistory::whereIn('alumni_id', $alumniIds)
            ->where('is_current', 1);

        $employedCount     = $workQuery->clone()->whereIn('employment_type', ['penuh_waktu', 'paruh_waktu', 'kontrak', 'magang'])->count();
        $selfEmployedCount = $workQuery->clone()->where('employment_type', 'wirausaha')->count();
        $employed          = $employedCount + $selfEmployedCount;

        $continuingStudy = SurveyResponse::whereIn('alumni_id', $alumniIds)
            ->where('status', 'selesai')
            ->whereHas('answers', fn ($q) => $q->whereNotNull('answer_value')
                ->where('answer_value', 'lanjut_studi'))
            ->count();

        $notWorking   = max(0, $totalAlumni - $employed - $continuingStudy);
        $employmentRate = round(($employed / $totalAlumni) * 100, 1);

        // Rata-rata waktu tunggu (dalam bulan)
        $avgWaiting = AlumniWorkHistory::whereIn('alumni_id', $alumniIds)
            ->where('is_current', 1)
            ->whereNotNull('waiting_time_months')
            ->avg('waiting_time_months');

        // Relevansi pekerjaan dengan bidang studi
        $relevantCount = AlumniWorkHistory::whereIn('alumni_id', $alumniIds)
            ->where('is_current', 1)
            ->where('is_relevant_to_study', 1)
            ->count();
        $relevanceBase  = AlumniWorkHistory::whereIn('alumni_id', $alumniIds)
            ->where('is_current', 1)
            ->whereNotNull('is_relevant_to_study')
            ->count();
        $relevanceRate = $relevanceBase > 0
            ? round(($relevantCount / $relevanceBase) * 100, 1)
            : 0.0;

        // Distribusi per sektor industri
        $byIndustry = AlumniWorkHistory::whereIn('alumni_id', $alumniIds)
            ->where('is_current', 1)
            ->whereNotNull('industry_sector')
            ->select('industry_sector', DB::raw('COUNT(*) as count'))
            ->groupBy('industry_sector')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'sector'     => $row->industry_sector,
                'count'      => (int) $row->count,
                'percentage' => $employed > 0
                    ? round(($row->count / $employed) * 100, 1)
                    : 0.0,
            ])
            ->toArray();

        // Distribusi per rentang gaji
        $bySalary = AlumniWorkHistory::whereIn('alumni_id', $alumniIds)
            ->where('is_current', 1)
            ->whereNotNull('monthly_salary_range')
            ->select('monthly_salary_range', DB::raw('COUNT(*) as count'))
            ->groupBy('monthly_salary_range')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'range'      => $row->monthly_salary_range,
                'count'      => (int) $row->count,
                'percentage' => $employed > 0
                    ? round(($row->count / $employed) * 100, 1)
                    : 0.0,
            ])
            ->toArray();

        // Per tahun kelulusan
        $byGradYear = Alumni::whereNull('deleted_at')
            ->whereIn('id', $alumniIds)
            ->with('graduationYear:id,year,academic_year')
            ->select('graduation_year_id', DB::raw('COUNT(*) as total'))
            ->groupBy('graduation_year_id')
            ->get()
            ->map(function ($row) use ($alumniIds) {
                $yearAlumniIds = Alumni::where('graduation_year_id', $row->graduation_year_id)
                    ->whereIn('id', $alumniIds)
                    ->pluck('id');

                $employedInYear = AlumniWorkHistory::whereIn('alumni_id', $yearAlumniIds)
                    ->where('is_current', 1)
                    ->whereIn('employment_type', ['penuh_waktu', 'paruh_waktu', 'kontrak', 'magang', 'wirausaha'])
                    ->count();

                return [
                    'year'          => $row->graduationYear->year ?? null,
                    'academic_year' => $row->graduationYear->academic_year ?? null,
                    'employed'      => $employedInYear,
                    'total'         => (int) $row->total,
                    'rate'          => $row->total > 0
                        ? round(($employedInYear / $row->total) * 100, 1)
                        : 0.0,
                ];
            })
            ->toArray();

        // Per program studi
        $byStudyProgram = Alumni::whereNull('deleted_at')
            ->whereIn('id', $alumniIds)
            ->with('studyProgram:id,name')
            ->select('study_program_id', DB::raw('COUNT(*) as total'))
            ->groupBy('study_program_id')
            ->get()
            ->map(function ($row) use ($alumniIds) {
                $progAlumniIds = Alumni::where('study_program_id', $row->study_program_id)
                    ->whereIn('id', $alumniIds)
                    ->pluck('id');

                $employedInProg = AlumniWorkHistory::whereIn('alumni_id', $progAlumniIds)
                    ->where('is_current', 1)
                    ->whereIn('employment_type', ['penuh_waktu', 'paruh_waktu', 'kontrak', 'magang', 'wirausaha'])
                    ->count();

                return [
                    'id'       => $row->study_program_id,
                    'name'     => $row->studyProgram->name ?? null,
                    'employed' => $employedInProg,
                    'total'    => (int) $row->total,
                    'rate'     => $row->total > 0
                        ? round(($employedInProg / $row->total) * 100, 1)
                        : 0.0,
                ];
            })
            ->toArray();

        return [
            'employment_rate'          => $employmentRate,
            'average_waiting_months'   => $avgWaiting !== null ? round((float) $avgWaiting, 1) : null,
            'relevance_rate'           => $relevanceRate,
            'by_industry'              => $byIndustry,
            'by_salary_range'          => $bySalary,
            'by_graduation_year'       => $byGradYear,
            'by_study_program'         => $byStudyProgram,
        ];
    }

    /**
     * Data sebaran alumni berdasarkan koordinat untuk peta.
     * Sesuai 05_API.md §7.3
     *
     * @param int|null $graduationYearId
     * @param int|null $studyProgramId
     */
    public function getAlumniMap(?int $graduationYearId = null, ?int $studyProgramId = null): array
    {
        $query = Alumni::whereNull('deleted_at')
            ->whereNotNull('address_province')
            ->select(
                'address_province',
                'address_city',
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(address_latitude) as lat'),
                DB::raw('AVG(address_longitude) as lng')
            )
            ->groupBy('address_province', 'address_city')
            ->orderByDesc('count');

        if ($graduationYearId) {
            $query->where('graduation_year_id', $graduationYearId);
        }
        if ($studyProgramId) {
            $query->where('study_program_id', $studyProgramId);
        }

        return $query->get()
            ->map(fn ($row) => [
                'province'    => $row->address_province,
                'city'        => $row->address_city,
                'count'       => (int) $row->count,
                'coordinates' => [
                    'lat' => $row->lat !== null ? round((float) $row->lat, 7) : null,
                    'lng' => $row->lng !== null ? round((float) $row->lng, 7) : null,
                ],
            ])
            ->toArray();
    }

    // ---------------------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------------------

    private function countEmploymentCategories(): array
    {
        $allAlumniIds = Alumni::whereNull('deleted_at')->pluck('id');

        $employed = AlumniWorkHistory::whereIn('alumni_id', $allAlumniIds)
            ->where('is_current', 1)
            ->whereIn('employment_type', ['penuh_waktu', 'paruh_waktu', 'kontrak', 'magang'])
            ->distinct('alumni_id')
            ->count('alumni_id');

        $selfEmployed = AlumniWorkHistory::whereIn('alumni_id', $allAlumniIds)
            ->where('is_current', 1)
            ->where('employment_type', 'wirausaha')
            ->distinct('alumni_id')
            ->count('alumni_id');

        return [
            'employed'          => $employed,
            'self_employed'     => $selfEmployed,
            'continuing_study'  => 0,  // Membutuhkan jawaban survei spesifik; placeholder aman
            'not_working'       => max(0, $allAlumniIds->count() - $employed - $selfEmployed),
        ];
    }

    private function buildActivityDescription(AuditLog $log): string
    {
        $actor   = $log->user->name ?? 'Sistem';
        $action  = $log->action;
        $module  = $log->module;

        $map = [
            'create' => 'menambahkan',
            'update' => 'memperbarui',
            'delete' => 'menghapus',
            'login'  => 'login ke',
            'logout' => 'logout dari',
            'submit_survey' => 'menyelesaikan survei di',
            'import' => 'mengimpor data ke',
            'export' => 'mengekspor data dari',
        ];

        $verb = $map[$action] ?? $action;
        return "{$actor} {$verb} {$module}";
    }

    private function emptyEmploymentStats(): array
    {
        return [
            'employment_rate'          => 0.0,
            'average_waiting_months'   => null,
            'relevance_rate'           => 0.0,
            'by_industry'              => [],
            'by_salary_range'          => [],
            'by_graduation_year'       => [],
            'by_study_program'         => [],
        ];
    }
}

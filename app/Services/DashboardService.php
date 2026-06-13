<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\AuditLog;
use App\Models\Employer;
use App\Models\SurveyPeriod;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Ringkasan utama dashboard.
     * Sesuai 05_API.md §7.1
     *
     * @return array
     */
    public function getSummary(): array
    {
        $cacheKey = 'dashboard:summary';

        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            $totalAlumni   = Alumni::whereNull('deleted_at')->count();
            $totalEmployer = Employer::whereNull('deleted_at')->count();

            /** @var SurveyPeriod|null $activePeriod */
            $activePeriod = SurveyPeriod::where('status', 'active')->latest('start_date')->first();

            $activePeriodData = null;
            if ($activePeriod) {
                $totalInPeriod = DB::table('alumni_survey_period')
                    ->where('survey_period_id', $activePeriod->id)
                    ->count();

                $completed = SurveyResponse::where('survey_period_id', $activePeriod->id)
                    ->where('respondent_type', 'alumni')
                    ->where('status', 'selesai')
                    ->count();

                $pending = $totalInPeriod - $completed;

                $activePeriodData = [
                    'id'                   => $activePeriod->id,
                    'name'                 => $activePeriod->name,
                    'response_rate'        => $totalInPeriod > 0
                        ? round(($completed / $totalInPeriod) * 100, 1)
                        : 0.0,
                    'responses_completed'  => $completed,
                    'responses_pending'    => max($pending, 0),
                    'end_date'             => $activePeriod->end_date,
                ];
            }

            // Statistik ketenagakerjaan dari riwayat kerja terkini
            $employmentStats = $this->buildEmploymentBreakdown();

            // 10 aktivitas terbaru dari audit_log
            $recentActivities = AuditLog::with('user:id,name')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(['action', 'module', 'user_id', 'created_at'])
                ->map(fn ($log) => [
                    'action'      => $log->action,
                    'description' => ($log->user?->name ?? 'Sistem') . ' — ' . $log->module,
                    'created_at'  => $log->created_at?->setTimezone('Asia/Makassar')->toIso8601String(),
                ])
                ->toArray();

            return [
                'total_alumni'          => (int) $totalAlumni,
                'total_employers'       => (int) $totalEmployer,
                'active_survey_period'  => $activePeriodData,
                'employment_stats'      => $employmentStats,
                'recent_activities'     => $recentActivities,
            ];
        });
    }

    /**
     * Statistik ketenagakerjaan lengkap.
     * Sesuai 05_API.md §7.2
     *
     * @param  int|null  $periodId
     * @param  int|null  $graduationYearId
     * @param  int|null  $studyProgramId
     * @return array
     */
    public function getEmploymentStats(
        ?int $periodId = null,
        ?int $graduationYearId = null,
        ?int $studyProgramId = null
    ): array {
        $cacheKey = "dashboard:employment:{$periodId}:{$graduationYearId}:{$studyProgramId}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use (
            $periodId,
            $graduationYearId,
            $studyProgramId
        ) {
            // Base query alumni dengan filter
            $alumniQuery = Alumni::query()
                ->whereNull('deleted_at')
                ->when($graduationYearId, fn ($q) => $q->where('graduation_year_id', $graduationYearId))
                ->when($studyProgramId, fn ($q) => $q->where('study_program_id', $studyProgramId));

            // Jika ada filter periode: ambil alumni yang ada di pivot alumni_survey_period
            if ($periodId) {
                $alumniInPeriod = DB::table('alumni_survey_period')
                    ->where('survey_period_id', $periodId)
                    ->pluck('alumni_id');
                $alumniQuery->whereIn('id', $alumniInPeriod);
            }

            $alumniIds    = $alumniQuery->pluck('id');
            $totalAlumni  = $alumniIds->count();

            // Alumni yang punya riwayat kerja terkini (is_current = 1)
            $currentJobs = DB::table('alumni_work_histories')
                ->whereIn('alumni_id', $alumniIds)
                ->where('is_current', 1)
                ->get();

            $employed        = $currentJobs->whereNotIn('employment_type', ['wirausaha'])->count();
            $selfEmployed    = $currentJobs->where('employment_type', 'wirausaha')->count();

            // Alumni yang masih melanjutkan studi (tidak punya pekerjaan, tapi ada di survey responses)
            // Pendekatan: alumni selesai survei tapi tidak punya current_job
            $alumniWithJob   = $currentJobs->pluck('alumni_id')->unique();
            $alumniNoJob     = $alumniIds->diff($alumniWithJob);

            // Dari alumni tanpa pekerjaan, cek apakah melanjutkan studi
            // (tidak ada data langsung di DB; gunakan estimasi dari alumni yang punya survey response selesai)
            $continuingStudy = 0;
            $notWorking      = (int) $alumniNoJob->count();

            $employmentRate = $totalAlumni > 0
                ? round((($employed + $selfEmployed) / $totalAlumni) * 100, 1)
                : 0.0;

            // Rata-rata bulan tunggu
            $avgWaiting = DB::table('alumni_work_histories')
                ->whereIn('alumni_id', $alumniIds)
                ->whereNotNull('waiting_time_months')
                ->avg('waiting_time_months');

            // Relevansi bidang studi
            $relevantCount = DB::table('alumni_work_histories')
                ->whereIn('alumni_id', $alumniIds)
                ->where('is_current', 1)
                ->where('is_relevant_to_study', 1)
                ->count();
            $totalJobCount  = $currentJobs->count();
            $relevanceRate  = $totalJobCount > 0
                ? round(($relevantCount / $totalJobCount) * 100, 1)
                : 0.0;

            // By industry
            $byIndustry = DB::table('alumni_work_histories')
                ->whereIn('alumni_id', $alumniIds)
                ->where('is_current', 1)
                ->whereNotNull('industry_sector')
                ->select('industry_sector', DB::raw('COUNT(*) as count'))
                ->groupBy('industry_sector')
                ->orderByDesc('count')
                ->get()
                ->map(fn ($row) => [
                    'sector'     => $row->industry_sector,
                    'count'      => (int) $row->count,
                    'percentage' => $totalJobCount > 0
                        ? round(($row->count / $totalJobCount) * 100, 1)
                        : 0.0,
                ])
                ->toArray();

            // By salary range
            $bySalary = DB::table('alumni_work_histories')
                ->whereIn('alumni_id', $alumniIds)
                ->where('is_current', 1)
                ->whereNotNull('monthly_salary_range')
                ->select('monthly_salary_range', DB::raw('COUNT(*) as count'))
                ->groupBy('monthly_salary_range')
                ->orderByDesc('count')
                ->get()
                ->map(fn ($row) => [
                    'range'      => $row->monthly_salary_range,
                    'count'      => (int) $row->count,
                    'percentage' => $totalJobCount > 0
                        ? round(($row->count / $totalJobCount) * 100, 1)
                        : 0.0,
                ])
                ->toArray();

            // By graduation year
            $byGraduationYear = Alumni::query()
                ->whereNull('deleted_at')
                ->whereIn('id', $alumniIds)
                ->join('graduation_years', 'alumni.graduation_year_id', '=', 'graduation_years.id')
                ->select(
                    'graduation_years.id',
                    'graduation_years.year',
                    'graduation_years.academic_year',
                    DB::raw('COUNT(alumni.id) as total')
                )
                ->groupBy('graduation_years.id', 'graduation_years.year', 'graduation_years.academic_year')
                ->orderByDesc('graduation_years.year')
                ->get()
                ->map(function ($row) use ($alumniIds) {
                    $yearAlumniIds = Alumni::whereNull('deleted_at')
                        ->where('graduation_year_id', $row->id)
                        ->whereIn('id', $alumniIds)
                        ->pluck('id');

                    $employedCount = DB::table('alumni_work_histories')
                        ->whereIn('alumni_id', $yearAlumniIds)
                        ->where('is_current', 1)
                        ->distinct('alumni_id')
                        ->count('alumni_id');

                    return [
                        'year'          => (int) $row->year,
                        'academic_year' => $row->academic_year,
                        'employed'      => (int) $employedCount,
                        'total'         => (int) $row->total,
                        'rate'          => $row->total > 0
                            ? round(($employedCount / $row->total) * 100, 1)
                            : 0.0,
                    ];
                })
                ->toArray();

            // By study program
            $byStudyProgram = Alumni::query()
                ->whereNull('deleted_at')
                ->whereIn('id', $alumniIds)
                ->join('study_programs', 'alumni.study_program_id', '=', 'study_programs.id')
                ->select(
                    'study_programs.id',
                    'study_programs.name',
                    DB::raw('COUNT(alumni.id) as total')
                )
                ->groupBy('study_programs.id', 'study_programs.name')
                ->orderByDesc('total')
                ->get()
                ->map(function ($row) use ($alumniIds) {
                    $progAlumniIds = Alumni::whereNull('deleted_at')
                        ->where('study_program_id', $row->id)
                        ->whereIn('id', $alumniIds)
                        ->pluck('id');

                    $employedCount = DB::table('alumni_work_histories')
                        ->whereIn('alumni_id', $progAlumniIds)
                        ->where('is_current', 1)
                        ->distinct('alumni_id')
                        ->count('alumni_id');

                    return [
                        'id'       => (int) $row->id,
                        'name'     => $row->name,
                        'employed' => (int) $employedCount,
                        'total'    => (int) $row->total,
                        'rate'     => $row->total > 0
                            ? round(($employedCount / $row->total) * 100, 1)
                            : 0.0,
                    ];
                })
                ->toArray();

            return [
                'employment_rate'          => (float) $employmentRate,
                'average_waiting_months'   => $avgWaiting !== null ? (float) round($avgWaiting, 1) : null,
                'relevance_rate'           => (float) $relevanceRate,
                'by_industry'              => $byIndustry,
                'by_salary_range'          => $bySalary,
                'by_graduation_year'       => $byGraduationYear,
                'by_study_program'         => $byStudyProgram,
            ];
        });
    }

    /**
     * Data peta sebaran alumni.
     * Sesuai 05_API.md §7.3
     *
     * @param  int|null  $graduationYearId
     * @param  int|null  $studyProgramId
     * @return array
     */
    public function getAlumniMap(
        ?int $graduationYearId = null,
        ?int $studyProgramId = null
    ): array {
        $cacheKey = "dashboard:alumni-map:{$graduationYearId}:{$studyProgramId}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use (
            $graduationYearId,
            $studyProgramId
        ) {
            return Alumni::query()
                ->whereNull('deleted_at')
                ->whereNotNull('address_province')
                ->whereNotNull('address_latitude')
                ->whereNotNull('address_longitude')
                ->when($graduationYearId, fn ($q) => $q->where('graduation_year_id', $graduationYearId))
                ->when($studyProgramId, fn ($q) => $q->where('study_program_id', $studyProgramId))
                ->select(
                    'address_province',
                    'address_city',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('AVG(address_latitude)  as lat'),
                    DB::raw('AVG(address_longitude) as lng')
                )
                ->groupBy('address_province', 'address_city')
                ->orderByDesc('count')
                ->get()
                ->map(fn ($row) => [
                    'province'    => $row->address_province,
                    'city'        => $row->address_city,
                    'count'       => (int) $row->count,
                    'coordinates' => [
                        'lat' => round((float) $row->lat, 7),
                        'lng' => round((float) $row->lng, 7),
                    ],
                ])
                ->toArray();
        });
    }

    // ──────────────────────────────────────────────────────────
    //  Private helpers
    // ──────────────────────────────────────────────────────────

    /**
     * Breakdown ringkas employed/self_employed/continuing_study/not_working
     * untuk summary dashboard (tidak menerima filter — scope global).
     */
    private function buildEmploymentBreakdown(): array
    {
        $allAlumniIds = Alumni::whereNull('deleted_at')->pluck('id');

        $currentJobs = DB::table('alumni_work_histories')
            ->whereIn('alumni_id', $allAlumniIds)
            ->where('is_current', 1)
            ->select('alumni_id', 'employment_type')
            ->get();

        $employed     = $currentJobs->whereNotIn('employment_type', ['wirausaha'])->count();
        $selfEmployed = $currentJobs->where('employment_type', 'wirausaha')->count();

        $alumniWithJob = $currentJobs->pluck('alumni_id')->unique();
        $alumniNoJob   = $allAlumniIds->diff($alumniWithJob)->count();

        return [
            'employed'          => (int) $employed,
            'self_employed'     => (int) $selfEmployed,
            'continuing_study'  => 0,            // tidak ada kolom khusus di DB saat ini
            'not_working'       => (int) $alumniNoJob,
        ];
    }
}

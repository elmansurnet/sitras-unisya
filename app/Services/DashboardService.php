<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\AlumniWorkHistory;
use App\Models\AuditLog;
use App\Models\Employer;
use App\Models\SurveyPeriod;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Ringkasan dashboard utama (summary card).
     * Cache 5 menit — refreshed setiap ada submit survei via SurveyResponseObserver.
     *
     * @return array
     */
    public function getSummary(): array
    {
        return Cache::remember('dashboard:summary', 300, function () {
            $totalAlumni   = Alumni::whereNull('deleted_at')->count();
            $totalEmployer = Employer::whereNull('deleted_at')->count();

            $activePeriod = SurveyPeriod::where('status', 'active')
                ->withCount([
                    'surveyResponses as responses_completed' => fn ($q) => $q->where('status', 'selesai'),
                    'surveyResponses as responses_pending'   => fn ($q) => $q->where('status', 'draft'),
                ])
                ->first();

            $activePeriodData = null;
            if ($activePeriod) {
                $totalInPeriod = $activePeriod->responses_completed + $activePeriod->responses_pending;
                $activePeriodData = [
                    'id'                  => $activePeriod->id,
                    'name'                => $activePeriod->name,
                    'response_rate'       => $totalInPeriod > 0
                        ? round(($activePeriod->responses_completed / $totalInPeriod) * 100, 1)
                        : 0.0,
                    'responses_completed' => (int) $activePeriod->responses_completed,
                    'responses_pending'   => (int) $activePeriod->responses_pending,
                    'end_date'            => $activePeriod->end_date,
                ];
            }

            // Statistik status pekerjaan dari riwayat kerja terbaru alumni
            $employmentStats = $this->buildEmploymentStatusCounts();

            // Aktivitas terbaru (10 item) dari audit_logs
            $recentActivities = AuditLog::with('user:id,name,role')
                ->whereIn('action', ['submit_survey', 'create', 'update', 'import'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(fn ($log) => [
                    'action'      => $log->action,
                    'description' => $log->new_values['description'] ?? "{$log->user?->name} melakukan {$log->action} pada {$log->module}",
                    'created_at'  => $log->created_at?->setTimezone('Asia/Makassar')->toIso8601String(),
                ])
                ->toArray();

            return [
                'total_alumni'         => (int) $totalAlumni,
                'total_employers'      => (int) $totalEmployer,
                'active_survey_period' => $activePeriodData,
                'employment_stats'     => $employmentStats,
                'recent_activities'    => $recentActivities,
            ];
        });
    }

    /**
     * Statistik ketenagakerjaan alumni.
     * Cache 15 menit per kombinasi filter.
     *
     * @param  array{period_id?: int|null, graduation_year_id?: int|null, study_program_id?: int|null}  $filters
     * @return array
     */
    public function getEmploymentStats(array $filters = []): array
    {
        $cacheKey = 'dashboard:employment_stats:' . md5(serialize($filters));

        return Cache::remember($cacheKey, 900, function () use ($filters) {
            // Base alumni query dengan filter
            $alumniQuery = Alumni::whereNull('deleted_at');

            if (!empty($filters['graduation_year_id'])) {
                $alumniQuery->where('graduation_year_id', $filters['graduation_year_id']);
            }

            if (!empty($filters['study_program_id'])) {
                $alumniQuery->where('study_program_id', $filters['study_program_id']);
            }

            if (!empty($filters['period_id'])) {
                $alumniQuery->whereHas('surveyPeriods', fn ($q) => $q->where('survey_periods.id', $filters['period_id']));
            }

            $totalAlumni = $alumniQuery->count();

            if ($totalAlumni === 0) {
                return $this->emptyEmploymentStats();
            }

            // Alumni yang memiliki pekerjaan aktif
            $alumniIds     = (clone $alumniQuery)->pluck('id');
            $employedIds   = AlumniWorkHistory::whereIn('alumni_id', $alumniIds)
                ->where('is_current', 1)
                ->distinct('alumni_id')
                ->pluck('alumni_id');

            $employmentRate = round(($employedIds->count() / $totalAlumni) * 100, 1);

            // Rata-rata waktu tunggu (hanya yang punya data)
            $avgWaiting = AlumniWorkHistory::whereIn('alumni_id', $alumniIds)
                ->where('is_current', 1)
                ->whereNotNull('waiting_time_months')
                ->avg('waiting_time_months');

            // Tingkat relevansi pekerjaan dengan bidang studi
            $relevantCount = AlumniWorkHistory::whereIn('alumni_id', $alumniIds)
                ->where('is_current', 1)
                ->where('is_relevant_to_study', 1)
                ->count();
            $totalWithJob  = AlumniWorkHistory::whereIn('alumni_id', $alumniIds)
                ->where('is_current', 1)
                ->whereNotNull('is_relevant_to_study')
                ->count();
            $relevanceRate = $totalWithJob > 0
                ? round(($relevantCount / $totalWithJob) * 100, 1)
                : 0.0;

            // Breakdown by industry sektor (top 10)
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
                    'percentage' => round(($row->count / max($employedIds->count(), 1)) * 100, 1),
                ])
                ->toArray();

            // Breakdown by salary range
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
                    'percentage' => round(($row->count / max($employedIds->count(), 1)) * 100, 1),
                ])
                ->toArray();

            // Breakdown by graduation year
            $byGraduationYear = (clone $alumniQuery)
                ->select(
                    'graduation_year_id',
                    DB::raw('COUNT(*) as total')
                )
                ->with('graduationYear:id,year,academic_year')
                ->groupBy('graduation_year_id')
                ->get()
                ->map(function ($row) use ($alumniIds) {
                    $employedInYear = AlumniWorkHistory::whereIn('alumni_id', function ($q) use ($row) {
                        $q->select('id')
                            ->from('alumni')
                            ->where('graduation_year_id', $row->graduation_year_id)
                            ->whereNull('deleted_at');
                    })
                        ->where('is_current', 1)
                        ->distinct('alumni_id')
                        ->count('alumni_id');

                    return [
                        'year'          => $row->graduationYear?->year,
                        'academic_year' => $row->graduationYear?->academic_year,
                        'employed'      => (int) $employedInYear,
                        'total'         => (int) $row->total,
                        'rate'          => $row->total > 0
                            ? round(($employedInYear / $row->total) * 100, 1)
                            : 0.0,
                    ];
                })
                ->toArray();

            // Breakdown by study program
            $byStudyProgram = (clone $alumniQuery)
                ->select(
                    'study_program_id',
                    DB::raw('COUNT(*) as total')
                )
                ->with('studyProgram:id,name')
                ->groupBy('study_program_id')
                ->get()
                ->map(function ($row) {
                    $employedInProdi = AlumniWorkHistory::whereIn('alumni_id', function ($q) use ($row) {
                        $q->select('id')
                            ->from('alumni')
                            ->where('study_program_id', $row->study_program_id)
                            ->whereNull('deleted_at');
                    })
                        ->where('is_current', 1)
                        ->distinct('alumni_id')
                        ->count('alumni_id');

                    return [
                        'id'       => $row->study_program_id,
                        'name'     => $row->studyProgram?->name,
                        'employed' => (int) $employedInProdi,
                        'total'    => (int) $row->total,
                        'rate'     => $row->total > 0
                            ? round(($employedInProdi / $row->total) * 100, 1)
                            : 0.0,
                    ];
                })
                ->toArray();

            return [
                'employment_rate'        => $employmentRate,
                'average_waiting_months' => $avgWaiting !== null ? round((float) $avgWaiting, 1) : null,
                'relevance_rate'         => $relevanceRate,
                'by_industry'            => $byIndustry,
                'by_salary_range'        => $bySalary,
                'by_graduation_year'     => $byGraduationYear,
                'by_study_program'       => $byStudyProgram,
            ];
        });
    }

    /**
     * Data sebaran alumni berdasarkan koordinat alamat (untuk Leaflet map).
     * Cache 30 menit — koordinat jarang berubah.
     *
     * @param  array{graduation_year_id?: int|null, study_program_id?: int|null}  $filters
     * @return array
     */
    public function getAlumniMap(array $filters = []): array
    {
        $cacheKey = 'dashboard:alumni_map:' . md5(serialize($filters));

        return Cache::remember($cacheKey, 1800, function () use ($filters) {
            $query = Alumni::whereNull('deleted_at')
                ->whereNotNull('address_latitude')
                ->whereNotNull('address_longitude')
                ->whereNotNull('address_province');

            if (!empty($filters['graduation_year_id'])) {
                $query->where('graduation_year_id', $filters['graduation_year_id']);
            }

            if (!empty($filters['study_program_id'])) {
                $query->where('study_program_id', $filters['study_program_id']);
            }

            // Group by province + city, ambil representatif koordinat (avg)
            return $query
                ->select(
                    'address_province',
                    'address_city',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('AVG(address_latitude) as lat'),
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

    /**
     * Invalidate semua cache dashboard (dipanggil dari SurveyResponseObserver setelah submit).
     */
    public function flushSummaryCache(): void
    {
        Cache::forget('dashboard:summary');
    }

    /**
     * Hitung jumlah alumni per status ketenagakerjaan.
     * Digunakan di getSummary() untuk employment_stats card.
     */
    private function buildEmploymentStatusCounts(): array
    {
        // Alumni dengan pekerjaan aktif = employed
        $employed = AlumniWorkHistory::where('is_current', 1)
            ->where('employment_type', '!=', 'wirausaha')
            ->distinct('alumni_id')
            ->count('alumni_id');

        $selfEmployed = AlumniWorkHistory::where('is_current', 1)
            ->where('employment_type', 'wirausaha')
            ->distinct('alumni_id')
            ->count('alumni_id');

        // Alumni yang melanjutkan studi: tidak ada flag eksplisit di DB,
        // diasumsikan dari survey_answer terkait (fallback: 0 jika belum ada data survei)
        $continuingStudy = 0;

        $totalAlumni  = Alumni::whereNull('deleted_at')->count();
        $allWorking   = AlumniWorkHistory::where('is_current', 1)->distinct('alumni_id')->count('alumni_id');
        $notWorking   = max(0, $totalAlumni - $allWorking - $continuingStudy);

        return [
            'employed'           => (int) $employed,
            'self_employed'      => (int) $selfEmployed,
            'continuing_study'   => (int) $continuingStudy,
            'not_working'        => (int) $notWorking,
        ];
    }

    /**
     * Kembalikan struktur kosong ketika tidak ada alumni yang cocok dengan filter.
     */
    private function emptyEmploymentStats(): array
    {
        return [
            'employment_rate'        => 0.0,
            'average_waiting_months' => null,
            'relevance_rate'         => 0.0,
            'by_industry'            => [],
            'by_salary_range'        => [],
            'by_graduation_year'     => [],
            'by_study_program'       => [],
        ];
    }
}

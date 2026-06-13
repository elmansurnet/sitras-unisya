<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\AlumniWorkHistory;
use App\Models\AuditLog;
use App\Models\Employer;
use App\Models\SurveyPeriod;
use App\Models\SurveyResponse;
use App\Models\SystemSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TracerStudyExport;

class ReportService
{
    /**
     * Generate laporan PDF, simpan ke storage, kembalikan tuple [Pdf, filename].
     * Sesuai 05_API.md §8.1
     *
     * @param  array  $params  ['type', 'period_id', 'study_program_id', 'graduation_year_id']
     * @return array{0: \Barryvdh\DomPDF\PDF, 1: string}
     */
    public function generatePdf(array $params): array
    {
        $type             = $params['type'];
        $periodId         = $params['period_id'] ?? null;
        $studyProgramId   = $params['study_program_id'] ?? null;
        $graduationYearId = $params['graduation_year_id'] ?? null;

        $data     = $this->collectReportData($type, $periodId, $studyProgramId, $graduationYearId);
        $view     = $this->resolveView($type);
        $filename = $this->buildFilename($type, $periodId, 'pdf');

        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => false,
                'defaultFont'          => 'DejaVu Sans',
            ]);

        Storage::put('reports/' . $filename, $pdf->output());

        return [$pdf, $filename];
    }

    /**
     * Generate laporan Excel, simpan ke temp, kembalikan tuple [tempPath, filename].
     * Sesuai 05_API.md §8.2
     *
     * @param  array  $params
     * @return array{0: string, 1: string}
     */
    public function generateExcel(array $params): array
    {
        $type             = $params['type'];
        $periodId         = $params['period_id'] ?? null;
        $studyProgramId   = $params['study_program_id'] ?? null;
        $graduationYearId = $params['graduation_year_id'] ?? null;

        $data     = $this->collectReportData($type, $periodId, $studyProgramId, $graduationYearId);
        $filename = $this->buildFilename($type, $periodId, 'xlsx');
        $tempPath = storage_path('app/temp/' . $filename);

        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        Excel::store(
            new TracerStudyExport($data),
            'temp/' . $filename,
            null,
            \Maatwebsite\Excel\Excel::XLSX
        );

        return [$tempPath, $filename];
    }

    /**
     * Daftar laporan tersimpan di storage/app/reports dengan paginasi.
     * Menyertakan generated_by dari AuditLog.
     * Sesuai 05_API.md §8.3
     *
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function listSavedReports(int $perPage = 15): LengthAwarePaginator
    {
        $files = Storage::files('reports');

        $items = collect($files)
            ->map(function (string $path, int $index) {
                $filename = basename($path);
                $format   = str_ends_with($filename, '.pdf') ? 'pdf' : 'excel';

                // Ekstrak type dari nama file: tracer-study-..., alumni-..., employer-...
                $type = 'tracer_study';
                if (str_starts_with($filename, 'employer')) {
                    $type = 'employer';
                } elseif (str_starts_with($filename, 'alumni')) {
                    $type = 'alumni';
                }

                $modified = Storage::lastModified($path);

                return [
                    'id'            => $index + 1,
                    'filename'      => $filename,
                    'format'        => $format,
                    'type'          => $type,
                    'file_size_kb'  => round(Storage::size($path) / 1024, 1),
                    'storage_path'  => $path,
                    'created_at'    => Carbon::createFromTimestamp($modified)
                        ->setTimezone('Asia/Makassar')
                        ->toIso8601String(),
                    'generated_by'  => null, // akan di-enrich di bawah
                ];
            })
            ->sortByDesc('created_at')
            ->values();

        // Enrich generated_by dari AuditLog (1 query bulk, bukan N+1)
        $logs = AuditLog::where('module', 'Report')
            ->where('action', 'generate_report')
            ->with('user:id,name')
            ->latest()
            ->get()
            ->keyBy(fn ($log) => $log->created_at->format('YmdH')); // kunci per jam

        $enriched = $items->map(function ($item) use ($logs) {
            $hourKey = Carbon::parse($item['created_at'])->format('YmdH');
            $log     = $logs->get($hourKey);

            $item['generated_by'] = $log
                ? ['id' => $log->user_id, 'name' => optional($log->user)->name]
                : null;

            return $item;
        });

        // Manual pagination dari Collection
        $page    = request()->input('page', 1);
        $sliced  = $enriched->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $sliced,
            $enriched->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Cari metadata laporan tersimpan berdasarkan urutan index (id).
     * Digunakan oleh ReportController::download() dan stream().
     * Sesuai 05_API.md §8.4
     *
     * @param  int  $id  index 1-based dari listSavedReports
     * @return array|null
     */
    public function findSavedReport(int $id): ?array
    {
        $files = collect(Storage::files('reports'))
            ->sortByDesc(fn ($path) => Storage::lastModified($path))
            ->values();

        $path = $files->get($id - 1); // id adalah 1-based index

        if (! $path) {
            return null;
        }

        $filename = basename($path);
        $format   = str_ends_with($filename, '.pdf') ? 'pdf' : 'excel';

        return [
            'id'           => $id,
            'filename'     => $filename,
            'format'       => $format,
            'storage_path' => $path,
            'file_size_kb' => round(Storage::size($path) / 1024, 1),
            'created_at'   => Carbon::createFromTimestamp(Storage::lastModified($path))
                ->setTimezone('Asia/Makassar')
                ->toIso8601String(),
        ];
    }

    /**
     * Generate laporan bulanan otomatis (dipanggil oleh GenerateMonthlyReport command).
     */
    public function generateMonthlyReport(int $month, int $year): string
    {
        $periodId = SurveyPeriod::where('status', 'closed')
            ->whereYear('end_date', $year)
            ->whereMonth('end_date', $month)
            ->value('id');

        $data                 = $this->collectReportData('tracer_study', $periodId);
        $data['report_month'] = $month;
        $data['report_year']  = $year;

        $view     = $this->resolveView('tracer_study');
        $filename = sprintf('laporan-bulanan-%04d-%02d.pdf', $year, $month);
        $savePath = 'reports/' . $filename;

        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => false,
                'defaultFont'          => 'DejaVu Sans',
            ]);

        Storage::put($savePath, $pdf->output());

        return $savePath;
    }

    // ---------------------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------------------

    private function collectReportData(
        string $type,
        ?int $periodId,
        ?int $studyProgramId = null,
        ?int $graduationYearId = null
    ): array {
        $period = $periodId ? SurveyPeriod::find($periodId) : null;

        $alumniQuery = Alumni::whereNull('deleted_at')
            ->with(['studyProgram:id,name,code,degree_level', 'graduationYear:id,year,academic_year']);

        if ($studyProgramId)   { $alumniQuery->where('study_program_id', $studyProgramId); }
        if ($graduationYearId) { $alumniQuery->where('graduation_year_id', $graduationYearId); }
        if ($periodId) {
            $alumniQuery->whereHas('surveyPeriods', fn ($q) => $q->where('survey_periods.id', $periodId));
        }

        $alumni      = $alumniQuery->get();
        $alumniIds   = $alumni->pluck('id');
        $totalAlumni = $alumni->count();

        $responseQuery = SurveyResponse::whereIn('alumni_id', $alumniIds)
            ->where('respondent_type', 'alumni');
        if ($periodId) { $responseQuery->where('survey_period_id', $periodId); }
        $completedResponses = $responseQuery->where('status', 'submitted')->count();
        $responseRate = $totalAlumni > 0
            ? round(($completedResponses / $totalAlumni) * 100, 1)
            : 0.0;

        $workHistories = AlumniWorkHistory::whereIn('alumni_id', $alumniIds)
            ->where('is_current', 1)
            ->get();

        $employedCount = $workHistories->whereIn('employment_type', [
            'penuh_waktu', 'paruh_waktu', 'kontrak', 'magang', 'wirausaha',
        ])->count();

        $employmentRate = $totalAlumni > 0
            ? round(($employedCount / $totalAlumni) * 100, 1)
            : 0.0;

        $avgWaiting    = $workHistories->whereNotNull('waiting_time_months')->avg('waiting_time_months');
        $relevantCount = $workHistories->where('is_relevant_to_study', true)->count();
        $relevanceBase = $workHistories->whereNotNull('is_relevant_to_study')->count();
        $relevanceRate = $relevanceBase > 0 ? round(($relevantCount / $relevanceBase) * 100, 1) : 0.0;

        $byIndustry = $workHistories->where('is_current', 1)
            ->whereNotNull('industry_sector')
            ->groupBy('industry_sector')
            ->map(fn ($group, $sector) => [
                'sector'     => $sector,
                'count'      => $group->count(),
                'percentage' => $employedCount > 0 ? round(($group->count() / $employedCount) * 100, 1) : 0.0,
            ])
            ->sortByDesc('count')->values()->toArray();

        $bySalary = $workHistories->where('is_current', 1)
            ->whereNotNull('monthly_salary_range')
            ->groupBy('monthly_salary_range')
            ->map(fn ($group, $range) => [
                'range'      => $range,
                'count'      => $group->count(),
                'percentage' => $employedCount > 0 ? round(($group->count() / $employedCount) * 100, 1) : 0.0,
            ])
            ->sortByDesc('count')->values()->toArray();

        $byStudyProgram = $alumni->groupBy('study_program_id')
            ->map(function ($group) use ($workHistories) {
                $first     = $group->first();
                $ids       = $group->pluck('id');
                $empInProg = $workHistories->whereIn('alumni_id', $ids->toArray())
                    ->whereIn('employment_type', ['penuh_waktu', 'paruh_waktu', 'kontrak', 'magang', 'wirausaha'])
                    ->count();

                return [
                    'id'       => $first->study_program_id,
                    'name'     => $first->studyProgram->name ?? '-',
                    'code'     => $first->studyProgram->code ?? '-',
                    'total'    => $group->count(),
                    'employed' => $empInProg,
                    'rate'     => $group->count() > 0 ? round(($empInProg / $group->count()) * 100, 1) : 0.0,
                ];
            })->values()->toArray();

        $employers = Employer::whereNull('deleted_at')
            ->where('survey_status', 'submitted')
            ->with('surveyResponses.answers')
            ->get();

        return [
            'period'              => $period,
            'generated_at'        => now()->setTimezone('Asia/Makassar')->toIso8601String(),
            'report_type'         => $type,
            'total_alumni'        => $totalAlumni,
            'completed_responses' => $completedResponses,
            'response_rate'       => $responseRate,
            'employment_rate'     => $employmentRate,
            'average_waiting'     => $avgWaiting !== null ? round((float) $avgWaiting, 1) : null,
            'relevance_rate'      => $relevanceRate,
            'by_industry'         => $byIndustry,
            'by_salary'           => $bySalary,
            'by_study_program'    => $byStudyProgram,
            'alumni'              => $alumni,
            'employers'           => $employers,
            'university_name'     => SystemSetting::getValue('university_name', 'Universitas Islam Syarifuddin'),
            'university_tagline'  => SystemSetting::getValue('university_tagline', 'Menelusuri Jejak, Meraih Mutu'),
        ];
    }

    private function resolveView(string $type): string
    {
        return match ($type) {
            'employer' => 'reports.employer-report',
            default    => 'reports.alumni-report',
        };
    }

    private function buildFilename(string $type, ?int $periodId, string $ext): string
    {
        $slug   = str_replace('_', '-', $type);
        $suffix = $periodId ? '-periode' . $periodId : '';
        $ts     = now()->format('Ymd-His');

        return "{$slug}{$suffix}-{$ts}.{$ext}";
    }
}
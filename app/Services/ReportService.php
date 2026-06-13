<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\AlumniWorkHistory;
use App\Models\Employer;
use App\Models\SurveyPeriod;
use App\Models\SurveyResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TracerStudyExport;

class ReportService
{
    /**
     * Generate laporan PDF dan kembalikan response download.
     * Sesuai 05_API.md §8.1
     *
     * @param string   $type           tracer_study
     * @param int|null $periodId
     * @param int|null $studyProgramId
     * @param int|null $graduationYearId
     * @return \Illuminate\Http\Response
     */
    public function generatePdf(
        string $type,
        ?int $periodId = null,
        ?int $studyProgramId = null,
        ?int $graduationYearId = null
    ): \Illuminate\Http\Response {
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

        // Simpan ke storage/app/reports/
        $storagePath = 'reports/' . $filename;
        Storage::put($storagePath, $pdf->output());

        return $pdf->download($filename);
    }

    /**
     * Generate laporan Excel dan kembalikan response download.
     * Sesuai 05_API.md §8.2
     *
     * @param string   $type
     * @param int|null $periodId
     * @param int|null $studyProgramId
     * @param int|null $graduationYearId
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function generateExcel(
        string $type,
        ?int $periodId = null,
        ?int $studyProgramId = null,
        ?int $graduationYearId = null
    ): \Symfony\Component\HttpFoundation\BinaryFileResponse {
        $data     = $this->collectReportData($type, $periodId, $studyProgramId, $graduationYearId);
        $filename = $this->buildFilename($type, $periodId, 'xlsx');

        return Excel::download(
            new TracerStudyExport($data),
            $filename,
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    /**
     * Daftar laporan tersimpan di storage/app/reports.
     * Sesuai 05_API.md §8.3
     *
     * @return array
     */
    public function listSavedReports(): array
    {
        $files = Storage::files('reports');

        return collect($files)
            ->map(function (string $path) {
                $filename = basename($path);
                $size     = Storage::size($path);
                $modified = Storage::lastModified($path);

                return [
                    'filename'      => $filename,
                    'file_size_kb'  => round($size / 1024, 1),
                    'created_at'    => \Carbon\Carbon::createFromTimestamp($modified)
                        ->setTimezone('Asia/Makassar')
                        ->toIso8601String(),
                    'download_path' => $path,
                ];
            })
            ->sortByDesc('created_at')
            ->values()
            ->toArray();
    }

    /**
     * Stream download laporan tersimpan via signed temporary URL.
     * Sesuai 05_API.md §8.4
     *
     * @param string $storagePath  path relatif di disk default (e.g. reports/xxx.pdf)
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function downloadSavedReport(string $storagePath): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (! Storage::exists($storagePath)) {
            abort(404, 'File laporan tidak ditemukan.');
        }

        $filename = basename($storagePath);
        $mimeType = str_ends_with($filename, '.pdf')
            ? 'application/pdf'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return Storage::download($storagePath, $filename, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate laporan bulanan otomatis (dipanggil oleh GenerateMonthlyReport command).
     * Menyimpan PDF ke storage/app/reports/ tanpa streaming ke response.
     *
     * @param int    $month  1–12
     * @param int    $year
     * @return string  path file yang tersimpan
     */
    public function generateMonthlyReport(int $month, int $year): string
    {
        $periodId = SurveyPeriod::where('year', $year)
            ->where('status', 'closed')
            ->value('id');

        $data     = $this->collectReportData('tracer_study', $periodId);
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

    /**
     * Kumpulkan semua data yang diperlukan untuk template laporan.
     */
    private function collectReportData(
        string $type,
        ?int $periodId,
        ?int $studyProgramId = null,
        ?int $graduationYearId = null
    ): array {
        $period = $periodId ? SurveyPeriod::find($periodId) : null;

        // Alumni query dengan optional filter
        $alumniQuery = Alumni::whereNull('deleted_at')
            ->with(['studyProgram:id,name,code,degree_level', 'graduationYear:id,year,academic_year']);

        if ($studyProgramId) {
            $alumniQuery->where('study_program_id', $studyProgramId);
        }
        if ($graduationYearId) {
            $alumniQuery->where('graduation_year_id', $graduationYearId);
        }
        if ($periodId) {
            $alumniQuery->whereHas('surveyPeriods', fn ($q) => $q->where('survey_periods.id', $periodId));
        }

        $alumni       = $alumniQuery->get();
        $alumniIds    = $alumni->pluck('id');
        $totalAlumni  = $alumni->count();

        // Response survei
        $responseQuery = SurveyResponse::whereIn('alumni_id', $alumniIds)
            ->where('respondent_type', 'alumni');
        if ($periodId) {
            $responseQuery->where('survey_period_id', $periodId);
        }
        $completedResponses = $responseQuery->where('status', 'selesai')->count();
        $responseRate = $totalAlumni > 0
            ? round(($completedResponses / $totalAlumni) * 100, 1)
            : 0.0;

        // Work histories (current jobs)
        $workHistories = AlumniWorkHistory::whereIn('alumni_id', $alumniIds)
            ->where('is_current', 1)
            ->get();

        $employedCount = $workHistories->whereIn('employment_type', [
            'penuh_waktu', 'paruh_waktu', 'kontrak', 'magang', 'wirausaha',
        ])->count();

        $employmentRate = $totalAlumni > 0
            ? round(($employedCount / $totalAlumni) * 100, 1)
            : 0.0;

        $avgWaiting = $workHistories->where('is_current', 1)
            ->whereNotNull('waiting_time_months')
            ->avg('waiting_time_months');

        $relevantCount  = $workHistories->where('is_relevant_to_study', true)->count();
        $relevanceBase  = $workHistories->whereNotNull('is_relevant_to_study')->count();
        $relevanceRate  = $relevanceBase > 0
            ? round(($relevantCount / $relevanceBase) * 100, 1)
            : 0.0;

        // Distribusi industri
        $byIndustry = $workHistories->where('is_current', 1)
            ->whereNotNull('industry_sector')
            ->groupBy('industry_sector')
            ->map(fn ($group, $sector) => [
                'sector'     => $sector,
                'count'      => $group->count(),
                'percentage' => $employedCount > 0
                    ? round(($group->count() / $employedCount) * 100, 1)
                    : 0.0,
            ])
            ->sortByDesc('count')
            ->values()
            ->toArray();

        // Distribusi gaji
        $bySalary = $workHistories->where('is_current', 1)
            ->whereNotNull('monthly_salary_range')
            ->groupBy('monthly_salary_range')
            ->map(fn ($group, $range) => [
                'range'      => $range,
                'count'      => $group->count(),
                'percentage' => $employedCount > 0
                    ? round(($group->count() / $employedCount) * 100, 1)
                    : 0.0,
            ])
            ->sortByDesc('count')
            ->values()
            ->toArray();

        // Per program studi
        $byStudyProgram = $alumni->groupBy('study_program_id')
            ->map(function ($group) use ($workHistories) {
                $first        = $group->first();
                $ids          = $group->pluck('id');
                $empInProg    = $workHistories->whereIn('alumni_id', $ids->toArray())
                    ->whereIn('employment_type', ['penuh_waktu', 'paruh_waktu', 'kontrak', 'magang', 'wirausaha'])
                    ->count();

                return [
                    'id'       => $first->study_program_id,
                    'name'     => $first->studyProgram->name ?? '-',
                    'code'     => $first->studyProgram->code ?? '-',
                    'total'    => $group->count(),
                    'employed' => $empInProg,
                    'rate'     => $group->count() > 0
                        ? round(($empInProg / $group->count()) * 100, 1)
                        : 0.0,
                ];
            })
            ->values()
            ->toArray();

        // Employer data (untuk laporan employer)
        $employers = Employer::whereNull('deleted_at')
            ->where('survey_status', 'selesai')
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
            'university_name'     => \App\Models\SystemSetting::getValue('university_name', 'Universitas Islam Syarifuddin'),
            'university_tagline'  => \App\Models\SystemSetting::getValue('university_tagline', 'Menelusuri Jejak, Meraih Mutu'),
        ];
    }

    /**
     * Resolve nama Blade view sesuai tipe laporan.
     */
    private function resolveView(string $type): string
    {
        return match ($type) {
            'tracer_study' => 'reports.alumni-report',
            'employer'     => 'reports.employer-report',
            default        => 'reports.alumni-report',
        };
    }

    /**
     * Bangun nama file laporan yang konsisten.
     */
    private function buildFilename(string $type, ?int $periodId, string $ext): string
    {
        $slug   = str_replace('_', '-', $type);
        $suffix = $periodId ? '-periode' . $periodId : '';
        $ts     = now()->format('Ymd-His');

        return "{$slug}{$suffix}-{$ts}.{$ext}";
    }
}

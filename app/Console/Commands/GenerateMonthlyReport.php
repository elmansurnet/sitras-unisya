<?php

namespace App\Console\Commands;

use App\Jobs\GenerateReportExport;
use App\Models\SurveyPeriod;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * GenerateMonthlyReport
 *
 * Command artisan untuk men-generate laporan bulanan secara otomatis.
 * Dijadwalkan berjalan tanggal 1 setiap bulan pukul 01:00 WIB via Kernel.php.
 *
 * Cara jalankan manual:
 *   php artisan report:generate-monthly
 *   php artisan report:generate-monthly --month=3 --year=2024
 *   php artisan report:generate-monthly --format=excel
 *   php artisan report:generate-monthly --format=both
 *   php artisan report:generate-monthly --dry-run
 *
 * Alur kerja:
 *   1. Tentukan bulan & tahun target (default: bulan lalu)
 *   2. Cari survey_period yang relevan (closed atau active pada bulan itu)
 *   3. Dispatch GenerateReportExport ke queue 'low' untuk setiap format
 *   4. Log hasil dispatch ke laravel.log
 */
class GenerateMonthlyReport extends Command
{
    /**
     * Nama dan signature command.
     *
     * Options:
     *   --month  : Nomor bulan 1-12 (default: bulan lalu)
     *   --year   : Tahun 4 digit (default: tahun bulan lalu)
     *   --format : pdf | excel | both (default: both)
     *   --dry-run: Tampilkan apa yang akan dilakukan tanpa benar-benar dispatch job
     */
    protected $signature = 'report:generate-monthly
                            {--month= : Nomor bulan (1-12), default bulan lalu}
                            {--year=  : Tahun 4 digit, default tahun bulan lalu}
                            {--format=both : Format laporan: pdf | excel | both}
                            {--dry-run : Tampilkan rencana tanpa dispatch job}';

    protected $description = 'Generate laporan bulanan otomatis (PDF/Excel) dan dispatch ke queue low';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        // ----------------------------------------------------------------
        // 1. Tentukan bulan & tahun target
        // ----------------------------------------------------------------
        $targetDate = $this->resolveTargetDate();

        if ($targetDate === null) {
            $this->error('[GenerateMonthlyReport] Opsi --month atau --year tidak valid.');
            return self::FAILURE;
        }

        $month = (int) $targetDate->format('m');
        $year  = (int) $targetDate->format('Y');
        $label = $targetDate->translatedFormat('F Y');

        // ----------------------------------------------------------------
        // 2. Validasi format
        // ----------------------------------------------------------------
        $format = strtolower(trim($this->option('format') ?? 'both'));

        if (! in_array($format, ['pdf', 'excel', 'both'], true)) {
            $this->error("[GenerateMonthlyReport] Format '{$format}' tidak valid. Gunakan: pdf | excel | both");
            return self::FAILURE;
        }

        $this->info("[GenerateMonthlyReport] Target: {$label} | Format: {$format}" . ($isDryRun ? ' | DRY-RUN' : ''));

        // ----------------------------------------------------------------
        // 3. Cari survey_period yang relevan untuk bulan tersebut
        //    Kriteria: periode yang closed atau active yang mencakup bulan target
        // ----------------------------------------------------------------
        $periods = SurveyPeriod::query()
            ->whereIn('status', ['closed', 'active'])
            ->where('year', $year)
            ->get();

        if ($periods->isEmpty()) {
            $this->warn("[GenerateMonthlyReport] Tidak ada survey_period dengan year={$year} dan status closed/active. Laporan tidak dibuat.");
            Log::warning('[GenerateMonthlyReport] Tidak ada periode yang relevan.', [
                'month' => $month,
                'year'  => $year,
            ]);
            return self::SUCCESS;
        }

        $this->info("[GenerateMonthlyReport] Ditemukan {$periods->count()} periode untuk tahun {$year}.");

        // ----------------------------------------------------------------
        // 4. Tentukan format yang akan di-generate
        // ----------------------------------------------------------------
        $formats = match ($format) {
            'pdf'   => ['pdf'],
            'excel' => ['excel'],
            'both'  => ['pdf', 'excel'],
        };

        $dispatched = 0;

        foreach ($periods as $period) {
            foreach ($formats as $fmt) {
                $payload = [
                    'type'       => 'tracer_study',
                    'period_id'  => $period->id,
                    'format'     => $fmt,
                    'triggered_by' => 'scheduler',
                    'month'      => $month,
                    'year'       => $year,
                ];

                $this->line("  → Dispatch GenerateReportExport: period_id={$period->id} [{$period->name}] format={$fmt}" . ($isDryRun ? ' (SKIP — dry-run)' : ''));

                if (! $isDryRun) {
                    GenerateReportExport::dispatch($payload)->onQueue('low');
                    $dispatched++;
                }
            }
        }

        // ----------------------------------------------------------------
        // 5. Log hasil
        // ----------------------------------------------------------------
        if ($isDryRun) {
            $this->info('[GenerateMonthlyReport] Dry-run selesai. Tidak ada job yang di-dispatch.');
            return self::SUCCESS;
        }

        $this->info("[GenerateMonthlyReport] Selesai. {$dispatched} job di-dispatch ke queue 'low'.");

        Log::info('[GenerateMonthlyReport] Laporan bulanan berhasil di-dispatch.', [
            'month'      => $month,
            'year'       => $year,
            'format'     => $format,
            'periods'    => $periods->pluck('id')->toArray(),
            'dispatched' => $dispatched,
        ]);

        return self::SUCCESS;
    }

    /**
     * Resolusi tanggal target dari opsi --month dan --year.
     * Default: bulan lalu (bulan saat ini - 1).
     *
     * @return Carbon|null  null jika input tidak valid
     */
    private function resolveTargetDate(): ?Carbon
    {
        $monthOpt = $this->option('month');
        $yearOpt  = $this->option('year');

        // Default: bulan lalu
        if ($monthOpt === null && $yearOpt === null) {
            return Carbon::now('Asia/Jakarta')->subMonth()->startOfMonth();
        }

        // Jika salah satu diisi, pakai bulan/tahun saat ini sebagai default sisanya
        $now   = Carbon::now('Asia/Jakarta');
        $month = $monthOpt !== null ? (int) $monthOpt : (int) $now->format('m');
        $year  = $yearOpt  !== null ? (int) $yearOpt  : (int) $now->format('Y');

        // Validasi range
        if ($month < 1 || $month > 12) {
            return null;
        }

        if ($year < 2020 || $year > 2100) {
            return null;
        }

        return Carbon::createFromDate($year, $month, 1, 'Asia/Jakarta');
    }
}

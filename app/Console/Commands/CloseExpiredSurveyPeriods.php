<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\SurveyPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * CloseExpiredSurveyPeriods — Tutup survey_periods yang end_date-nya sudah lewat.
 *
 * Jadwal: harian pukul 00:05 WIB (setelah tengah malam).
 * Logika:
 *   - Cari semua SurveyPeriod dengan status='active' dan end_date < today.
 *   - Update status → 'closed'.
 *   - Catat ke audit_logs (module: survey_period, action: auto_closed).
 *
 * php artisan survey:close-expired [--dry-run]
 */
class CloseExpiredSurveyPeriods extends Command
{
    protected $signature = 'survey:close-expired
                            {--dry-run : Tampilkan period yang akan ditutup tanpa mengubah data}';

    protected $description = 'Tutup otomatis survey_periods yang sudah melewati tanggal berakhir';

    public function handle(): int
    {
        $today    = Carbon::today(config('app.timezone', 'Asia/Makassar'));
        $isDryRun = $this->option('dry-run');

        $this->info("[CloseExpiredSurveyPeriods] Running at {$today->toDateString()} | DryRun: " . ($isDryRun ? 'yes' : 'no'));

        $expiredPeriods = SurveyPeriod::query()
            ->where('status', 'active')
            ->where('end_date', '<', $today)
            ->get();

        if ($expiredPeriods->isEmpty()) {
            $this->info('Tidak ada survey period yang perlu ditutup.');
            return self::SUCCESS;
        }

        $this->info("Ditemukan {$expiredPeriods->count()} period untuk ditutup:");

        foreach ($expiredPeriods as $period) {
            $this->line("  [{$period->id}] {$period->name} (end_date: {$period->end_date->toDateString()})");

            if ($isDryRun) {
                continue;
            }

            $period->update(['status' => 'closed']);

            AuditLog::record(
                module:      'survey_period',
                action:      'auto_closed',
                modelType:   SurveyPeriod::class,
                modelId:     $period->id,
                description: "Survey period '{$period->name}' ditutup otomatis karena melewati end_date ({$period->end_date->toDateString()}).",
                oldValues:   ['status' => 'active'],
                newValues:   ['status' => 'closed'],
                performedBy: null, // sistem
            );

            $this->info("  ✓ Period [{$period->id}] berhasil ditutup.");
        }

        if (!$isDryRun) {
            Log::info('[CloseExpiredSurveyPeriods] Completed', [
                'date'   => $today->toDateString(),
                'closed' => $expiredPeriods->count(),
            ]);
        }

        $this->info("[CloseExpiredSurveyPeriods] Selesai. Ditutup: {$expiredPeriods->count()} period.");

        return self::SUCCESS;
    }
}

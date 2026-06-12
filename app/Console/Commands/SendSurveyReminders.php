<?php

namespace App\Console\Commands;

use App\Models\SurveyPeriod;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * SendSurveyReminders — Kirim pengingat kepada alumni/employer yang belum submit.
 *
 * Jadwal: harian pukul 08:00 WIB.
 * Strategi:
 *   - Kirim reminder 7 hari sebelum end_date.
 *   - Kirim reminder 3 hari sebelum end_date.
 *   - Kirim reminder 1 hari sebelum end_date.
 *   - Template code: survey_reminder_alumni / survey_reminder_employer.
 *
 * php artisan survey:send-reminders [--dry-run] [--period=ID]
 */
class SendSurveyReminders extends Command
{
    protected $signature = 'survey:send-reminders
                            {--dry-run : Tampilkan siapa saja yang akan dikirimi tanpa mengirim notifikasi}
                            {--period= : Kirim reminder hanya untuk period ID tertentu}';

    protected $description = 'Kirim reminder survei kepada alumni/employer yang belum submit';

    /** Hari-hari sebelum end_date yang menjadi trigger reminder. */
    private const REMINDER_DAYS = [7, 3, 1];

    public function handle(NotificationService $notificationService): int
    {
        $today    = Carbon::today(config('app.timezone', 'Asia/Makassar'));
        $isDryRun = $this->option('dry-run');
        $periodId = $this->option('period');

        $this->info("[SendSurveyReminders] Date: {$today->toDateString()} | DryRun: " . ($isDryRun ? 'yes' : 'no'));

        $query = SurveyPeriod::query()
            ->where('status', 'active')
            ->where('end_date', '>=', $today);

        if ($periodId) {
            $query->where('id', (int) $periodId);
        }

        $periods = $query->get();

        if ($periods->isEmpty()) {
            $this->info('Tidak ada period aktif yang memerlukan reminder.');
            return self::SUCCESS;
        }

        $totalSent = 0;

        foreach ($periods as $period) {
            $daysUntilEnd = $today->diffInDays($period->end_date, false);

            if (!in_array((int) $daysUntilEnd, self::REMINDER_DAYS, true)) {
                $this->line("  Period [{$period->id}] {$period->name}: {$daysUntilEnd} hari lagi — skip");
                continue;
            }

            $this->info("  Processing period [{$period->id}] {$period->name} (H-{$daysUntilEnd})");
            $sent = $this->processRemindersForPeriod($period, $notificationService, $isDryRun);
            $totalSent += $sent;
        }

        $this->info("[SendSurveyReminders] Selesai. Total reminder: {$totalSent}");

        Log::info('[SendSurveyReminders] Completed', [
            'date'           => $today->toDateString(),
            'periods_checked' => $periods->count(),
            'total_sent'     => $totalSent,
            'dry_run'        => $isDryRun,
        ]);

        return self::SUCCESS;
    }

    private function processRemindersForPeriod(
        SurveyPeriod $period,
        NotificationService $notificationService,
        bool $isDryRun,
    ): int {
        $sent = 0;

        // --- Alumni yang sudah punya response draft (belum submit) ---
        $period->load(['alumniResponses' => function ($q) {
            $q->where('status', 'draft')
              ->with('alumni');
        }]);

        foreach ($period->alumniResponses as $response) {
            $alumni = $response->alumni;
            if (!$alumni) {
                continue;
            }

            $this->line("    [alumni] {$alumni->full_name} ({$alumni->phone})");

            if (!$isDryRun) {
                $notificationService->sendByTemplate(
                    templateCode:   'survey_reminder_alumni',
                    recipientType:  'alumni',
                    recipientId:    $alumni->id,
                    phone:          $alumni->phone,
                    email:          $alumni->user?->email,
                    variables: [
                        'nama_penerima'   => $alumni->full_name,
                        'nama_period'     => $period->name,
                        'tanggal_selesai' => $period->end_date->format('d M Y'),
                        'link_survei'     => rtrim(config('app.frontend_url', config('app.url')), '/') . "/alumni/survey/{$period->id}",
                    ],
                    channel:        'both',
                    surveyPeriodId: $period->id,
                );
            }

            $sent++;
        }

        // --- Employer yang sudah punya response draft (belum submit) ---
        $period->load(['employerResponses' => function ($q) {
            $q->where('status', 'draft')
              ->with('employer');
        }]);

        foreach ($period->employerResponses as $response) {
            $employer = $response->employer;
            if (!$employer) {
                continue;
            }

            $this->line("    [employer] {$employer->name} ({$employer->phone})");

            if (!$isDryRun) {
                $notificationService->sendByTemplate(
                    templateCode:   'survey_reminder_employer',
                    recipientType:  'employer',
                    recipientId:    $employer->id,
                    phone:          $employer->phone,
                    email:          $employer->email,
                    variables: [
                        'nama_penerima'   => $employer->name,
                        'nama_period'     => $period->name,
                        'tanggal_selesai' => $period->end_date->format('d M Y'),
                        'link_survei'     => rtrim(config('app.frontend_url', config('app.url')), '/') . "/employer/survey/{$employer->survey_token}",
                    ],
                    channel:        'both',
                    surveyPeriodId: $period->id,
                );
            }

            $sent++;
        }

        return $sent;
    }
}

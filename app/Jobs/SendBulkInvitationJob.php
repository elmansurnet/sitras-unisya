<?php

namespace App\Jobs;

use App\Models\Alumni;
use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SendBulkInvitationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah retry jika job gagal.
     */
    public int $tries = 3;

    /**
     * Backoff dalam detik antar retry: 60s, 300s, 600s.
     *
     * @var array<int,int>
     */
    public array $backoff = [60, 300, 600];

    /**
     * Timeout maksimal eksekusi job (detik).
     */
    public int $timeout = 300;

    /**
     * @param  Collection<int,int> $alumniIds   ID alumni yang akan diundang
     * @param  int                 $surveyPeriodId
     * @param  int                 $actorId     user_id yang men-trigger blast
     * @param  int                 $questionnaireId  ID kuesioner yang akan dikirimkan
     */
    public function __construct(
        private readonly Collection $alumniIds,
        private readonly int        $surveyPeriodId,
        private readonly int        $actorId,
        private readonly int        $questionnaireId,
    ) {}

    /**
     * Eksekusi job: kirim undangan ke setiap alumni.
     * Notifikasi aktual (WA/email) akan diimplementasikan di sesi 4A
     * setelah SurveyPeriod, NotificationTemplate, dan WhatsAppService tersedia.
     */
    public function handle(): void
    {
        $chunk = 50; // Proses 50 alumni per iterasi untuk menghindari memory leak

        $this->alumniIds->chunk($chunk)->each(function (Collection $chunk) {
            $alumni = Alumni::whereIn('id', $chunk)
                ->where('is_active', true)
                ->with('user:id,name,email')
                ->get();

            foreach ($alumni as $alum) {
                try {
                    // Placeholder: dispatching SendWhatsAppNotification & SendEmailNotification
                    // akan diisi penuh di sesi 4A setelah NotificationService tersedia
                    Log::info('SendBulkInvitationJob: alumni queued', [
                        'alumni_id'        => $alum->id,
                        'survey_period_id' => $this->surveyPeriodId,
                        'questionnaire_id' => $this->questionnaireId,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('SendBulkInvitationJob: failed for alumni ' . $alum->id, [
                        'error' => $e->getMessage(),
                    ]);
                    // Lanjutkan ke alumni berikutnya (jangan lempar exception agar loop tidak berhenti)
                }
            }
        });

        AuditLog::record(
            action   : 'bulk_invitation_dispatched',
            module   : 'survey',
            modelId  : $this->surveyPeriodId,
            oldValues: null,
            newValues: [
                'alumni_count'     => $this->alumniIds->count(),
                'questionnaire_id' => $this->questionnaireId,
                'actor_id'         => $this->actorId,
            ],
            modelType: null,
        );
    }

    /**
     * Tangani kegagalan job setelah semua retry habis.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendBulkInvitationJob permanently failed', [
            'survey_period_id' => $this->surveyPeriodId,
            'alumni_count'     => $this->alumniIds->count(),
            'actor_id'         => $this->actorId,
            'error'            => $exception->getMessage(),
        ]);

        AuditLog::record(
            action   : 'bulk_invitation_failed',
            module   : 'survey',
            modelId  : $this->surveyPeriodId,
            oldValues: null,
            newValues: [
                'error'       => $exception->getMessage(),
                'actor_id'    => $this->actorId,
                'alumni_count'=> $this->alumniIds->count(),
            ],
            modelType: null,
        );
    }
}

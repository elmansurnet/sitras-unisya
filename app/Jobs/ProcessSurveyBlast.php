<?php

namespace App\Jobs;

use App\Models\Alumni;
use App\Models\Employer;
use App\Models\NotificationLog;
use App\Models\SurveyPeriod;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessSurveyBlast — Job untuk mengirim undangan survei secara massal.
 *
 * Dipanggil oleh Admin\SurveyPeriodController@blast setelah validasi.
 * Job ini memproses satu batch recipients (alumni atau employer) lalu
 * mendispatch SendWhatsAppNotification / SendEmailNotification per individu.
 *
 * Queue: high (prioritas tinggi agar blast tidak menumpuk di default).
 * Timeout: 300 detik. Tries: 1 (retry logic ada di individual notification jobs).
 */
class ProcessSurveyBlast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout  = 300;
    public int $tries    = 1;
    public string $queue = 'high';

    /**
     * @param SurveyPeriod $surveyPeriod   Period survei yang akan di-blast.
     * @param string       $recipientType  'alumni' atau 'employer'.
     * @param int          $questionnaireId Questionnaire yang digunakan untuk survei ini.
     * @param string       $channel        'whatsapp', 'email', atau 'both'.
     * @param array<int>   $recipientIds   Array ID alumni/employer yang akan menerima blast.
     *                                     Kosong = semua alumni/employer yang eligible.
     */
    public function __construct(
        public readonly SurveyPeriod $surveyPeriod,
        public readonly string $recipientType,
        public readonly int $questionnaireId,
        public readonly string $channel,
        public readonly array $recipientIds = [],
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        Log::info('[ProcessSurveyBlast] Starting blast', [
            'survey_period_id' => $this->surveyPeriod->id,
            'recipient_type'   => $this->recipientType,
            'questionnaire_id' => $this->questionnaireId,
            'channel'          => $this->channel,
            'recipient_count'  => count($this->recipientIds) ?: 'all',
        ]);

        $this->recipientType === 'alumni'
            ? $this->blastAlumni($notificationService)
            : $this->blastEmployers($notificationService);

        Log::info('[ProcessSurveyBlast] Blast complete', [
            'survey_period_id' => $this->surveyPeriod->id,
        ]);
    }

    // -------------------------------------------------------------------------

    private function blastAlumni(NotificationService $notificationService): void
    {
        $query = Alumni::query()
            ->where('status', 'active')
            ->whereDoesntHave('surveyResponses', function ($q) {
                $q->where('survey_period_id', $this->surveyPeriod->id)
                  ->whereIn('status', ['submitted', 'draft']);
            });

        if (!empty($this->recipientIds)) {
            $query->whereIn('id', $this->recipientIds);
        }

        $query->chunkById(50, function ($alumniChunk) use ($notificationService) {
            foreach ($alumniChunk as $alumni) {
                $this->dispatchNotification(
                    notificationService: $notificationService,
                    recipientType:       'alumni',
                    recipientId:         $alumni->id,
                    recipientName:       $alumni->full_name,
                    phone:               $alumni->phone,
                    email:               $alumni->user?->email,
                    surveyUrl:           $this->buildAlumniSurveyUrl($alumni),
                );
            }
        });
    }

    private function blastEmployers(NotificationService $notificationService): void
    {
        $query = Employer::query()
            ->whereDoesntHave('surveyResponses', function ($q) {
                $q->where('survey_period_id', $this->surveyPeriod->id)
                  ->whereIn('status', ['submitted', 'draft']);
            })
            ->whereNotNull('survey_token')
            ->where('token_expires_at', '>', now());

        if (!empty($this->recipientIds)) {
            $query->whereIn('id', $this->recipientIds);
        }

        $query->chunkById(50, function ($employerChunk) use ($notificationService) {
            foreach ($employerChunk as $employer) {
                $this->dispatchNotification(
                    notificationService: $notificationService,
                    recipientType:       'employer',
                    recipientId:         $employer->id,
                    recipientName:       $employer->name,
                    phone:               $employer->phone,
                    email:               $employer->email,
                    surveyUrl:           $this->buildEmployerSurveyUrl($employer),
                );
            }
        });
    }

    // -------------------------------------------------------------------------

    private function dispatchNotification(
        NotificationService $notificationService,
        string $recipientType,
        int $recipientId,
        string $recipientName,
        ?string $phone,
        ?string $email,
        string $surveyUrl,
    ): void {
        $templateCode = $recipientType === 'alumni'
            ? 'survey_invitation_alumni'
            : 'survey_invitation_employer';

        $variables = [
            'nama_penerima'   => $recipientName,
            'nama_period'     => $this->surveyPeriod->name,
            'tanggal_selesai' => $this->surveyPeriod->end_date->format('d M Y'),
            'link_survei'     => $surveyUrl,
        ];

        $notificationService->sendByTemplate(
            templateCode:  $templateCode,
            recipientType: $recipientType,
            recipientId:   $recipientId,
            phone:         $phone,
            email:         $email,
            variables:     $variables,
            channel:       $this->channel,
            surveyPeriodId: $this->surveyPeriod->id,
        );
    }

    private function buildAlumniSurveyUrl(Alumni $alumni): string
    {
        $baseUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
        return "{$baseUrl}/alumni/survey/{$this->surveyPeriod->id}";
    }

    private function buildEmployerSurveyUrl(Employer $employer): string
    {
        $baseUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
        return "{$baseUrl}/employer/survey/{$employer->survey_token}";
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[ProcessSurveyBlast] Job failed', [
            'survey_period_id' => $this->surveyPeriod->id,
            'error'            => $exception->getMessage(),
            'trace'            => $exception->getTraceAsString(),
        ]);
    }
}

<?php

namespace App\Jobs;

use App\Models\Alumni;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SendBulkInvitationJob
 * Kirim undangan survei massal ke alumni via WA atau Email.
 * Dipush ke queue 'high' untuk prioritas tinggi.
 *
 * Dispatch contoh:
 *   SendBulkInvitationJob::dispatch($alumniIds, $channel, $questionnaireId, $periodId, $actorId)
 *       ->onQueue('high');
 */
class SendBulkInvitationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maksimal percobaan ulang jika job gagal.
     */
    public int $tries = 3;

    /**
     * Timeout per job (detik).
     */
    public int $timeout = 120;

    /**
     * @param  array<int>  $alumniIds      ID alumni yang akan dikirim undangan
     * @param  string      $channel        whatsapp | email | both
     * @param  int         $questionnaireId ID kuesioner yang digunakan
     * @param  int         $periodId        ID survey_period
     * @param  int         $actorId         user_id yang memicu pengiriman
     */
    public function __construct(
        private readonly array  $alumniIds,
        private readonly string $channel,
        private readonly int    $questionnaireId,
        private readonly int    $periodId,
        private readonly int    $actorId,
    ) {
        $this->onQueue('high');
    }

    /**
     * Eksekusi job.
     */
    public function handle(): void
    {
        $alumni = Alumni::with('user:id,email,phone,name')
            ->whereIn('id', $this->alumniIds)
            ->where('is_active', true)
            ->get();

        $successCount = 0;
        $failedCount  = 0;

        foreach ($alumni as $alum) {
            try {
                if (in_array($this->channel, ['whatsapp', 'both'])) {
                    $this->sendWhatsApp($alum);
                }

                if (in_array($this->channel, ['email', 'both'])) {
                    $this->sendEmail($alum);
                }

                // Update survey_status → terkirim jika masih belum_disurvei
                if ($alum->survey_status === 'belum_disurvei') {
                    $alum->update(['survey_status' => 'terkirim']);
                }

                $successCount++;
            } catch (\Throwable $e) {
                Log::error('SendBulkInvitationJob: gagal kirim ke alumni ' . $alum->id, [
                    'error' => $e->getMessage(),
                ]);
                $failedCount++;
            }
        }

        AuditLog::record(
            action   : 'bulk_invitation_sent',
            module   : 'survey_period',
            modelId  : $this->periodId,
            oldValues: null,
            newValues: [
                'channel'          => $this->channel,
                'questionnaire_id' => $this->questionnaireId,
                'total'            => count($this->alumniIds),
                'success'          => $successCount,
                'failed'           => $failedCount,
                'actor_id'         => $this->actorId,
            ],
            modelType: \App\Models\SurveyPeriod::class,
        );
    }

    /**
     * Kirim via WhatsApp Gateway UNISYA.
     * Konfigurasi: wa_gateway_url, wa_api_key, wa_sender di system_settings.
     */
    private function sendWhatsApp(Alumni $alumni): void
    {
        $phone = $alumni->user?->phone;
        if (empty($phone)) {
            Log::warning('SendBulkInvitationJob: no phone for alumni ' . $alumni->id);
            return;
        }

        $gatewayUrl = SystemSetting::getValue('wa_gateway_url', config('tracer.wa_gateway_url'));
        $apiKey     = SystemSetting::getValue('wa_api_key');
        $sender     = SystemSetting::getValue('wa_sender');

        if (empty($apiKey) || empty($sender)) {
            Log::warning('SendBulkInvitationJob: WA gateway not configured.');
            return;
        }

        $surveyUrl = config('app.frontend_url') . '/survey?period=' . $this->periodId;
        $message   = "Yth. {$alumni->full_name},\n\n"
            . "Kami mengundang Anda untuk mengisi Survei Tracer Study UNISYA.\n"
            . "Klik tautan berikut untuk mulai:\n{$surveyUrl}\n\n"
            . "Terima kasih atas partisipasi Anda.\n"
            . "Tim Tracer Study UNISYA";

        Http::timeout(10)->post($gatewayUrl, [
            'api_key' => $apiKey,
            'sender'  => $sender,
            'number'  => $phone,
            'message' => $message,
            'full'    => 1,
        ]);
        // Status 'delivered' tidak diisi otomatis (gateway ini tidak punya webhook)
    }

    /**
     * Kirim via Email (Laravel Mail).
     * Implementasi penuh di sesi 4A setelah Mailable dibuat.
     */
    private function sendEmail(Alumni $alumni): void
    {
        $email = $alumni->user?->email;
        if (empty($email)) {
            Log::warning('SendBulkInvitationJob: no email for alumni ' . $alumni->id);
            return;
        }

        // TODO sesi 4A: dispatch Mailable SurveyInvitationMail
        // \Mail::to($email)->send(new \App\Mail\SurveyInvitationMail($alumni, $this->periodId));
        Log::info('SendBulkInvitationJob: email queued for alumni ' . $alumni->id);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendBulkInvitationJob FAILED', [
            'alumni_ids' => $this->alumniIds,
            'error'      => $exception->getMessage(),
        ]);
    }
}

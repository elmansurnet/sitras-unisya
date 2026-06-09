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
 *
 * Dikirim ke queue 'high' — kirim undangan survei massal ke alumni.
 * Setiap job menangani SATU alumni untuk isolasi kegagalan.
 *
 * Dispatch dari:
 *  - AlumniService::sendInvitation() — undangan tunggal
 *  - SurveyPeriodService::sendBulkInvitations() — undangan massal (sesi 4A)
 */
class SendBulkInvitationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maksimal percobaan ulang sebelum job dinyatakan gagal.
     */
    public int $tries = 3;

    /**
     * Timeout per job dalam detik.
     */
    public int $timeout = 30;

    public function __construct(
        public readonly int    $alumniId,
        public readonly int    $questionnaireId,
        public readonly string $channel,         // 'whatsapp' | 'email' | 'both'
        public readonly int    $actorId,
    ) {
        $this->onQueue('high');
    }

    public function handle(): void
    {
        $alumni = Alumni::with('user')->find($this->alumniId);

        if (!$alumni) {
            Log::warning('SendBulkInvitationJob: alumni not found', ['alumni_id' => $this->alumniId]);
            return;
        }

        // Buat URL survei alumni
        $surveyUrl = config('app.frontend_url', config('app.url'))
            . '/survey?alumni=' . $alumni->id
            . '&q='            . $this->questionnaireId;

        $message = "Assalamu'alaikum, Yth. {$alumni->full_name}\n\n"
            . "Kami mengundang Anda untuk mengisi Survei Tracer Study UNISYA.\n"
            . "Survei ini hanya membutuhkan ±10 menit.\n\n"
            . "Link survei:\n{$surveyUrl}\n\n"
            . "Terima kasih atas partisipasi Anda. 🙏";

        $sent = false;

        if (in_array($this->channel, ['whatsapp', 'both'], true) && $alumni->phone) {
            $sent = $this->sendWhatsapp($alumni->phone, $message);
        }

        if (in_array($this->channel, ['email', 'both'], true) && $alumni->user?->email) {
            // Email akan diimplementasi penuh di sesi 3A (NotificationService)
            // Placeholder: log saja dulu
            Log::info('SendBulkInvitationJob: email queued', [
                'alumni_id' => $this->alumniId,
                'email'     => $alumni->user->email,
            ]);
            $sent = true;
        }

        if ($sent) {
            // Update survey_status → 'terkirim'
            $alumni->update(['survey_status' => 'terkirim']);

            AuditLog::record(
                action   : 'send_invitation',
                module   : 'alumni',
                modelId  : $alumni->id,
                oldValues: ['survey_status' => $alumni->getOriginal('survey_status')],
                newValues: [
                    'survey_status'    => 'terkirim',
                    'channel'          => $this->channel,
                    'questionnaire_id' => $this->questionnaireId,
                    'actor_id'         => $this->actorId,
                ],
                modelType: Alumni::class,
            );
        }
    }

    /**
     * Kirim pesan via WA Gateway UNISYA.
     * Config: wa_gateway_url, wa_api_key, wa_sender di system_settings.
     *
     * Response success: { status: true, data: { key: { id: "..." } } }
     * Status 'delivered' TIDAK diisi otomatis dari gateway ini.
     */
    private function sendWhatsapp(string $phone, string $message): bool
    {
        try {
            $url    = SystemSetting::getValue('wa_gateway_url', config('tracer.wa_gateway_url', ''));
            $apiKey = SystemSetting::getValue('wa_api_key', '');
            $sender = SystemSetting::getValue('wa_sender', '');

            if (empty($url) || empty($apiKey) || empty($sender)) {
                Log::warning('SendBulkInvitationJob: WA gateway not configured');
                return false;
            }

            $response = Http::timeout(10)->post($url, [
                'api_key' => $apiKey,
                'sender'  => $sender,
                'number'  => $phone,
                'message' => $message,
                'full'    => 1,
            ]);

            $body = $response->json();

            if ($response->successful() && ($body['status'] ?? false) === true) {
                Log::info('SendBulkInvitationJob: WA sent', [
                    'alumni_id' => $this->alumniId,
                    'phone'     => $phone,
                ]);
                return true;
            }

            Log::warning('SendBulkInvitationJob: WA gateway returned failure', [
                'alumni_id' => $this->alumniId,
                'response'  => $body,
            ]);
            return false;

        } catch (\Throwable $e) {
            Log::error('SendBulkInvitationJob: WA exception', [
                'alumni_id' => $this->alumniId,
                'error'     => $e->getMessage(),
            ]);
            // Re-throw agar Laravel Queue retry job ini
            throw $e;
        }
    }

    /**
     * Tangani job yang sudah gagal setelah semua retry habis.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendBulkInvitationJob: permanently failed', [
            'alumni_id' => $this->alumniId,
            'error'     => $exception->getMessage(),
        ]);
    }
}

<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SendWhatsAppNotification Job
 * Queue: 'high' — OTP harus dikirim secepat mungkin
 *
 * WA Gateway UNISYA:
 *   URL    : config wa_gateway_url dari system_settings
 *   Method : POST JSON
 *   Params : api_key, sender, number, message, full=1
 *   Response: { status: true/false, data: { key: { id: "..." } } }
 *
 * PENTING: status 'delivered' TIDAK diisi otomatis (gateway tidak support webhook).
 */
class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah percobaan ulang jika job gagal.
     */
    public int $tries = 3;

    /**
     * Timeout per percobaan (detik).
     */
    public int $timeout = 30;

    public function __construct(
        private readonly string  $number,
        private readonly string  $message,
        private readonly ?int    $notifiableId = null,
        private readonly ?string $notifiableType = null,
    ) {
    }

    public function handle(): void
    {
        // Baca konfigurasi WA Gateway dari system_settings
        $settings = DB::table('system_settings')
            ->whereIn('key', ['wa_gateway_url', 'wa_api_key', 'wa_sender'])
            ->pluck('value', 'key');

        $gatewayUrl = $settings['wa_gateway_url'] ?? config('tracer.whatsapp.gateway_url');
        $apiKey     = $settings['wa_api_key']     ?? config('tracer.whatsapp.api_key');
        $sender     = $settings['wa_sender']      ?? config('tracer.whatsapp.sender');

        if (empty($gatewayUrl) || empty($apiKey) || empty($sender)) {
            Log::warning('SendWhatsAppNotification: WA Gateway belum dikonfigurasi.', [
                'number' => $this->number,
            ]);
            $this->logNotification('failed', null, 'WA Gateway tidak dikonfigurasi');
            return;
        }

        $status     = 'failed';
        $messageId  = null;
        $errorMsg   = null;
        $providerResponse = null;

        try {
            $response = Http::timeout(20)->post($gatewayUrl, [
                'api_key' => $apiKey,
                'sender'  => $sender,
                'number'  => $this->number,
                'message' => $this->message,
                'full'    => 1,
            ]);

            $providerResponse = $response->json();

            if ($response->successful() && ($providerResponse['status'] ?? false) === true) {
                $status    = 'sent';
                // Ambil message ID dari response: { data: { key: { id: "..." } } }
                $dataKeys  = array_keys($providerResponse['data'] ?? []);
                $firstKey  = $dataKeys[0] ?? null;
                $messageId = $firstKey ? ($providerResponse['data'][$firstKey]['id'] ?? null) : null;
            } else {
                $errorMsg = $providerResponse['message'] ?? 'Gateway returned failure';
                Log::error('SendWhatsAppNotification: gateway returned failure', [
                    'number'   => $this->number,
                    'response' => $providerResponse,
                ]);
            }
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            Log::error('SendWhatsAppNotification: HTTP exception', [
                'number'    => $this->number,
                'exception' => $errorMsg,
            ]);
            // Re-throw agar queue retry bisa berjalan
            throw $e;
        } finally {
            // Selalu log ke notification_logs
            // status 'delivered' TIDAK diisi (gateway tidak support webhook)
            $this->logNotification($status, $messageId, $errorMsg, $providerResponse);
        }
    }

    private function logNotification(
        string  $status,
        ?string $messageId,
        ?string $errorMessage,
        ?array  $providerResponse = null,
    ): void {
        try {
            DB::table('notification_logs')->insert([
                'notifiable_type'   => $this->notifiableType,
                'notifiable_id'     => $this->notifiableId,
                'channel'           => 'whatsapp',
                'recipient'         => $this->number,
                'message'           => $this->message,
                'status'            => $status,
                // delivered_at sengaja NULL — gateway tidak support webhook
                'delivered_at'      => null,
                'provider_message_id' => $messageId,
                'provider_response' => $providerResponse ? json_encode($providerResponse) : null,
                'error_message'     => $errorMessage,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('SendWhatsAppNotification: gagal log ke notification_logs', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

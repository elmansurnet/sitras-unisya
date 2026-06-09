<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * SendEmailNotification Job
 * Queue: 'high' — OTP harus dikirim secepat mungkin
 *
 * Menggunakan SMTP config dari system_settings:
 *   smtp_host, smtp_port, smtp_username, smtp_password (encrypted),
 *   smtp_encryption, smtp_from_address, smtp_from_name
 */
class SendEmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(
        private readonly string  $toEmail,
        private readonly string  $subject,
        private readonly string  $body,
        private readonly ?int    $notifiableId   = null,
        private readonly ?string $notifiableType = null,
    ) {
    }

    public function handle(): void
    {
        // Baca SMTP config dari system_settings
        $settings = DB::table('system_settings')
            ->whereIn('key', [
                'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
                'smtp_encryption', 'smtp_from_address', 'smtp_from_name',
            ])
            ->pluck('value', 'key');

        $host        = $settings['smtp_host']         ?? config('mail.mailers.smtp.host');
        $port        = $settings['smtp_port']         ?? config('mail.mailers.smtp.port');
        $username    = $settings['smtp_username']     ?? config('mail.mailers.smtp.username');
        $password    = $settings['smtp_password']     ?? config('mail.mailers.smtp.password');
        $encryption  = $settings['smtp_encryption']  ?? config('mail.mailers.smtp.encryption');
        $fromAddress = $settings['smtp_from_address'] ?? config('mail.from.address');
        $fromName    = $settings['smtp_from_name']    ?? config('mail.from.name');

        // Override config runtime agar menggunakan setting dari DB
        config([
            'mail.mailers.smtp.host'       => $host,
            'mail.mailers.smtp.port'       => $port,
            'mail.mailers.smtp.username'   => $username,
            'mail.mailers.smtp.password'   => $password,
            'mail.mailers.smtp.encryption' => $encryption,
            'mail.from.address'            => $fromAddress,
            'mail.from.name'               => $fromName,
        ]);

        $status    = 'failed';
        $errorMsg  = null;

        try {
            Mail::raw($this->body, function (Message $mail) use ($fromAddress, $fromName) {
                $mail->to($this->toEmail)
                     ->subject($this->subject)
                     ->from($fromAddress, $fromName);
            });

            $status = 'sent';
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            Log::error('SendEmailNotification: failed', [
                'to'        => $this->toEmail,
                'subject'   => $this->subject,
                'exception' => $errorMsg,
            ]);
            throw $e;
        } finally {
            $this->logNotification($status, $errorMsg);
        }
    }

    private function logNotification(string $status, ?string $errorMessage): void
    {
        try {
            DB::table('notification_logs')->insert([
                'notifiable_type'     => $this->notifiableType,
                'notifiable_id'       => $this->notifiableId,
                'channel'             => 'email',
                'recipient'           => $this->toEmail,
                'message'             => $this->body,
                'status'              => $status,
                'delivered_at'        => $status === 'sent' ? now() : null,
                'provider_message_id' => null,
                'provider_response'   => null,
                'error_message'       => $errorMessage,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('SendEmailNotification: gagal log ke notification_logs', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Alumni;
use App\Models\Employer;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(
        private readonly WhatsAppService $whatsApp,
    ) {}

    /**
     * Kirim notifikasi ke alumni (channel WA dan/atau email).
     *
     * @param  string  $event    Nama event (e.g. 'survey_invitation', 'survey_reminder')
     * @param  Alumni  $alumni
     * @param  array   $variables  Variabel untuk render template
     * @param  bool    $sendWa
     * @param  bool    $sendEmail
     */
    public function sendToAlumni(
        string $event,
        Alumni $alumni,
        array $variables = [],
        bool $sendWa = true,
        bool $sendEmail = false
    ): void {
        if ($sendWa) {
            $this->send('whatsapp', $event, $variables, [
                'recipient_type' => 'alumni',
                'recipient_id'   => $alumni->id,
                'phone'          => $alumni->phone,
                'name'           => $alumni->full_name,
            ]);
        }

        if ($sendEmail && $alumni->user?->email) {
            $this->send('email', $event, $variables, [
                'recipient_type' => 'alumni',
                'recipient_id'   => $alumni->id,
                'email'          => $alumni->user->email,
                'name'           => $alumni->full_name,
            ]);
        }
    }

    /**
     * Kirim notifikasi ke employer (channel WA dan/atau email).
     *
     * @param  string   $event
     * @param  Employer $employer
     * @param  array    $variables
     * @param  bool     $sendWa
     * @param  bool     $sendEmail
     */
    public function sendToEmployer(
        string $event,
        Employer $employer,
        array $variables = [],
        bool $sendWa = true,
        bool $sendEmail = false
    ): void {
        if ($sendWa && $employer->phone) {
            $this->send('whatsapp', $event, $variables, [
                'recipient_type' => 'employer',
                'recipient_id'   => $employer->id,
                'phone'          => $employer->phone,
                'name'           => $employer->name,
            ]);
        }

        if ($sendEmail && $employer->user?->email) {
            $this->send('email', $event, $variables, [
                'recipient_type' => 'employer',
                'recipient_id'   => $employer->id,
                'email'          => $employer->user->email,
                'name'           => $employer->name,
            ]);
        }
    }

    /**
     * Core dispatcher: ambil template, render, kirim, catat log.
     *
     * @param  string  $channel  'whatsapp' | 'email'
     * @param  string  $event
     * @param  array   $variables
     * @param  array{recipient_type:string, recipient_id:int, phone?:string, email?:string, name:string} $recipient
     */
    public function send(
        string $channel,
        string $event,
        array $variables,
        array $recipient
    ): NotificationLog {
        // 1. Ambil template aktif
        $template = NotificationTemplate::query()
            ->active()
            ->forChannel($channel)
            ->forEvent($event)
            ->first();

        $renderedBody   = null;
        $renderedFooter = null;
        $templateId     = null;

        if ($template) {
            $templateId   = $template->id;
            $renderedBody = $this->renderTemplate($template->body, $variables);
            if ($template->footer) {
                $renderedFooter = $this->renderTemplate($template->footer, $variables);
            }
        } else {
            Log::warning('[NotificationService] Template tidak ditemukan', [
                'channel' => $channel,
                'event'   => $event,
            ]);
            // Fallback message minimal
            $renderedBody = "Notifikasi dari SITRAS UNISYA: {$event}";
        }

        // 2. Buat log awal (status: pending)
        $log = NotificationLog::create([
            'notification_template_id' => $templateId,
            'recipient_type'           => $recipient['recipient_type'],
            'recipient_id'             => $recipient['recipient_id'],
            'channel'                  => $channel,
            'phone'                    => $recipient['phone'] ?? null,
            'email'                    => $recipient['email'] ?? null,
            'rendered_body'            => $renderedBody,
            'rendered_footer'          => $renderedFooter,
            'status'                   => 'pending',
            'sent_at'                  => now(),
        ]);

        // 3. Kirim sesuai channel
        try {
            $providerResponse = match ($channel) {
                'whatsapp' => $this->whatsApp->send(
                    number : $recipient['phone'],
                    message: $renderedBody,
                    footer : $renderedFooter,
                ),
                'email' => $this->sendEmail(
                    to     : $recipient['email'],
                    name   : $recipient['name'],
                    body   : $renderedBody,
                    event  : $event,
                ),
                default => throw new \InvalidArgumentException("Channel '{$channel}' tidak didukung"),
            };

            // 4. Update log → sent (gateway UNISYA tidak kirim delivered callback)
            $log->update([
                'status'            => 'sent',
                'provider_response' => $providerResponse,
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status'            => 'failed',
                'provider_response' => ['error' => $e->getMessage()],
            ]);

            Log::error('[NotificationService] Gagal kirim notifikasi', [
                'channel'        => $channel,
                'event'          => $event,
                'recipient_id'   => $recipient['recipient_id'],
                'error'          => $e->getMessage(),
            ]);
        }

        return $log->fresh();
    }

    /**
     * Render template body dengan mengganti {{variable}} → nilai aktual.
     *
     * @param  string              $template  Template string dengan placeholder {{var}}
     * @param  array<string,mixed> $variables
     */
    public function renderTemplate(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace("{{{$key}}}", (string) ($value ?? ''), $template);
        }

        // Bersihkan placeholder yang tidak terisi
        return preg_replace('/\{\{[a-z0-9_]+\}\}/i', '', $template);
    }

    /**
     * Kirim notifikasi email via Laravel Mail.
     * Saat ini menggunakan Mail::raw() — dapat diganti Mailable.
     *
     * @return array{status:bool}
     */
    private function sendEmail(string $to, string $name, string $body, string $event): array
    {
        \Illuminate\Support\Facades\Mail::raw($body, function ($message) use ($to, $name, $event) {
            $message->to($to, $name)
                    ->subject('SITRAS UNISYA — ' . ucwords(str_replace('_', ' ', $event)));
        });

        return ['status' => true, 'channel' => 'email', 'to' => $to];
    }
}

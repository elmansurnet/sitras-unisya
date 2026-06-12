<?php

namespace App\Services;

use App\Jobs\SendWhatsAppNotification;
use App\Jobs\SendEmailNotification;
use App\Models\AuditLog;
use App\Models\Employer;
use App\Repositories\Contracts\EmployerRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmployerService
{
    public function __construct(
        protected EmployerRepositoryInterface $repository
    ) {}

    // ─── CRUD ───────────────────────────────────────────────────────────────

    public function create(array $data): Employer
    {
        return $this->repository->create($data);
    }

    public function update(Employer $employer, array $data): Employer
    {
        return $this->repository->update($employer, $data);
    }

    public function delete(Employer $employer): void
    {
        $this->repository->delete($employer);
    }

    // ─── Token Management ──────────────────────────────────────────────────

    /**
     * Generate token CSPRNG 64 char (plaintext, bukan hash).
     * Token disimpan plaintext karena dipakai langsung dalam URL survei.
     * Lihat: KONFIGURASI KRITIS — Employer Token.
     */
    public function generateToken(): string
    {
        do {
            $token = Str::random(64);
        } while (Employer::where('survey_token', $token)->exists());

        return $token;
    }

    /**
     * Kirim token survei ke employer.
     * - Generate token baru (invalidate yang lama)
     * - Set expires_at = now() + 30 hari
     * - Update survey_status → 'terkirim'
     * - Dispatch notification job
     * - Catat ke audit_logs
     *
     * @throws ValidationException jika employer sudah selesai survei
     */
    public function sendSurveyToken(Employer $employer, string $channel): void
    {
        if ($employer->survey_status === 'selesai') {
            throw ValidationException::withMessages([
                'employer' => ['Employer ini sudah menyelesaikan survei.'],
            ]);
        }

        $token     = $this->generateToken();
        $expiresAt = now()->addDays(30);

        $employer->update([
            'survey_token'            => $token,
            'survey_token_expires_at' => $expiresAt,
            'survey_token_used_at'    => null,
            'survey_status'           => 'terkirim',
        ]);

        $frontendUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
        $surveyUrl   = "{$frontendUrl}/survey/employer/{$token}";

        $payload = [
            'type'       => 'survey_invitation_employer',
            'employer'   => [
                'id'            => $employer->id,
                'company_name'  => $employer->company_name,
                'contact_name'  => $employer->contact_person_name,
                'contact_phone' => $employer->contact_person_phone,
                'contact_email' => $employer->contact_person_email,
            ],
            'survey_url' => $surveyUrl,
            'expires_at' => $expiresAt->toIso8601String(),
            'channel'    => $channel,
        ];

        if ($channel === 'whatsapp') {
            SendWhatsAppNotification::dispatch($payload)->onQueue('notifications');
        } else {
            SendEmailNotification::dispatch($payload)->onQueue('notifications');
        }

        AuditLog::record(
            action: 'send_survey_token',
            module: 'Employer',
            modelId: $employer->id,
            modelType: Employer::class,
            oldValues: null,
            newValues: [
                'channel'       => $channel,
                'expires_at'    => $expiresAt->toIso8601String(),
                'survey_status' => 'terkirim',
            ]
        );
    }

    /**
     * Regenerate token — buat token baru, reset expires_at.
     * Dicatat dengan level WARNING karena mengganti token aktif yang sudah terkirim.
     *
     * @throws ValidationException jika employer sudah selesai survei
     */
    public function regenerateToken(Employer $employer): string
    {
        if ($employer->survey_status === 'selesai') {
            throw ValidationException::withMessages([
                'employer' => ['Token tidak dapat di-regenerate: employer sudah menyelesaikan survei.'],
            ]);
        }

        $hadToken  = ! empty($employer->survey_token);
        $oldStatus = $employer->survey_status;
        $token     = $this->generateToken();
        $expiresAt = now()->addDays(30);

        $employer->update([
            'survey_token'            => $token,
            'survey_token_expires_at' => $expiresAt,
            'survey_token_used_at'    => null,
            'survey_status'           => 'terkirim',
        ]);

        AuditLog::record(
            action: 'regenerate_token',
            module: 'Employer',
            modelId: $employer->id,
            modelType: Employer::class,
            oldValues: ['had_token' => $hadToken, 'survey_status' => $oldStatus],
            newValues: [
                'expires_at'    => $expiresAt->toIso8601String(),
                'survey_status' => 'terkirim',
            ],
            level: 'warning'
        );

        return $token;
    }
}

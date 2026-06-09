<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\OtpRequestRequest;
use App\Http\Requests\Auth\OtpVerifyRequest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;

/**
 * OtpController
 * Endpoint OTP untuk login alumni via NIM/email/phone.
 * Rate limit: throttle:otp-request pada requestOtp
 *             throttle:auth pada verifyOtp
 * Response format sesuai 05_API.md §2.1 dan §2.2
 */
class OtpController extends Controller
{
    public function __construct(private readonly OtpService $otpService)
    {
    }

    /**
     * POST /api/v1/auth/otp/request
     * 05_API.md §2.1
     */
    public function requestOtp(OtpRequestRequest $request): JsonResponse
    {
        $identifier     = $request->input('identifier');
        $identifierType = $request->input('identifier_type');
        $channel        = $request->input('channel', 'whatsapp');

        // Cari user berdasarkan identifier type
        $user = $this->resolveUser($identifier, $identifierType);

        // Tentukan destination (nomor WA atau email)
        $destination = $this->resolveDestination($user, $identifier, $identifierType, $channel);

        try {
            $rawOtp = $this->otpService->generateOtp($user, $identifier, $channel);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (str_starts_with($msg, 'COOLDOWN:')) {
                $retryAfter = (int) str_replace('COOLDOWN:', '', $msg);
                return response()->json([
                    'success' => false,
                    'message' => "OTP sudah dikirim. Tunggu {$retryAfter} detik sebelum request ulang.",
                    'data'    => [
                        'retry_after_seconds' => $retryAfter,
                    ],
                ], 429);
            }
            throw $e;
        }

        // Dispatch notifikasi ke queue 'high'
        $this->otpService->dispatchOtpNotification($rawOtp, $destination, $channel);

        $maskedDestination = $this->otpService->maskDestination($destination);
        $expiryMinutes     = config('tracer.otp.expiry_minutes', 5);
        $cooldown          = config('tracer.otp.resend_cooldown_seconds', 60);

        return response()->json([
            'success' => true,
            'message' => $channel === 'whatsapp'
                ? 'Kode OTP telah dikirim ke WhatsApp Anda'
                : 'Kode OTP telah dikirim ke email Anda',
            'data'    => [
                'expires_in'          => $expiryMinutes * 60, // detik
                'channel'             => $channel,
                'masked_destination'  => $maskedDestination,
                'resend_available_in' => $cooldown,
            ],
        ], 200);
    }

    /**
     * POST /api/v1/auth/otp/verify
     * 05_API.md §2.2
     */
    public function verifyOtp(OtpVerifyRequest $request): JsonResponse
    {
        $identifier = $request->input('identifier');
        $otpCode    = $request->input('otp_code');

        $otpRecord = $this->otpService->verifyOtp($identifier, $otpCode);

        if (! $otpRecord) {
            // Hitung remaining attempts dari DB
            $maxAttempts   = config('tracer.otp.max_attempts', 3);
            $latestOtp     = \App\Models\OtpCode::where('identifier', $identifier)
                ->where('expires_at', '>', now())
                ->latest()
                ->first();
            $remaining = $latestOtp
                ? max(0, $maxAttempts - $latestOtp->attempts)
                : 0;

            if ($remaining === 0) {
                return response()->json([
                    'success'    => false,
                    'message'    => 'Kode OTP sudah kedaluwarsa. Silakan request OTP baru.',
                    'error_code' => 'OTP_EXPIRED',
                ], 401);
            }

            return response()->json([
                'success' => false,
                'message' => "Kode OTP salah. Sisa percobaan: {$remaining}",
                'data'    => [
                    'remaining_attempts' => $remaining,
                ],
            ], 401);
        }

        // OTP valid — cari user dan buat Sanctum token
        $identifierType = $request->input('identifier_type');
        $user           = $this->resolveUser($identifier, $identifierType);

        if (! $user) {
            return response()->json([
                'success'    => false,
                'message'    => 'Akun tidak ditemukan.',
                'error_code' => 'USER_NOT_FOUND',
            ], 404);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda telah dinonaktifkan.',
            ], 403);
        }

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken('alumni-otp')->plainTextToken;

        \App\Models\AuditLog::record('login', 'Auth', $user->id, null, ['via' => 'otp']);

        $expiresAt = now()->addMinutes(config('sanctum.expiration', 10080));

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data'    => [
                'token'              => $token,
                'token_type'         => 'Bearer',
                'expires_at'         => $expiresAt->toIso8601String(),
                'user'               => [
                    'id'                  => $user->id,
                    'name'                => $user->name,
                    'role'                => $user->role,
                    'email'               => $user->email,
                    'is_profile_complete' => $user->alumni?->is_profile_complete ?? false,
                ],
            ],
        ], 200);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function resolveUser(string $identifier, string $identifierType): ?User
    {
        return match ($identifierType) {
            'nim'   => User::whereHas('alumni', fn ($q) => $q->where('nim', $identifier))->first(),
            'email' => User::where('email', $identifier)->first(),
            'phone' => User::whereHas('alumni', fn ($q) => $q->where('phone', $identifier))->first(),
            default => null,
        };
    }

    private function resolveDestination(?User $user, string $identifier, string $identifierType, string $channel): string
    {
        if ($channel === 'email') {
            return $user?->email ?? $identifier;
        }
        // WhatsApp: gunakan phone dari alumni profile atau identifier jika type=phone
        if ($identifierType === 'phone') {
            return $identifier;
        }
        return $user?->alumni?->phone ?? $identifier;
    }
}

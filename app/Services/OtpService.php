<?php

namespace App\Services;

use App\Jobs\SendEmailNotification;
use App\Jobs\SendWhatsAppNotification;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * OtpService
 * Implementasi sesuai 07_SECURITY.md §4.2
 *
 * - OTP TIDAK PERNAH disimpan plaintext — hanya SHA-256 hash
 * - Verifikasi menggunakan hash_equals() (timing-safe)
 * - Cooldown 60 detik antar request OTP
 * - Max 3 percobaan verifikasi
 */
class OtpService
{
    /**
     * Generate OTP, simpan ke DB (hashed), return rawOtp plaintext untuk dikirim.
     *
     * @param  User|null  $user       User terkait (null jika belum diketahui saat request)
     * @param  string     $identifier NIM / email / phone
     * @param  string     $channel    whatsapp | email
     *
     * @throws \Exception Jika cooldown 60 detik belum habis
     */
    public function generateOtp(?User $user, string $identifier, string $channel): string
    {
        // 1. Cek apakah ada OTP aktif dalam 60 detik terakhir (cooldown)
        $existing = OtpCode::where('identifier', $identifier)
            ->where('is_used', 0)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($existing) {
            $secondsElapsed = now()->diffInSeconds($existing->created_at, false) * -1;
            $cooldown       = config('tracer.otp.resend_cooldown_seconds', 60);

            if ($secondsElapsed < $cooldown) {
                $retryAfter = (int) ($cooldown - $secondsElapsed);
                throw new \Exception("COOLDOWN:{$retryAfter}");
            }

            // Cooldown sudah lewat: invalidasi OTP lama
            $existing->update(['is_used' => 1]);
        }

        // 2. Generate OTP 6 digit CSPRNG
        $rawOtp    = (string) random_int(100000, 999999);
        $hashedOtp = hash('sha256', $rawOtp); // VARCHAR(64) SHA-256 hex digest

        // 3. Simpan ke otp_codes
        OtpCode::create([
            'user_id'    => $user?->id,
            'identifier' => $identifier,
            'channel'    => $channel,
            'code'       => $hashedOtp,
            'expires_at' => now()->addMinutes(config('tracer.otp.expiry_minutes', 5)),
            'is_used'    => false,
            'attempts'   => 0,
        ]);

        return $rawOtp; // plaintext untuk dikirim ke user
    }

    /**
     * Verifikasi OTP input dari user.
     *
     * @return OtpCode|false  OtpCode jika valid, false jika gagal
     */
    public function verifyOtp(string $identifier, string $inputOtp): OtpCode|false
    {
        $maxAttempts = config('tracer.otp.max_attempts', 3);

        // Cari OTP aktif terbaru untuk identifier ini
        $otpRecord = OtpCode::where('identifier', $identifier)
            ->where('is_used', 0)
            ->where('expires_at', '>', now())
            ->where('attempts', '<', $maxAttempts)
            ->latest()
            ->first();

        if (! $otpRecord) {
            return false; // Tidak ada OTP aktif / sudah expired
        }

        $inputHash  = hash('sha256', $inputOtp);
        $isValid    = hash_equals($otpRecord->code, $inputHash); // timing-safe

        if (! $isValid) {
            // Increment attempts
            $otpRecord->increment('attempts');

            // Jika sudah mencapai max attempts, invalidasi OTP
            if ($otpRecord->fresh()->attempts >= $maxAttempts) {
                $otpRecord->update(['is_used' => 1]);
            }

            return false;
        }

        // Berhasil: mark as used
        $otpRecord->update(['is_used' => 1]);

        return $otpRecord;
    }

    /**
     * Dispatch notifikasi OTP ke queue 'high'.
     *
     * @param  string  $rawOtp       OTP plaintext (6 digit)
     * @param  string  $destination  Nomor WA atau alamat email
     * @param  string  $channel      whatsapp | email
     */
    public function dispatchOtpNotification(string $rawOtp, string $destination, string $channel): void
    {
        $message = "Kode OTP SITRAS UNISYA Anda adalah: *{$rawOtp}*\n"
            . "Berlaku selama " . config('tracer.otp.expiry_minutes', 5) . " menit.\n"
            . "Jangan bagikan kode ini kepada siapa pun.";

        if ($channel === 'whatsapp') {
            SendWhatsAppNotification::dispatch($destination, $message, null)
                ->onQueue('high');
        } else {
            SendEmailNotification::dispatch($destination, 'Kode OTP SITRAS UNISYA', $message, null)
                ->onQueue('high');
        }
    }

    /**
     * Mask destination: tampilkan 2 karakter awal + 2 karakter akhir.
     * Contoh: 087812345678 → 08**********78
     *         ahmad@email.com → ah**@**.com
     */
    public function maskDestination(string $destination): string
    {
        if (str_contains($destination, '@')) {
            // Email: mask local part
            [$local, $domain] = explode('@', $destination, 2);
            $masked = substr($local, 0, 2) . str_repeat('*', max(0, strlen($local) - 2));
            $domainParts = explode('.', $domain);
            $maskedDomain = substr($domainParts[0], 0, 1) . str_repeat('*', max(0, strlen($domainParts[0]) - 1));
            return $masked . '@' . $maskedDomain . '.' . implode('.', array_slice($domainParts, 1));
        }

        // Phone / NIM: 2 awal + bintang + 2 akhir
        $len = strlen($destination);
        if ($len <= 4) {
            return $destination;
        }
        return substr($destination, 0, 2) . str_repeat('*', $len - 4) . substr($destination, -2);
    }
}

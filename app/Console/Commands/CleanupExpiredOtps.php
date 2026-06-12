<?php

namespace App\Console\Commands;

use App\Models\Otp;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * CleanupExpiredOtps — Hapus permanen OTP yang sudah kadaluarsa atau sudah digunakan.
 *
 * Jadwal: setiap jam (hourly).
 * Logika:
 *   - Hapus OTP dengan expires_at < now() (sudah kadaluarsa).
 *   - Hapus OTP dengan is_used = true dan updated_at > 1 jam lalu (sudah dikonsumsi).
 *
 * Alasan tidak pakai SoftDeletes untuk OTP:
 *   - Data OTP tidak perlu recovery, ini adalah tabel high-write transaksional.
 *   - Pembersihan rutin menjaga tabel tetap ringan.
 *
 * php artisan otp:cleanup [--dry-run]
 */
class CleanupExpiredOtps extends Command
{
    protected $signature = 'otp:cleanup
                            {--dry-run : Hitung saja berapa OTP yang akan dihapus tanpa menghapus}';

    protected $description = 'Hapus OTP kadaluarsa dan OTP yang sudah digunakan dari database';

    public function handle(): int
    {
        $now      = Carbon::now();
        $isDryRun = $this->option('dry-run');

        // --- Hitung expired OTPs ---
        $expiredCount = Otp::query()
            ->where('expires_at', '<', $now)
            ->count();

        // --- Hitung used OTPs (lebih dari 1 jam lalu) ---
        $usedCount = Otp::query()
            ->where('is_used', true)
            ->where('updated_at', '<', $now->copy()->subHour())
            ->count();

        $this->info("[CleanupExpiredOtps] DryRun: " . ($isDryRun ? 'yes' : 'no'));
        $this->info("  OTP kadaluarsa: {$expiredCount}");
        $this->info("  OTP sudah digunakan (>1 jam): {$usedCount}");
        $this->info("  Total yang akan dihapus: " . ($expiredCount + $usedCount));

        if ($isDryRun) {
            return self::SUCCESS;
        }

        $deletedExpired = Otp::query()
            ->where('expires_at', '<', $now)
            ->delete();

        $deletedUsed = Otp::query()
            ->where('is_used', true)
            ->where('updated_at', '<', $now->copy()->subHour())
            ->delete();

        $total = $deletedExpired + $deletedUsed;

        $this->info("[CleanupExpiredOtps] Selesai. Terhapus: {$total} OTP.");

        Log::info('[CleanupExpiredOtps] Completed', [
            'deleted_expired' => $deletedExpired,
            'deleted_used'    => $deletedUsed,
            'total'           => $total,
        ]);

        return self::SUCCESS;
    }
}

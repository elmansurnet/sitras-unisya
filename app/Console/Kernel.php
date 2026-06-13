<?php

namespace App\Console;

use App\Console\Commands\CleanupExpiredOtps;
use App\Console\Commands\CloseExpiredSurveyPeriods;
use App\Console\Commands\GenerateMonthlyReport;
use App\Console\Commands\SendSurveyReminders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Console Kernel — SITRAS UNISYA
 *
 * Mendaftarkan semua Artisan command dan jadwal scheduler.
 *
 * Timezone default: Asia/Jakarta (WIB, UTC+7) sesuai lokasi server UNISYA.
 * Semua jadwal harus menyertakan ->timezone() secara eksplisit agar tidak
 * terpengaruh oleh perubahan nilai APP_TIMEZONE di .env.
 *
 * Cara menjalankan scheduler di production (crontab):
 *   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
 *
 * Cara test lokal:
 *   php artisan schedule:list                          — lihat semua jadwal terdaftar
 *   php artisan schedule:work                          — jalankan scheduler tiap menit (dev)
 *   php artisan survey:close-expired --dry-run
 *   php artisan survey:send-reminders --dry-run
 *   php artisan otp:cleanup --dry-run
 *   php artisan report:generate-monthly --dry-run
 *   php artisan report:generate-monthly --month=1 --year=2024 --format=pdf --dry-run
 */
class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * Urutan jadwal dari tengah malam ke siang:
     *   00:05 → close expired survey periods (setelah midnight rollover)
     *   01:00 → generate monthly report (tanggal 1, hitung data bulan lalu)
     *   08:00 → send survey reminders (jam kerja pagi)
     *   */30  → cleanup expired OTPs (setiap 30 menit)
     */
    protected function schedule(Schedule $schedule): void
    {
        // ----------------------------------------------------------------------
        // 1. Tutup Survey Period yang sudah melewati end_date
        //    Jadwal: setiap hari pukul 00:05 WIB
        //    Alasan 00:05 dan bukan 00:00: beri jeda kecil dari midnight agar
        //    proses DB rollover lainnya selesai terlebih dahulu.
        // ----------------------------------------------------------------------
        $schedule->command(CloseExpiredSurveyPeriods::class)
            ->dailyAt('00:05')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error(
                    '[Scheduler] CloseExpiredSurveyPeriods FAILED — cek log queue/laravel.log'
                );
            })
            ->appendOutputTo(storage_path('logs/scheduler-close-periods.log'));

        // ----------------------------------------------------------------------
        // 2. Generate Laporan Bulanan Otomatis
        //    Jadwal: tanggal 1 setiap bulan pukul 01:00 WIB
        //    Mem-generate laporan untuk bulan lalu secara otomatis dan
        //    men-dispatch GenerateReportExport ke queue 'low'.
        //    Berjalan 00:55 setelah CloseExpiredSurveyPeriods agar data
        //    status periode sudah ter-update sebelum snapshot diambil.
        // ----------------------------------------------------------------------
        $schedule->command(GenerateMonthlyReport::class, ['--format' => 'both'])
            ->monthlyOn(1, '01:00')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error(
                    '[Scheduler] GenerateMonthlyReport FAILED — cek log queue/laravel.log'
                );
            })
            ->appendOutputTo(storage_path('logs/scheduler-monthly-report.log'));

        // ----------------------------------------------------------------------
        // 3. Kirim Reminder Survei ke alumni/employer yang belum submit
        //    Jadwal: setiap hari pukul 08:00 WIB (jam kerja)
        //    Command hanya mengirim pada H-7, H-3, H-1 sebelum end_date.
        //    Hari-hari lain command berjalan tapi tidak mengirim notifikasi.
        // ----------------------------------------------------------------------
        $schedule->command(SendSurveyReminders::class)
            ->dailyAt('08:00')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error(
                    '[Scheduler] SendSurveyReminders FAILED — cek log queue/laravel.log'
                );
            })
            ->appendOutputTo(storage_path('logs/scheduler-survey-reminders.log'));

        // ----------------------------------------------------------------------
        // 4. Bersihkan OTP kadaluarsa dan OTP yang sudah digunakan
        //    Jadwal: setiap 30 menit
        //    OTP berlaku 5 menit → pembersihan setiap 30 menit sudah lebih
        //    dari cukup untuk menjaga tabel otps tetap ringan tanpa overhead
        //    run setiap menit.
        // ----------------------------------------------------------------------
        $schedule->command(CleanupExpiredOtps::class)
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error(
                    '[Scheduler] CleanupExpiredOtps FAILED — cek log queue/laravel.log'
                );
            });
            // Tidak perlu appendOutputTo — cleanup adalah operasi silent,
            // output hanya relevan untuk debug via schedule:work.
    }

    /**
     * Register the commands for the application.
     *
     * Laravel 12 dengan struktur PSR-4 akan auto-discover command di
     * app/Console/Commands/ jika menggunakan $commands atau load().
     * Kita gunakan load() agar semua file di Commands/ terdaftar otomatis
     * tanpa perlu mendaftarkan satu per satu secara manual.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}

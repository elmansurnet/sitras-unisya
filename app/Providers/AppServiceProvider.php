<?php

namespace App\Providers;

use App\Models\Alumni;
use App\Models\Employer;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Observers\AlumniObserver;
use App\Observers\EmployerObserver;
use App\Observers\SurveyResponseObserver;
use App\Observers\UserObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerRateLimiters();
        $this->registerObservers();
    }

    /**
     * Daftarkan 4 rate limiter sesuai 07_SECURITY.md §7.1
     *
     * 1. otp-request : 5 req/menit per IP
     * 2. auth        : 10 req/menit per IP
     * 3. api         : 60 req/menit per user (atau 20/menit per IP jika guest)
     * 4. export      : 5 req per 5 menit per user/IP
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('otp-request', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Terlalu banyak permintaan OTP. Coba lagi dalam 1 menit.',
                    ], 429);
                });
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Terlalu banyak percobaan login. Coba lagi dalam 1 menit.',
                    ], 429);
                });
        });

        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(60)->by($request->user()->id)
                : Limit::perMinute(20)->by($request->ip());
        });

        RateLimiter::for('export', function (Request $request) {
            return Limit::perMinutes(5, 5)->by($request->user()?->id ?? $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Batas export tercapai. Coba lagi dalam 5 menit.',
                    ], 429);
                });
        });
    }

    /**
     * Daftarkan Eloquent Observers.
     *
     * Semua observer sudah tersedia sebagai placeholder.
     * Body method diisi bertahap:
     *   - AlumniObserver  : sesi 2A
     *   - UserObserver    : sesi 2A
     *   - EmployerObserver: sesi 2B
     *   - SurveyResponseObserver: sesi 3A
     */
    private function registerObservers(): void
    {
        Alumni::observe(AlumniObserver::class);
        Employer::observe(EmployerObserver::class);
        SurveyResponse::observe(SurveyResponseObserver::class);
        User::observe(UserObserver::class);
    }
}

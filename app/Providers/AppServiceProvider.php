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
use App\Policies\AlumniPolicy;
use App\Policies\EmployerPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(
            \App\Repositories\Contracts\AlumniRepositoryInterface::class,
            \App\Repositories\AlumniRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\EmployerRepositoryInterface::class,
            \App\Repositories\EmployerRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // -----------------------------------------------------------------
        // OBSERVER REGISTRATIONS
        // -----------------------------------------------------------------
        Alumni::observe(AlumniObserver::class);
        Employer::observe(EmployerObserver::class);
        SurveyResponse::observe(SurveyResponseObserver::class);
        User::observe(UserObserver::class);

        // -----------------------------------------------------------------
        // POLICY REGISTRATIONS
        // -----------------------------------------------------------------
        Gate::policy(Alumni::class, AlumniPolicy::class);
        Gate::policy(Employer::class, EmployerPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        // -----------------------------------------------------------------
        // GATE DEFINITIONS — Role-based access shorthand
        // Digunakan di controller: $this->authorize('superadmin-only')
        // -----------------------------------------------------------------
        Gate::define('superadmin-only', function (User $user): bool {
            return $user->role === 'superadmin';
        });

        Gate::define('admin-or-superadmin', function (User $user): bool {
            return in_array($user->role, ['admin', 'superadmin'], true);
        });

        // -----------------------------------------------------------------
        // RATE LIMITERS — Sesuai 07_SECURITY.md §7 & 05_API.md §1.5
        //
        // Semua limiter mengembalikan JSON 429 dengan format sesuai
        // 05_API.md §1.3: { success, message, data }
        // -----------------------------------------------------------------

        /**
         * otp-request — 5 request per menit per IP
         * Digunakan di: POST /api/v1/auth/otp/request
         * Referensi: 07_SECURITY.md §7.1
         */
        RateLimiter::for('otp-request', function (Request $request): Limit {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Terlalu banyak permintaan OTP. Coba lagi dalam 1 menit.',
                        'data'    => null,
                    ], 429);
                });
        });

        /**
         * auth — 10 request per menit per IP
         * Digunakan di: POST /api/v1/auth/otp/verify, POST /api/v1/auth/login
         * Referensi: 07_SECURITY.md §7.1
         */
        RateLimiter::for('auth', function (Request $request): Limit {
            return Limit::perMinute(10)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Terlalu banyak percobaan login. Coba lagi dalam 1 menit.',
                        'data'    => null,
                    ], 429);
                });
        });

        /**
         * api — 60 request per menit per user (authenticated)
         *       20 request per menit per IP (unauthenticated/guest)
         * Digunakan di: semua route /admin/*, /alumni/*, /employer/*
         * Referensi: 07_SECURITY.md §7.1
         */
        RateLimiter::for('api', function (Request $request): Limit {
            return $request->user()
                ? Limit::perMinute(60)->by($request->user()->id)
                : Limit::perMinute(20)->by($request->ip());
        });

        /**
         * reports — 5 request per 5 menit per user
         * Digunakan di: POST /api/v1/admin/reports/generate/pdf
         *               POST /api/v1/admin/reports/generate/excel
         * Referensi: 05_API.md §1.5, routes/api.php throttle:reports
         */
        RateLimiter::for('reports', function (Request $request): Limit {
            return Limit::perMinutes(5, 5)
                ->by($request->user()?->id ?? $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Batas generate laporan tercapai. Coba lagi dalam 5 menit.',
                        'data'    => null,
                    ], 429);
                });
        });
    }
}

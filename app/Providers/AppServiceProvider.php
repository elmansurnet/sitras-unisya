<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\AlumniObserver;
use App\Observers\EmployerObserver;
use App\Observers\SurveyResponseObserver;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Security: prevent mass assignment vulnerabilities globally
        Model::shouldBeStrict(!app()->isProduction());

        // Force HTTPS di production
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        // Sanctum custom token model (jika diperlukan di masa depan)
        // Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // =========================================================================
        // Register Observers
        // AlumniObserver    — diisi sesi 2B (model Alumni belum ada)
        // EmployerObserver  — diisi sesi 2C (model Employer belum ada)
        // SurveyResponseObserver — diisi sesi 3B
        // UserObserver      — aktif sekarang
        // =========================================================================
        User::observe(UserObserver::class);

        // Observer berikut diaktifkan saat model-nya sudah dibuat di sesi terkait:
        // \App\Models\Alumni::observe(AlumniObserver::class);         // sesi 2B
        // \App\Models\Employer::observe(EmployerObserver::class);     // sesi 2C
        // \App\Models\SurveyResponse::observe(SurveyResponseObserver::class); // sesi 3B
    }
}

<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\OtpController;
use App\Http\Controllers\Api\V1\Public\PublicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — SITRAS UNISYA
| Base: /api/v1/
| Dokumentasi: 05_API.md
|--------------------------------------------------------------------------
|
| Middleware order WAJIB untuk protected routes:
|   auth:sanctum → EnsureAccountActive → CheckRole
|
*/

Route::prefix('v1')->group(function () {

    // =========================================================================
    // PUBLIC — Tanpa auth middleware
    // =========================================================================
    Route::prefix('public')->name('api.v1.public.')->group(function () {
        Route::get('employer-token/{token}/validate', [PublicController::class, 'validateEmployerToken'])
            ->name('employer-token.validate');
        Route::get('study-programs',  [PublicController::class, 'masterStudyPrograms'])
            ->name('study-programs');
        Route::get('faculties',       [PublicController::class, 'masterFaculties'])
            ->name('faculties');
        Route::get('industry-sectors', [PublicController::class, 'masterIndustrySectors'])
            ->name('industry-sectors');
        Route::get('graduation-years', [PublicController::class, 'masterGraduationYears'])
            ->name('graduation-years');
        Route::get('salary-ranges',   [PublicController::class, 'masterSalaryRanges'])
            ->name('salary-ranges');
    });

    // =========================================================================
    // AUTH — Endpoint autentikasi
    // =========================================================================
    Route::prefix('auth')->name('api.v1.auth.')->group(function () {

        // --- OTP (untuk alumni) ---
        // Rate limit: throttle:otp-request
        Route::middleware('throttle:otp-request')->group(function () {
            Route::post('otp/request', [OtpController::class, 'requestOtp'])
                ->name('otp.request');
        });

        // Rate limit: throttle:auth
        Route::middleware('throttle:auth')->group(function () {
            Route::post('otp/verify', [OtpController::class, 'verifyOtp'])
                ->name('otp.verify');

            // --- Login Superadmin / Admin ---
            Route::post('login', [AuthController::class, 'login'])
                ->name('login');
        });

        // --- Login Employer via token di URL ---
        // ValidateEmployerToken middleware memvalidasi token sebelum controller
        Route::middleware(['App\Http\Middleware\ValidateEmployerToken'])
            ->get('employer/token/{token}', [AuthController::class, 'loginEmployer'])
            ->name('employer.token.login');

        // --- Protected auth routes ---
        Route::middleware(['auth:sanctum', 'App\Http\Middleware\EnsureAccountActive'])
            ->group(function () {
                Route::post('logout', [AuthController::class, 'logout'])->name('logout');
                Route::get('me',     [AuthController::class, 'me'])->name('me');
            });
    });

    // =========================================================================
    // ADMIN — Superadmin & Admin
    // Middleware order: auth:sanctum → EnsureAccountActive → CheckRole
    // Diisi di sesi 2A+
    // =========================================================================
    Route::middleware([
        'auth:sanctum',
        'App\Http\Middleware\EnsureAccountActive',
        'App\Http\Middleware\CheckRole:superadmin,admin',
        'App\Http\Middleware\LogActivity',
        'throttle:api',
    ])->prefix('admin')->name('api.v1.admin.')->group(function () {
        // Controller-controller admin akan didaftarkan di sesi 2A–3B
    });

    // =========================================================================
    // ALUMNI — Role: alumni
    // =========================================================================
    Route::middleware([
        'auth:sanctum',
        'App\Http\Middleware\EnsureAccountActive',
        'App\Http\Middleware\CheckRole:alumni',
        'throttle:api',
    ])->prefix('alumni')->name('api.v1.alumni.')->group(function () {
        // Controller alumni akan didaftarkan di sesi 2C+
    });

    // =========================================================================
    // EMPLOYER — Role: employer (via token Sanctum)
    // =========================================================================
    Route::middleware([
        'auth:sanctum',
        'App\Http\Middleware\EnsureAccountActive',
        'App\Http\Middleware\CheckRole:employer',
        'throttle:api',
    ])->prefix('employer')->name('api.v1.employer.')->group(function () {
        // Controller employer akan didaftarkan di sesi 3A+
    });
});

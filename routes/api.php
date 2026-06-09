<?php

use App\Http\Controllers\Api\V1\Admin\AlumniController;
use App\Http\Controllers\Api\V1\Alumni\ProfileController;
use App\Http\Controllers\Api\V1\Alumni\WorkHistoryController;
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
        Route::get('study-programs',   [PublicController::class, 'masterStudyPrograms'])
            ->name('study-programs');
        Route::get('faculties',        [PublicController::class, 'masterFaculties'])
            ->name('faculties');
        Route::get('industry-sectors', [PublicController::class, 'masterIndustrySectors'])
            ->name('industry-sectors');
        Route::get('graduation-years', [PublicController::class, 'masterGraduationYears'])
            ->name('graduation-years');
        Route::get('salary-ranges',    [PublicController::class, 'masterSalaryRanges'])
            ->name('salary-ranges');
    });

    // =========================================================================
    // AUTH — Endpoint autentikasi
    // =========================================================================
    Route::prefix('auth')->name('api.v1.auth.')->group(function () {

        Route::middleware('throttle:otp-request')->group(function () {
            Route::post('otp/request', [OtpController::class, 'requestOtp'])->name('otp.request');
        });

        Route::middleware('throttle:auth')->group(function () {
            Route::post('otp/verify', [OtpController::class, 'verifyOtp'])->name('otp.verify');
            Route::post('login',      [AuthController::class, 'login'])->name('login');
        });

        Route::middleware(['App\Http\Middleware\ValidateEmployerToken'])
            ->get('employer/token/{token}', [AuthController::class, 'loginEmployer'])
            ->name('employer.token.login');

        Route::middleware(['auth:sanctum', 'App\Http\Middleware\EnsureAccountActive'])
            ->group(function () {
                Route::post('logout', [AuthController::class, 'logout'])->name('logout');
                Route::get('me',     [AuthController::class, 'me'])->name('me');
            });
    });

    // =========================================================================
    // ADMIN — Superadmin & Admin
    // =========================================================================
    Route::middleware([
        'auth:sanctum',
        'App\Http\Middleware\EnsureAccountActive',
        'App\Http\Middleware\CheckRole:superadmin,admin',
        'App\Http\Middleware\LogActivity',
        'throttle:api',
    ])->prefix('admin')->name('api.v1.admin.')->group(function () {

        // --- Alumni Management (2A.13) ---
        // Urutan PENTING: route spesifik (stats, import, export, template) HARUS
        // didaftarkan SEBELUM route dengan parameter {alumni} untuk menghindari konflik.
        Route::prefix('alumni')->name('alumni.')->group(function () {
            Route::get('stats',    [AlumniController::class, 'stats'])->name('stats');
            Route::get('export',   [AlumniController::class, 'export'])->name('export');
            Route::get('template', [AlumniController::class, 'importTemplate'])->name('template');
            Route::post('import',  [AlumniController::class, 'import'])->name('import');

            Route::get('/',                               [AlumniController::class, 'index'])->name('index');
            Route::post('/',                              [AlumniController::class, 'store'])->name('store');
            Route::get('/{alumni}',                       [AlumniController::class, 'show'])->name('show');
            Route::put('/{alumni}',                       [AlumniController::class, 'update'])->name('update');
            Route::delete('/{alumni}',                    [AlumniController::class, 'destroy'])->name('destroy');
            Route::post('/{alumni}/invite',               [AlumniController::class, 'sendInvitation'])->name('invite');
            Route::get('/{alumni}/work-histories',        [WorkHistoryController::class, 'indexForAdmin'])->name('work-histories.index');
        });

        // Controller-controller admin lain akan didaftarkan di sesi 2B–5A
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

        // Profil alumni (2A.13)
        Route::get('profile',        [ProfileController::class, 'show'])->name('profile.show');
        Route::put('profile',        [ProfileController::class, 'update'])->name('profile.update');
        Route::post('profile/photo', [ProfileController::class, 'uploadPhoto'])->name('profile.photo');

        // Riwayat pekerjaan (2A.13)
        Route::prefix('work-histories')->name('work-histories.')->group(function () {
            // Alumni butuh model Alumni untuk inject di controller → resolve dari user_id
            // Gunakan route dengan {alumni} yang diisi dari middleware (lihat ProfileController)
            // Sederhananya: ambil alumni dari auth user di controller
            Route::get('/',               [WorkHistoryController::class, 'index'])->name('index');
            Route::post('/{alumni}',      [WorkHistoryController::class, 'store'])->name('store');
            Route::put('/{alumni}/{workHistory}',    [WorkHistoryController::class, 'update'])->name('update');
            Route::delete('/{alumni}/{workHistory}', [WorkHistoryController::class, 'destroy'])->name('destroy');
        });
    });

    // =========================================================================
    // EMPLOYER — Role: employer
    // =========================================================================
    Route::middleware([
        'auth:sanctum',
        'App\Http\Middleware\EnsureAccountActive',
        'App\Http\Middleware\CheckRole:employer',
        'throttle:api',
    ])->prefix('employer')->name('api.v1.employer.')->group(function () {
        // Controller employer akan didaftarkan di sesi 2B
    });
});

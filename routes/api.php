<?php

use App\Http\Controllers\Api\V1\Admin\AlumniController;
use App\Http\Controllers\Api\V1\Admin\AuditLogController;
use App\Http\Controllers\Api\V1\Admin\EmployerController as AdminEmployerController;
use App\Http\Controllers\Api\V1\Admin\FacultyController;
use App\Http\Controllers\Api\V1\Admin\GraduationYearController;
use App\Http\Controllers\Api\V1\Admin\SettingController;
use App\Http\Controllers\Api\V1\Admin\StudyProgramController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Alumni\ProfileController as AlumniProfileController;
use App\Http\Controllers\Api\V1\Alumni\WorkHistoryController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\OtpController;
use App\Http\Controllers\Api\V1\Employer\ProfileController as EmployerProfileController;
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
                Route::get('me',      [AuthController::class, 'me'])->name('me');
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
        // Urutan PENTING: route spesifik HARUS didaftarkan SEBELUM route parameter {alumni}
        Route::prefix('alumni')->name('alumni.')->group(function () {
            Route::get('stats',    [AlumniController::class, 'stats'])->name('stats');
            Route::get('export',   [AlumniController::class, 'export'])->name('export');
            Route::get('template', [AlumniController::class, 'importTemplate'])->name('template');
            Route::post('import',  [AlumniController::class, 'import'])->name('import');

            Route::get('/',                         [AlumniController::class, 'index'])->name('index');
            Route::post('/',                        [AlumniController::class, 'store'])->name('store');
            Route::get('/{alumni}',                 [AlumniController::class, 'show'])->name('show');
            Route::put('/{alumni}',                 [AlumniController::class, 'update'])->name('update');
            Route::delete('/{alumni}',              [AlumniController::class, 'destroy'])->name('destroy');
            Route::post('/{alumni}/invite',         [AlumniController::class, 'sendInvitation'])->name('invite');
            Route::get('/{alumni}/work-histories',  [WorkHistoryController::class, 'indexForAdmin'])->name('work-histories.index');
        });

        // --- Employer Management (2B.10) ---
        // Urutan: static routes SEBELUM route parameter {employer}
        Route::prefix('employers')->name('employers.')->group(function () {
            Route::get('/',    [AdminEmployerController::class, 'index'])->name('index');
            Route::post('/',   [AdminEmployerController::class, 'store'])->name('store');

            Route::get('/{employer}',                    [AdminEmployerController::class, 'show'])->name('show');
            Route::put('/{employer}',                    [AdminEmployerController::class, 'update'])->name('update');
            Route::delete('/{employer}',                 [AdminEmployerController::class, 'destroy'])->name('destroy');
            Route::post('/{employer}/send-survey-token', [AdminEmployerController::class, 'sendSurveyToken'])->name('send-survey-token');
            Route::post('/{employer}/regenerate-token',  [AdminEmployerController::class, 'regenerateToken'])->name('regenerate-token');
        });

        // --- Faculty Management (2C.1) ---
        Route::prefix('faculties')->name('faculties.')->group(function () {
            Route::get('/',              [FacultyController::class, 'index'])->name('index');
            Route::post('/',             [FacultyController::class, 'store'])->name('store');
            Route::get('/{faculty}',     [FacultyController::class, 'show'])->name('show');
            Route::put('/{faculty}',     [FacultyController::class, 'update'])->name('update');
            Route::delete('/{faculty}',  [FacultyController::class, 'destroy'])->name('destroy');
        });

        // --- Study Program Management (2C.2) ---
        Route::prefix('study-programs')->name('study-programs.')->group(function () {
            Route::get('/',                      [StudyProgramController::class, 'index'])->name('index');
            Route::post('/',                     [StudyProgramController::class, 'store'])->name('store');
            Route::get('/{study_program}',       [StudyProgramController::class, 'show'])->name('show');
            Route::put('/{study_program}',       [StudyProgramController::class, 'update'])->name('update');
            Route::delete('/{study_program}',    [StudyProgramController::class, 'destroy'])->name('destroy');
        });

        // --- Graduation Year Management (2C.3) ---
        Route::prefix('graduation-years')->name('graduation-years.')->group(function () {
            Route::get('/',                          [GraduationYearController::class, 'index'])->name('index');
            Route::post('/',                         [GraduationYearController::class, 'store'])->name('store');
            Route::get('/{graduation_year}',         [GraduationYearController::class, 'show'])->name('show');
            Route::put('/{graduation_year}',         [GraduationYearController::class, 'update'])->name('update');
            Route::delete('/{graduation_year}',      [GraduationYearController::class, 'destroy'])->name('destroy');
        });

        // --- User Management (2C.4) — superadmin only (Gate di controller) ---
        // Urutan: static 'password' SEBELUM route parameter {user}
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/',                      [UserController::class, 'index'])->name('index');
            Route::post('/',                     [UserController::class, 'store'])->name('store');
            Route::get('/{user}',                [UserController::class, 'show'])->name('show');
            Route::put('/{user}',                [UserController::class, 'update'])->name('update');
            Route::delete('/{user}',             [UserController::class, 'destroy'])->name('destroy');
            Route::patch('/{user}/password',     [UserController::class, 'updatePassword'])->name('password');
        });

        // --- System Settings (2C.5) — superadmin only (Gate di controller) ---
        // Urutan: 'bulk' (static) SEBELUM route parameter {key}
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/',          [SettingController::class, 'index'])->name('index');
            Route::patch('/bulk',    [SettingController::class, 'bulkUpdate'])->name('bulk');
            Route::get('/{key}',     [SettingController::class, 'show'])->name('show');
            Route::put('/{key}',     [SettingController::class, 'update'])->name('update');
        });

        // --- Audit Log (2C.6) — superadmin only, read-only (Gate di controller) ---
        // Urutan: static 'modules' dan 'actions' SEBELUM route parameter {auditLog}
        Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
            Route::get('/',              [AuditLogController::class, 'index'])->name('index');
            Route::get('/modules',       [AuditLogController::class, 'modules'])->name('modules');
            Route::get('/actions',       [AuditLogController::class, 'actions'])->name('actions');
            Route::get('/{auditLog}',    [AuditLogController::class, 'show'])->name('show');
        });
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
        Route::get('profile',        [AlumniProfileController::class, 'show'])->name('profile.show');
        Route::put('profile',        [AlumniProfileController::class, 'update'])->name('profile.update');
        Route::post('profile/photo', [AlumniProfileController::class, 'uploadPhoto'])->name('profile.photo');

        // Riwayat pekerjaan (2A.13)
        Route::prefix('work-histories')->name('work-histories.')->group(function () {
            Route::get('/',                           [WorkHistoryController::class, 'index'])->name('index');
            Route::post('/{alumni}',                  [WorkHistoryController::class, 'store'])->name('store');
            Route::put('/{alumni}/{workHistory}',      [WorkHistoryController::class, 'update'])->name('update');
            Route::delete('/{alumni}/{workHistory}',   [WorkHistoryController::class, 'destroy'])->name('destroy');
        });
    });

    // =========================================================================
    // EMPLOYER — Role: employer (2B.10)
    // =========================================================================
    Route::middleware([
        'auth:sanctum',
        'App\Http\Middleware\EnsureAccountActive',
        'App\Http\Middleware\CheckRole:employer',
        'throttle:api',
    ])->prefix('employer')->name('api.v1.employer.')->group(function () {

        // Profil employer (2B.9)
        Route::get('profile',  [EmployerProfileController::class, 'show'])->name('profile.show');
        Route::put('profile',  [EmployerProfileController::class, 'update'])->name('profile.update');

        // Survey employer akan didaftarkan di sesi 4A
    });
});

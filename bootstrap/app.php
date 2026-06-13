<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\LogActivity;
use App\Http\Middleware\ValidateEmployerToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // =====================================================================
        // SANCTUM — SPA stateful authentication
        // Diperlukan agar Sanctum mengenali request dari frontend Vue (cookie-based)
        // =====================================================================
        $middleware->statefulApi();

        // =====================================================================
        // GLOBAL API MIDDLEWARE
        // Urutan eksekusi: atas ke bawah
        // =====================================================================
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // =====================================================================
        // GLOBAL WEB + API MIDDLEWARE
        // TrimStrings & ConvertEmptyStringsToNull berlaku untuk semua request
        // =====================================================================
        $middleware->append([
            \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        // =====================================================================
        // MIDDLEWARE ALIASES
        // Digunakan di routes/api.php sebagai string shorthand
        // Contoh: 'check-role:admin,superadmin' → CheckRole::class
        // =====================================================================
        $middleware->alias([
            'check-role'      => CheckRole::class,
            'ensure-active'   => EnsureAccountActive::class,
            'log-activity'    => LogActivity::class,
            'employer-token'  => ValidateEmployerToken::class,
        ]);

        // =====================================================================
        // THROTTLE — Rate limiting config
        // Definisi limit ada di AppServiceProvider::boot() via RateLimiter::for()
        // Mapping throttle:<name> ke limiter yang terdaftar
        // =====================================================================
        // throttle:otp-request  → 5 req/menit per IP    (07_SECURITY.md §7)
        // throttle:auth         → 10 req/menit per IP   (07_SECURITY.md §7)
        // throttle:api          → 60 req/menit per user (07_SECURITY.md §7)
        // throttle:reports      → 5 req/5menit per user (05_API.md §1.5)
        // Semua sudah terpasang di routes/api.php, tidak perlu mapping ulang di sini
        // karena Laravel 12 sudah otomatis resolve throttle:<name> ke RateLimiter::for(<name>)

        // =====================================================================
        // TRUST PROXIES — Untuk deployment di belakang Nginx reverse proxy
        // Pastikan IP, HTTPS, dan host header terbaca dengan benar
        // =====================================================================
        $middleware->trustProxies(at: '*');

        // =====================================================================
        // PREVENT MASS ASSIGNMENT VULNERABILITY VIA JSON
        // Secara default Laravel 12 sudah handle ini, tapi eksplisit lebih aman
        // =====================================================================
        $middleware->validateCsrfTokens(except: [
            'api/*',  // API menggunakan Sanctum token, bukan CSRF cookie
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // =====================================================================
        // API ERROR RESPONSE — Semua exception di /api/* dikembalikan sebagai JSON
        // Format sesuai 05_API.md §1.3 (success, message, data, errors)
        // =====================================================================
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e): bool {
            return $request->is('api/*') || $request->expectsJson();
        });

        // =====================================================================
        // CUSTOM JSON ERROR FORMAT untuk API
        // =====================================================================
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request): ?JsonResponse {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi Anda telah berakhir. Silakan login kembali.',
                    'data'    => null,
                ], 401);
            }
            return null;
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request): ?JsonResponse {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke fitur ini.',
                    'data'    => null,
                ], 403);
            }
            return null;
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, Request $request): ?JsonResponse {
            if ($request->is('api/*') || $request->expectsJson()) {
                $model = class_basename($e->getModel());
                return response()->json([
                    'success' => false,
                    'message' => "{$model} tidak ditemukan.",
                    'data'    => null,
                ], 404);
            }
            return null;
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request): ?JsonResponse {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data yang dikirim tidak valid.',
                    'data'    => null,
                    'errors'  => $e->errors(),
                ], 422);
            }
            return null;
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request): ?JsonResponse {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Terjadi kesalahan pada permintaan Anda.',
                    'data'    => null,
                ], $e->getStatusCode());
            }
            return null;
        });

        $exceptions->render(function (\Throwable $e, Request $request): ?JsonResponse {
            if ($request->is('api/*') || $request->expectsJson()) {
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                $message    = app()->isProduction()
                    ? 'Terjadi kesalahan internal. Silakan hubungi administrator.'
                    : $e->getMessage();

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'data'    => null,
                ], $statusCode);
            }
            return null;
        });
    })
    ->create();

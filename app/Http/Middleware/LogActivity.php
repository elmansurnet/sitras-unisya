<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * LogActivity Middleware
 * Tulis ke audit_logs untuk setiap request admin yang bersifat mutating.
 *
 * Rules:
 *  - Hanya log: POST, PUT, PATCH, DELETE
 *  - Skip GET (kecuali yang disertakan secara eksplisit)
 *  - Skip path: /auth/me, /dashboard/*
 *  - Hanya log jika user terautentikasi
 */
class LogActivity
{
    /**
     * Path prefix yang di-skip walaupun method bukan GET.
     */
    protected array $skipPaths = [
        'api/v1/auth/me',
        'api/v1/dashboard',
    ];

    /**
     * HTTP method yang di-log.
     */
    protected array $logMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Hanya proses setelah response (post-middleware)
        if (! $request->user()) {
            return $response;
        }

        $method = strtoupper($request->method());

        // Hanya log mutating methods
        if (! in_array($method, $this->logMethods, true)) {
            return $response;
        }

        // Skip path tertentu
        $path = $request->path();
        foreach ($this->skipPaths as $skipPath) {
            if (str_starts_with($path, $skipPath)) {
                return $response;
            }
        }

        // Tentukan modul dari path segment
        // Contoh: api/v1/admin/alumni → modul: Alumni
        $segments  = explode('/', $path);
        $moduleRaw = $segments[3] ?? $segments[2] ?? 'Unknown'; // setelah api/v1/{scope}
        $module    = ucfirst(str_replace('-', ' ', $moduleRaw));

        // Buat entri audit log
        AuditLog::record(
            action: strtolower($method),
            module: $module,
            modelId: null,
            oldValues: null,
            newValues: null,
        );

        return $response;
    }
}

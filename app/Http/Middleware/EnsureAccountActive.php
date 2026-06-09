<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureAccountActive Middleware
 * Pastikan users.is_active = 1 sebelum melanjutkan request.
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda telah dinonaktifkan. Hubungi administrator untuk informasi lebih lanjut.',
            ], 403);
        }

        return $next($request);
    }
}

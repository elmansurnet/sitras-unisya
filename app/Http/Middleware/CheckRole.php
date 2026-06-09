<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckRole Middleware
 * Implementasi sesuai 07_SECURITY.md §3.2
 * Penggunaan: ->middleware('role:superadmin,admin')
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user() || ! in_array($request->user()->role, $roles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke fitur ini.',
            ], 403);
        }

        return $next($request);
    }
}

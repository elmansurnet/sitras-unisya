<?php

namespace App\Http\Middleware;

use App\Models\Employer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ValidateEmployerToken Middleware
 * Implementasi PERSIS sesuai 07_SECURITY.md §5.2
 *
 * Memvalidasi token employer dari URL parameter {token}.
 * Menolak jika:
 *  - Token tidak ditemukan di DB
 *  - survey_token_expires_at sudah lewat
 *  - survey_status == 'selesai' (satu-survei use)
 *
 * Set survey_token_used_at jika pertama kali diakses.
 * Inject $employer ke dalam request untuk digunakan controller downstream.
 */
class ValidateEmployerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');

        $employer = Employer::where('survey_token', $token)
            ->where('survey_token_expires_at', '>', now())
            ->where('survey_status', '!=', 'selesai') // tidak bisa akses jika sudah selesai
            ->first();

        if (! $employer) {
            return response()->json([
                'success'    => false,
                'message'    => 'Link survei tidak valid atau sudah kedaluwarsa.',
                'error_code' => 'INVALID_EMPLOYER_TOKEN',
            ], 401);
        }

        // Catat waktu pertama akses jika belum pernah
        if (! $employer->survey_token_used_at) {
            $employer->update(['survey_token_used_at' => now()]);
        }

        // Inject employer ke dalam request agar bisa diakses controller
        $request->merge(['employer' => $employer]);

        return $next($request);
    }
}

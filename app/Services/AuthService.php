<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * AuthService
 * Mengelola logika autentikasi: login admin, login employer, logout.
 * Implementasi sesuai 07_SECURITY.md §3 & §5.
 */
class AuthService
{
    /**
     * Login untuk superadmin / admin via email + password.
     *
     * @throws \Exception Jika akun terkunci atau kredensial salah
     *
     * @return array { token, token_type, user }
     */
    public function loginAdmin(array $credentials): array
    {
        /** @var User|null $user */
        $user = User::where('email', $credentials['email'])->first();

        // Cek lockout
        if ($user && $user->isLocked()) {
            AuditLog::record('login_failed', 'Auth', $user->id, null, [
                'reason' => 'account_locked',
                'email'  => $credentials['email'],
            ]);

            throw new \Exception('LOCKED:' . $user->locked_until->toIso8601String());
        }

        // Attempt login
        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            if ($user) {
                $user->incrementLoginAttempts(); // auto-lock setelah 5 gagal
            }

            AuditLog::record('login_failed', 'Auth', $user?->id, null, [
                'email'  => $credentials['email'],
                'reason' => 'invalid_credentials',
            ]);

            throw new \Exception('INVALID_CREDENTIALS');
        }

        /** @var User $user */
        $user = Auth::user();

        // Reset login attempts & update last_login_at
        $user->resetLoginAttempts();
        $user->update(['last_login_at' => now()]);

        // Catat audit log
        AuditLog::record('login', 'Auth', $user->id, null, null);

        // Buat Sanctum token
        $token = $user->createToken('web')->plainTextToken;

        return [
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => [
                'id'            => $user->id,
                'name'          => $user->name,
                'role'          => $user->role,
                'email'         => $user->email,
                'last_login_at' => $user->last_login_at?->toIso8601String(),
            ],
        ];
    }

    /**
     * Login employer via survey_token.
     * Token sudah divalidasi oleh ValidateEmployerToken middleware sebelum method ini dipanggil.
     *
     * @param  Employer  $employer  Employer yang sudah divalidasi
     *
     * @return array { token, token_type, employer }
     */
    public function loginViaEmployerToken(Employer $employer): array
    {
        // Employer tidak punya User account — login sebagai User dengan role employer
        // atau jika employer punya user_id, gunakan itu
        $user = $employer->user;

        if (! $user) {
            throw new \Exception('EMPLOYER_USER_NOT_FOUND');
        }

        $token = $user->createToken('employer-survey')->plainTextToken;

        AuditLog::record('login', 'Auth', $user->id, null, [
            'via'         => 'employer_token',
            'employer_id' => $employer->id,
        ]);

        return [
            'token'      => $token,
            'token_type' => 'Bearer',
            'employer'   => [
                'id'                   => $employer->id,
                'company_name'         => $employer->company_name,
                'contact_person_name'  => $employer->contact_person_name,
            ],
            'survey_url' => '/employer/survey',
        ];
    }

    /**
     * Logout: hapus current access token.
     */
    public function logout(User $user): void
    {
        AuditLog::record('logout', 'Auth', $user->id, null, null);
        $user->currentAccessToken()->delete();
    }
}

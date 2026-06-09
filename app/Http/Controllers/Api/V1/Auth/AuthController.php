<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use App\Models\Employer;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AuthController
 * Login superadmin/admin, login employer via token, logout, me.
 * Response format sesuai 05_API.md §2.3 – §2.6
 */
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    /**
     * POST /api/v1/auth/login
     * 05_API.md §2.3
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->loginAdmin($request->only('email', 'password'));
        } catch (\Exception $e) {
            $msg = $e->getMessage();

            if (str_starts_with($msg, 'LOCKED:')) {
                $lockedUntil = str_replace('LOCKED:', '', $msg);
                return response()->json([
                    'success' => false,
                    'message' => 'Akun terkunci. Terlalu banyak percobaan login gagal.',
                    'data'    => [
                        'locked_until' => $lockedUntil,
                    ],
                ], 423);
            }

            return response()->json([
                'success' => false,
                'message' => 'Kredensial tidak valid.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data'    => $result,
        ], 200);
    }

    /**
     * GET /api/v1/auth/employer/token/{token}
     * 05_API.md §2.4
     * Token sudah divalidasi oleh ValidateEmployerToken middleware.
     */
    public function loginEmployer(Request $request, string $token): JsonResponse
    {
        /** @var Employer $employer */
        $employer = $request->input('employer');

        try {
            $result = $this->authService->loginViaEmployerToken($employer);
        } catch (\Exception $e) {
            return response()->json([
                'success'    => false,
                'message'    => 'Link survei tidak valid atau sudah kedaluwarsa.',
                'error_code' => 'INVALID_EMPLOYER_TOKEN',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Akses diberikan',
            'data'    => $result,
        ], 200);
    }

    /**
     * POST /api/v1/auth/logout
     * 05_API.md §2.5
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
        ], 200);
    }

    /**
     * GET /api/v1/auth/me
     * 05_API.md §2.6
     * Include alumni/employer nested data jika ada.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['alumni.studyProgram', 'employer']);

        $userData = [
            'id'            => $user->id,
            'name'          => $user->name,
            'role'          => $user->role,
            'email'         => $user->email,
            'is_active'     => $user->is_active,
            'last_login_at' => $user->last_login_at?->toIso8601String(),
        ];

        if ($user->alumni) {
            $userData['alumni'] = [
                'id'                  => $user->alumni->id,
                'nim'                 => $user->alumni->nim,
                'full_name'           => $user->alumni->full_name,
                'study_program'       => $user->alumni?->studyProgram?->name,
                'graduation_year'     => $user->alumni->graduation_year_id,
                'survey_status'       => $user->alumni->survey_status,
                'is_profile_complete' => $user->alumni->is_profile_complete ?? false,
            ];
        }

        if ($user->employer) {
            $userData['employer'] = [
                'id'                  => $user->employer->id,
                'company_name'        => $user->employer->company_name,
                'contact_person_name' => $user->employer->contact_person_name,
                'survey_status'       => $user->employer->survey_status,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Data pengguna berhasil diambil',
            'data'    => $userData,
        ], 200);
    }
}

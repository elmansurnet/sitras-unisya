<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * GET /api/v1/admin/users
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('superadmin-only');

        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = $request->integer('per_page', 15);
        $users   = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $users->items(),
            'meta'    => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/admin/users/{user}
     */
    public function show(User $user): JsonResponse
    {
        Gate::authorize('superadmin-only');

        return response()->json([
            'success' => true,
            'data'    => $user,
        ]);
    }

    /**
     * POST /api/v1/admin/users
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated             = $request->validated();
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        AuditLog::record(
            module: 'user',
            action: 'created',
            targetType: User::class,
            targetId: $user->id,
            newValues: $this->sanitizeForLog($user->toArray())
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil ditambahkan.',
            'data'    => $user,
        ], 201);
    }

    /**
     * PUT /api/v1/admin/users/{user}
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $oldValues = $this->sanitizeForLog($user->toArray());

        $user->update($request->validated());

        AuditLog::record(
            module: 'user',
            action: 'updated',
            targetType: User::class,
            targetId: $user->id,
            oldValues: $oldValues,
            newValues: $this->sanitizeForLog($user->fresh()->toArray())
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil diperbarui.',
            'data'    => $user->fresh(),
        ]);
    }

    /**
     * DELETE /api/v1/admin/users/{user}
     * Superadmin tidak bisa hapus dirinya sendiri.
     */
    public function destroy(User $user): JsonResponse
    {
        Gate::authorize('superadmin-only');

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ], 422);
        }

        $oldValues = $this->sanitizeForLog($user->toArray());
        $user->delete();

        AuditLog::record(
            module: 'user',
            action: 'deleted',
            targetType: User::class,
            targetId: $user->id,
            oldValues: $oldValues
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil dihapus.',
        ]);
    }

    /**
     * PATCH /api/v1/admin/users/{user}/password
     * Ganti password user oleh superadmin.
     */
    public function updatePassword(UpdatePasswordRequest $request, User $user): JsonResponse
    {
        $user->update([
            'password' => Hash::make($request->validated()['password']),
        ]);

        AuditLog::record(
            module: 'user',
            action: 'password_reset_by_admin',
            targetType: User::class,
            targetId: $user->id,
            newValues: ['reset_by' => auth()->id()]
        );

        return response()->json([
            'success' => true,
            'message' => 'Password pengguna berhasil diperbarui.',
        ]);
    }

    /**
     * Redact password & remember_token dari array sebelum masuk log.
     */
    private function sanitizeForLog(array $data): array
    {
        unset($data['password'], $data['remember_token']);
        return $data;
    }
}

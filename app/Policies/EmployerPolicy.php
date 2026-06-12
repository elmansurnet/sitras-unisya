<?php

namespace App\Policies;

use App\Models\Employer;
use App\Models\User;

/**
 * EmployerPolicy
 *
 * Matriks izin sesuai 07_SECURITY.md §3.3:
 * - superadmin : semua aksi (via before() shortcut)
 * - admin      : view, create, update, sendSurveyToken, regenerateToken (TIDAK bisa delete)
 * - alumni     : tidak ada akses ke resource admin employer
 * - employer   : akses profil sendiri saja (via ProfileController, bukan policy ini)
 */
class EmployerPolicy
{
    /**
     * Superadmin selalu diizinkan untuk semua aksi.
     */
    public function before(User $user): ?bool
    {
        if ($user->role === 'superadmin') {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function view(User $user, Employer $employer): bool
    {
        return $user->role === 'admin';
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Employer $employer): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Hanya superadmin yang boleh hapus.
     * Admin mendapat 403 Forbidden.
     * before() sudah handle superadmin, jadi ini selalu false untuk admin.
     */
    public function delete(User $user, Employer $employer): bool
    {
        return false;
    }

    public function sendSurveyToken(User $user, Employer $employer): bool
    {
        return $user->role === 'admin';
    }

    public function regenerateToken(User $user, Employer $employer): bool
    {
        return $user->role === 'admin';
    }
}

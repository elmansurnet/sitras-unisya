<?php

namespace App\Policies;

use App\Models\Alumni;
use App\Models\User;

/**
 * AlumniPolicy
 *
 * Matriks izin sesuai 07_SECURITY.md §3.3:
 * ---------------------------------------------------
 * | Aksi          | superadmin | admin | alumni      |
 * |---------------|------------|-------|-------------|
 * | viewAny       | ✅         | ✅    | ❌          |
 * | view          | ✅         | ✅    | self only   |
 * | create        | ✅         | ✅    | ❌          |
 * | update        | ✅         | ✅    | self only   |
 * | delete        | ✅         | ❌    | ❌          |
 * | import/export | ✅         | ✅    | ❌          |
 * ---------------------------------------------------
 */
class AlumniPolicy
{
    /**
     * Superadmin & admin bisa melihat daftar alumni.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['superadmin', 'admin'], true);
    }

    /**
     * Superadmin & admin bisa lihat semua.
     * Alumni hanya bisa lihat data dirinya sendiri.
     */
    public function view(User $user, Alumni $alumni): bool
    {
        if (in_array($user->role, ['superadmin', 'admin'], true)) {
            return true;
        }

        return $user->role === 'alumni' && $alumni->user_id === $user->id;
    }

    /**
     * Superadmin & admin bisa membuat alumni.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['superadmin', 'admin'], true);
    }

    /**
     * Superadmin & admin bisa update semua.
     * Alumni hanya bisa update data dirinya sendiri.
     */
    public function update(User $user, Alumni $alumni): bool
    {
        if (in_array($user->role, ['superadmin', 'admin'], true)) {
            return true;
        }

        return $user->role === 'alumni' && $alumni->user_id === $user->id;
    }

    /**
     * Hanya superadmin yang bisa hapus (soft delete).
     */
    public function delete(User $user, Alumni $alumni): bool
    {
        return $user->role === 'superadmin';
    }

    /**
     * Import & export: superadmin & admin.
     */
    public function import(User $user): bool
    {
        return in_array($user->role, ['superadmin', 'admin'], true);
    }

    public function export(User $user): bool
    {
        return in_array($user->role, ['superadmin', 'admin'], true);
    }
}

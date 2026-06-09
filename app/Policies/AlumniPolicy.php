<?php

namespace App\Policies;

use App\Models\Alumni;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AlumniPolicy
{
    use HandlesAuthorization;

    /**
     * Superadmin dan Admin bisa lihat daftar alumni.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['superadmin', 'admin'], true);
    }

    /**
     * Admin/Superadmin bisa lihat detail alumni mana pun.
     * Alumni hanya bisa lihat data miliknya sendiri.
     */
    public function view(User $user, Alumni $alumni): bool
    {
        if (in_array($user->role, ['superadmin', 'admin'], true)) {
            return true;
        }

        // Alumni melihat datanya sendiri
        return $user->role === 'alumni' && $user->id === $alumni->user_id;
    }

    /**
     * Hanya Superadmin dan Admin yang bisa membuat alumni.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['superadmin', 'admin'], true);
    }

    /**
     * Admin/Superadmin bisa update siapa pun.
     * Alumni hanya bisa update datanya sendiri.
     */
    public function update(User $user, Alumni $alumni): bool
    {
        if (in_array($user->role, ['superadmin', 'admin'], true)) {
            return true;
        }

        return $user->role === 'alumni' && $user->id === $alumni->user_id;
    }

    /**
     * Hanya Superadmin yang bisa hapus alumni.
     */
    public function delete(User $user, Alumni $alumni): bool
    {
        return $user->role === 'superadmin';
    }

    /**
     * Hanya Superadmin yang bisa restore alumni yang soft-deleted.
     */
    public function restore(User $user, Alumni $alumni): bool
    {
        return $user->role === 'superadmin';
    }

    /**
     * Hanya Superadmin yang bisa force-delete.
     */
    public function forceDelete(User $user, Alumni $alumni): bool
    {
        return $user->role === 'superadmin';
    }
}

<?php

namespace App\Policies;

use App\Models\Alumni;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AlumniPolicy
{
    use HandlesAuthorization;

    /**
     * Superadmin & admin selalu bisa melakukan semua aksi.
     * Alumni hanya bisa akses data diri sendiri.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperadmin() || $user->isAdmin()) {
            return true;
        }

        return null; // lanjutkan ke method spesifik
    }

    /**
     * Lihat daftar alumni (admin only — sudah di-handle `before`).
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Alumni dapat melihat profil diri sendiri.
     */
    public function view(User $user, Alumni $alumni): bool
    {
        return $user->id === $alumni->user_id;
    }

    /**
     * Hanya admin yang bisa membuat alumni baru.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Alumni hanya bisa update profil diri sendiri.
     */
    public function update(User $user, Alumni $alumni): bool
    {
        return $user->id === $alumni->user_id;
    }

    /**
     * Hanya admin yang bisa delete alumni (handled by before()).
     */
    public function delete(User $user, Alumni $alumni): bool
    {
        return false;
    }

    /**
     * Upload foto: alumni hanya bisa upload foto diri sendiri.
     */
    public function uploadPhoto(User $user, Alumni $alumni): bool
    {
        return $user->id === $alumni->user_id;
    }
}

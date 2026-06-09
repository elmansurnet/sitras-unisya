<?php

namespace App\Policies;

use App\Models\Alumni;
use App\Models\User;

/**
 * AlumniPolicy — Otorisasi akses resource Alumni.
 *
 * Matriks izin (07_SECURITY.md §3.3):
 * ┌──────────────────┬─────────────┬───────┬─────────┬──────────┐
 * │ Action           │ superadmin  │ admin │  alumni │ employer │
 * ├──────────────────┼─────────────┼───────┼─────────┼──────────┤
 * │ viewAny          │ ✅          │ ✅    │ ❌      │ ❌       │
 * │ view             │ ✅          │ ✅    │ own ✅  │ ❌       │
 * │ create           │ ✅          │ ✅    │ ❌      │ ❌       │
 * │ update           │ ✅          │ ✅    │ own ✅  │ ❌       │
 * │ delete           │ ✅          │ ✅    │ ❌      │ ❌       │
 * │ import           │ ✅          │ ✅    │ ❌      │ ❌       │
 * │ export           │ ✅          │ ✅    │ ❌      │ ❌       │
 * │ uploadPhoto      │ ✅          │ ✅    │ own ✅  │ ❌       │
 * └──────────────────┴─────────────┴───────┴─────────┴──────────┘
 */
class AlumniPolicy
{
    /**
     * Superadmin bypass semua gate.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return null;
    }

    /**
     * Lihat daftar semua alumni — hanya admin.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Lihat detail satu alumni — admin atau alumni pemilik data.
     */
    public function view(User $user, Alumni $alumni): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $alumni->user_id;
    }

    /**
     * Buat alumni baru — hanya admin.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Update alumni — admin atau alumni pemilik data.
     */
    public function update(User $user, Alumni $alumni): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $alumni->user_id;
    }

    /**
     * Hapus alumni — hanya admin.
     */
    public function delete(User $user, Alumni $alumni): bool
    {
        return $user->isAdmin();
    }

    /**
     * Import batch alumni dari Excel — hanya admin.
     */
    public function import(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Export data alumni ke Excel — hanya admin.
     */
    public function export(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Upload foto profil — admin atau alumni pemilik data.
     */
    public function uploadPhoto(User $user, Alumni $alumni): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $alumni->user_id;
    }
}

<?php

namespace App\Policies;

use App\Models\Alumni;
use App\Models\User;

/**
 * AlumniPolicy
 * Matriks izin sesuai 07_SECURITY.md §3.3:
 * - viewAny, view, create, update : superadmin, admin
 * - delete                        : superadmin saja
 * - viewOwn, updateOwn            : alumni (pemilik data)
 */
class AlumniPolicy
{
    /**
     * Superadmin melewati semua gate.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }
        return null;
    }

    /**
     * Lihat daftar alumni (admin dashboard).
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Lihat detail alumni tertentu.
     * Admin bisa lihat semua; alumni hanya bisa lihat miliknya sendiri.
     */
    public function view(User $user, Alumni $alumni): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $alumni->user_id;
    }

    /**
     * Tambah alumni baru (admin only).
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Update data alumni.
     * Admin bisa update semua; alumni hanya miliknya sendiri.
     */
    public function update(User $user, Alumni $alumni): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $alumni->user_id;
    }

    /**
     * Hapus alumni — superadmin saja (ditangani before() untuk superadmin).
     * Admin dan alumni tidak boleh.
     */
    public function delete(User $user, Alumni $alumni): bool
    {
        return false;
    }

    /**
     * Import alumni massal (admin).
     */
    public function import(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Export alumni (admin).
     */
    public function export(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Upload foto profil — alumni miliknya sendiri atau admin.
     */
    public function uploadPhoto(User $user, Alumni $alumni): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $alumni->user_id;
    }

    /**
     * Kirim undangan survei ke alumni (admin).
     */
    public function sendInvitation(User $user): bool
    {
        return $user->isAdmin();
    }
}

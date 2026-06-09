<?php

namespace App\Observers;

use App\Models\User;

/**
 * UserObserver
 *
 * Placeholder — implementasi diisi pada sesi 2A.
 * Akan menangani: audit log untuk create/update/delete user.
 */
class UserObserver
{
    public function created(User $user): void
    {
        // TODO sesi 2A: AuditLog::record('create', 'user', $user->id, null, $user->toArray())
    }

    public function updated(User $user): void
    {
        // TODO sesi 2A: AuditLog::record('update', 'user', $user->id, $user->getOriginal(), $user->getDirty())
    }

    public function deleted(User $user): void
    {
        // TODO sesi 2A: AuditLog::record('delete', 'user', $user->id, $user->toArray(), null)
    }

    public function restored(User $user): void
    {
        // TODO sesi 2A
    }

    public function forceDeleted(User $user): void
    {
        // TODO sesi 2A
    }
}

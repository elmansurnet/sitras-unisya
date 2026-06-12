<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        AuditLog::record(
            action: 'create',
            module: 'User',
            modelId: $user->id,
            oldValues: null,
            newValues: $user->only(['name', 'email', 'role', 'is_active']),
            modelType: User::class,
        );
    }

    public function updated(User $user): void
    {
        if (! $user->isDirty()) {
            return;
        }

        // Jangan log perubahan password/remember_token (sensitif)
        $dirty    = collect($user->getDirty())->except(['password', 'remember_token', 'login_attempts', 'locked_until', 'last_login_at'])->all();
        $original = collect($user->getOriginal())->except(['password', 'remember_token', 'login_attempts', 'locked_until', 'last_login_at'])->all();

        if (empty($dirty)) {
            return;
        }

        AuditLog::record(
            action: 'update',
            module: 'User',
            modelId: $user->id,
            oldValues: $original,
            newValues: $dirty,
            modelType: User::class,
        );
    }

    public function deleted(User $user): void
    {
        AuditLog::record(
            action: 'delete',
            module: 'User',
            modelId: $user->id,
            oldValues: $user->only(['name', 'email', 'role']),
            newValues: null,
            modelType: User::class,
        );
    }

    public function restored(User $user): void
    {
        AuditLog::record(
            action: 'restore',
            module: 'User',
            modelId: $user->id,
            oldValues: null,
            newValues: $user->only(['name', 'email', 'role', 'is_active']),
            modelType: User::class,
        );
    }

    public function forceDeleted(User $user): void
    {
        AuditLog::record(
            action: 'force_delete',
            module: 'User',
            modelId: $user->id,
            oldValues: $user->only(['name', 'email', 'role']),
            newValues: null,
            modelType: User::class,
        );
    }
}

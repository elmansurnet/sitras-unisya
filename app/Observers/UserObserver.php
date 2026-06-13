<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        AuditLog::record(
            module: 'user',
            action: 'created',
            modelType: User::class,
            modelId: $user->id,
            newValues: $this->sanitize($user->toArray())
        );
    }

    public function updated(User $user): void
    {
        AuditLog::record(
            module: 'user',
            action: 'updated',
            modelType: User::class,
            modelId: $user->id,
            oldValues: $this->sanitize($user->getOriginal()),
            newValues: $this->sanitize($user->getChanges())
        );
    }

    public function deleted(User $user): void
    {
        AuditLog::record(
            module: 'user',
            action: 'deleted',
            modelType: User::class,
            modelId: $user->id,
            oldValues: $this->sanitize($user->toArray())
        );
    }

    /**
     * Redact password dari log — tidak boleh tersimpan di audit_logs.
     */
    private function sanitize(array $data): array
    {
        unset($data['password'], $data['remember_token']);
        return $data;
    }
}

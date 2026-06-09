<?php

namespace App\Observers;

use App\Models\Alumni;
use App\Models\AuditLog;

/**
 * AlumniObserver — implementasi penuh.
 * Mencatat semua perubahan data alumni ke audit_logs.
 */
class AlumniObserver
{
    public function created(Alumni $alumni): void
    {
        AuditLog::record(
            action   : 'create',
            module   : 'alumni',
            modelId  : $alumni->id,
            oldValues: null,
            newValues: $alumni->toArray(),
            modelType: Alumni::class,
        );
    }

    public function updated(Alumni $alumni): void
    {
        if (empty($alumni->getDirty())) {
            return;
        }

        $changed = array_keys($alumni->getDirty());
        $old     = array_intersect_key($alumni->getOriginal(), array_flip($changed));
        $new     = $alumni->only($changed);

        // Jangan log perubahan kolom non-kritis (timestamps)
        $skip = ['updated_at'];
        $old  = collect($old)->except($skip)->toArray();
        $new  = collect($new)->except($skip)->toArray();

        if (empty($old) && empty($new)) {
            return;
        }

        AuditLog::record(
            action   : 'update',
            module   : 'alumni',
            modelId  : $alumni->id,
            oldValues: $old,
            newValues: $new,
            modelType: Alumni::class,
        );
    }

    public function deleted(Alumni $alumni): void
    {
        AuditLog::record(
            action   : 'delete',
            module   : 'alumni',
            modelId  : $alumni->id,
            oldValues: $alumni->toArray(),
            newValues: null,
            modelType: Alumni::class,
        );
    }

    public function restored(Alumni $alumni): void
    {
        AuditLog::record(
            action   : 'restore',
            module   : 'alumni',
            modelId  : $alumni->id,
            oldValues: ['deleted_at' => $alumni->getOriginal('deleted_at')],
            newValues: ['deleted_at' => null],
            modelType: Alumni::class,
        );
    }

    public function forceDeleted(Alumni $alumni): void
    {
        AuditLog::record(
            action   : 'force_delete',
            module   : 'alumni',
            modelId  : $alumni->id,
            oldValues: $alumni->toArray(),
            newValues: null,
            modelType: Alumni::class,
        );
    }
}

<?php

namespace App\Observers;

use App\Models\Alumni;
use App\Models\AuditLog;

class AlumniObserver
{
    public function created(Alumni $alumni): void
    {
        AuditLog::record(
            action: 'create',
            module: 'Alumni',
            modelId: $alumni->id,
            oldValues: null,
            newValues: $alumni->only([
                'nim', 'full_name', 'study_program_id', 'graduation_year_id',
            ]),
            modelType: Alumni::class,
        );
    }

    public function updated(Alumni $alumni): void
    {
        if ($alumni->isDirty()) {
            AuditLog::record(
                action: 'update',
                module: 'Alumni',
                modelId: $alumni->id,
                oldValues: $alumni->getOriginal(),
                newValues: $alumni->getDirty(),
                modelType: Alumni::class,
            );
        }
    }

    public function deleted(Alumni $alumni): void
    {
        AuditLog::record(
            action: 'delete',
            module: 'Alumni',
            modelId: $alumni->id,
            oldValues: $alumni->only(['nim', 'full_name']),
            newValues: null,
            modelType: Alumni::class,
        );
    }

    public function restored(Alumni $alumni): void
    {
        AuditLog::record(
            action: 'restore',
            module: 'Alumni',
            modelId: $alumni->id,
            oldValues: null,
            newValues: $alumni->only(['nim', 'full_name']),
            modelType: Alumni::class,
        );
    }

    public function forceDeleted(Alumni $alumni): void
    {
        AuditLog::record(
            action: 'force_delete',
            module: 'Alumni',
            modelId: $alumni->id,
            oldValues: $alumni->only(['nim', 'full_name']),
            newValues: null,
            modelType: Alumni::class,
        );
    }
}

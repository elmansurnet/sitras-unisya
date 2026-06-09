<?php

namespace App\Observers;

use App\Models\Alumni;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * AlumniObserver
 *
 * Mencatat semua perubahan data alumni ke audit_logs.
 * Diaktifkan dari AppServiceProvider setelah model Alumni tersedia (Sesi 2A).
 *
 * Implementasi sesuai 07_SECURITY.md §8.2 (Observer pattern untuk audit trail).
 * AuditLog::record() sesuai 07_SECURITY.md §8.3.
 */
class AlumniObserver
{
    /**
     * Alumni baru dibuat.
     */
    public function created(Alumni $alumni): void
    {
        AuditLog::record(
            action: 'create',
            module: 'alumni',
            modelId: $alumni->id,
            oldValues: null,
            newValues: $alumni->getAttributes(),
            modelType: Alumni::class,
        );
    }

    /**
     * Data alumni diperbarui.
     * Hanya catat kolom yang benar-benar berubah.
     */
    public function updated(Alumni $alumni): void
    {
        $dirty = $alumni->getDirty();

        // Jangan catat perubahan timestamp murni
        unset($dirty['updated_at']);

        if (empty($dirty)) {
            return;
        }

        $oldValues = [];
        foreach (array_keys($dirty) as $key) {
            $oldValues[$key] = $alumni->getOriginal($key);
        }

        AuditLog::record(
            action: 'update',
            module: 'alumni',
            modelId: $alumni->id,
            oldValues: $oldValues,
            newValues: $dirty,
            modelType: Alumni::class,
        );
    }

    /**
     * Alumni di-soft-delete.
     */
    public function deleted(Alumni $alumni): void
    {
        AuditLog::record(
            action: 'delete',
            module: 'alumni',
            modelId: $alumni->id,
            oldValues: ['deleted_at' => null],
            newValues: ['deleted_at' => $alumni->deleted_at],
            modelType: Alumni::class,
        );
    }

    /**
     * Alumni di-restore dari soft-delete.
     */
    public function restored(Alumni $alumni): void
    {
        AuditLog::record(
            action: 'restore',
            module: 'alumni',
            modelId: $alumni->id,
            oldValues: null,
            newValues: ['restored_at' => now()],
            modelType: Alumni::class,
        );
    }

    /**
     * Alumni dihapus permanen (force delete).
     */
    public function forceDeleted(Alumni $alumni): void
    {
        AuditLog::record(
            action: 'force_delete',
            module: 'alumni',
            modelId: $alumni->id,
            oldValues: $alumni->getOriginal(),
            newValues: null,
            modelType: Alumni::class,
        );
    }
}

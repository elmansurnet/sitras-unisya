<?php

namespace App\Observers;

use App\Models\Alumni;

/**
 * AlumniObserver
 *
 * Placeholder — implementasi diisi pada sesi 2A.
 * Akan menangani: sync user email/phone, audit log create/update/delete.
 */
class AlumniObserver
{
    public function created(Alumni $alumni): void
    {
        // TODO sesi 2A: AuditLog::record('create', 'alumni', ...)
    }

    public function updated(Alumni $alumni): void
    {
        // TODO sesi 2A: AuditLog::record('update', 'alumni', ...)
    }

    public function deleted(Alumni $alumni): void
    {
        // TODO sesi 2A: AuditLog::record('delete', 'alumni', ...)
    }

    public function restored(Alumni $alumni): void
    {
        // TODO sesi 2A
    }

    public function forceDeleted(Alumni $alumni): void
    {
        // TODO sesi 2A
    }
}

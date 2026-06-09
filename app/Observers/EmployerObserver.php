<?php

namespace App\Observers;

use App\Models\Employer;

/**
 * EmployerObserver
 *
 * Placeholder — implementasi diisi pada sesi 2B.
 * Akan menangani: token generation, audit log create/update/delete.
 */
class EmployerObserver
{
    public function created(Employer $employer): void
    {
        // TODO sesi 2B: generate survey_token, AuditLog::record('create', 'employer', ...)
    }

    public function updated(Employer $employer): void
    {
        // TODO sesi 2B: AuditLog::record('update', 'employer', ...)
    }

    public function deleted(Employer $employer): void
    {
        // TODO sesi 2B: AuditLog::record('delete', 'employer', ...)
    }

    public function restored(Employer $employer): void
    {
        // TODO sesi 2B
    }

    public function forceDeleted(Employer $employer): void
    {
        // TODO sesi 2B
    }
}

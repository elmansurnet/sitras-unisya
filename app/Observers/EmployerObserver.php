<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Employer;

class EmployerObserver
{
    public function created(Employer $employer): void
    {
        AuditLog::record(
            action: 'create',
            module: 'Employer',
            modelId: $employer->id,
            oldValues: null,
            newValues: $employer->only([
                'company_name', 'contact_name', 'contact_email',
                'industry_sector_id', 'company_size',
            ]),
            modelType: Employer::class,
        );
    }

    public function updated(Employer $employer): void
    {
        if ($employer->isDirty()) {
            // Jangan log perubahan survey_token (sensitif)
            $dirty   = collect($employer->getDirty())->except(['survey_token'])->all();
            $original = collect($employer->getOriginal())->except(['survey_token'])->all();

            if (empty($dirty)) {
                return;
            }

            AuditLog::record(
                action: 'update',
                module: 'Employer',
                modelId: $employer->id,
                oldValues: $original,
                newValues: $dirty,
                modelType: Employer::class,
            );
        }
    }

    public function deleted(Employer $employer): void
    {
        AuditLog::record(
            action: 'delete',
            module: 'Employer',
            modelId: $employer->id,
            oldValues: $employer->only(['company_name', 'contact_email']),
            newValues: null,
            modelType: Employer::class,
        );
    }

    public function restored(Employer $employer): void
    {
        AuditLog::record(
            action: 'restore',
            module: 'Employer',
            modelId: $employer->id,
            oldValues: null,
            newValues: $employer->only(['company_name', 'contact_email']),
            modelType: Employer::class,
        );
    }

    public function forceDeleted(Employer $employer): void
    {
        AuditLog::record(
            action: 'force_delete',
            module: 'Employer',
            modelId: $employer->id,
            oldValues: $employer->only(['company_name', 'contact_email']),
            newValues: null,
            modelType: Employer::class,
        );
    }
}

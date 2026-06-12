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
            modelType: Employer::class,
            oldValues: null,
            newValues: $employer->only([
                'company_name', 'company_type', 'industry_sector',
                'address_city', 'address_province', 'survey_status',
            ])
        );
    }

    public function updated(Employer $employer): void
    {
        if (! $employer->isDirty()) {
            return;
        }

        // Jangan catat perubahan survey_token ke audit log (sensitive)
        $dirty = collect($employer->getDirty())
            ->except(['survey_token', 'survey_token_expires_at', 'survey_token_used_at', 'updated_at'])
            ->toArray();

        if (empty($dirty)) {
            return;
        }

        AuditLog::record(
            action: 'update',
            module: 'Employer',
            modelId: $employer->id,
            modelType: Employer::class,
            oldValues: array_intersect_key($employer->getOriginal(), $dirty),
            newValues: $dirty
        );
    }

    public function deleted(Employer $employer): void
    {
        AuditLog::record(
            action: 'delete',
            module: 'Employer',
            modelId: $employer->id,
            modelType: Employer::class,
            oldValues: $employer->only(['company_name', 'company_type', 'survey_status']),
            newValues: null
        );
    }
}

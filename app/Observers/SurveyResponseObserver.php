<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\SurveyResponse;

class SurveyResponseObserver
{
    public function created(SurveyResponse $response): void
    {
        AuditLog::record(
            action: 'create',
            module: 'survey_response',
            modelId: $response->id,
            oldValues: null,
            newValues: [
                'survey_period_id'  => $response->survey_period_id,
                'questionnaire_id'  => $response->questionnaire_id,
                'alumni_id'         => $response->alumni_id,
                'employer_id'       => $response->employer_id,
                'respondent_type'   => $response->respondent_type,
                'status'            => $response->status,
            ],
            modelType: SurveyResponse::class,
        );
    }

    public function updated(SurveyResponse $response): void
    {
        if (! $response->isDirty()) {
            return;
        }

        $dirty = $response->getDirty();

        // Tangkap transisi status secara eksplisit untuk audit trail
        $action = 'update';
        if (isset($dirty['status'])) {
            $action = match ($dirty['status']) {
                'submitted' => 'submit',
                'draft'     => 'save_draft',
                default     => 'update',
            };
        }

        AuditLog::record(
            action: $action,
            module: 'survey_response',
            modelId: $response->id,
            oldValues: array_intersect_key($response->getOriginal(), $dirty),
            newValues: $dirty,
            modelType: SurveyResponse::class,
        );
    }

    public function deleted(SurveyResponse $response): void
    {
        AuditLog::record(
            action: 'delete',
            module: 'survey_response',
            modelId: $response->id,
            oldValues: [
                'survey_period_id' => $response->survey_period_id,
                'alumni_id'        => $response->alumni_id,
                'employer_id'      => $response->employer_id,
                'status'           => $response->status,
            ],
            newValues: null,
            modelType: SurveyResponse::class,
        );
    }
}

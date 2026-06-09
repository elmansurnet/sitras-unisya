<?php

namespace App\Observers;

use App\Models\SurveyResponse;

/**
 * SurveyResponseObserver
 *
 * Placeholder — implementasi diisi pada sesi 3A.
 * Akan menangani: update alumni employment status, audit log.
 */
class SurveyResponseObserver
{
    public function created(SurveyResponse $surveyResponse): void
    {
        // TODO sesi 3A
    }

    public function updated(SurveyResponse $surveyResponse): void
    {
        // TODO sesi 3A: jika status → 'submitted', update alumni last_survey_at
    }

    public function deleted(SurveyResponse $surveyResponse): void
    {
        // TODO sesi 3A
    }
}

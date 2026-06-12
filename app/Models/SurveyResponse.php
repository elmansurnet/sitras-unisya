<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyResponse extends Model
{
    protected $fillable = [
        'questionnaire_id',
        'survey_period_id',
        'respondent_type',
        'alumni_id',
        'employer_id',
        'status',
        'started_at',
        'submitted_at',
        'ip_address',
        'user_agent',
        'completion_percentage',
    ];

    protected $casts = [
        'started_at'            => 'datetime',
        'submitted_at'          => 'datetime',
        'completion_percentage' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function surveyPeriod(): BelongsTo
    {
        return $this->belongsTo(SurveyPeriod::class);
    }

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class);
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'selesai';
    }

    public function getRespondentAttribute(): Alumni|Employer|null
    {
        return $this->respondent_type === 'alumni'
            ? $this->alumni
            : $this->employer;
    }
}

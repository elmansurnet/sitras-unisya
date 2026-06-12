<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyPeriod extends Model
{
    protected $fillable = [
        'name',
        'year',
        'start_date',
        'end_date',
        'target_graduation_years',
        'status',
        'description',
        'created_by',
    ];

    protected $casts = [
        'target_graduation_years' => 'array',
        'start_date'              => 'date',
        'end_date'                => 'date',
        'year'                    => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Alumni yang terdaftar dalam periode ini (via pivot alumni_survey_period).
     */
    public function alumni(): BelongsToMany
    {
        return $this->belongsToMany(Alumni::class, 'alumni_survey_period')
            ->withPivot([
                'invitation_sent_at',
                'invitation_channel',
                'reminder_count',
                'last_reminder_at',
            ]);
    }

    /**
     * Semua respons survei yang terkait dengan periode ini.
     * Catatan: survey_periods TIDAK punya FK ke questionnaires.
     * Questionnaire dipilih saat blast undangan.
     */
    public function surveyResponses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date->isPast() || $this->status === 'closed';
    }
}

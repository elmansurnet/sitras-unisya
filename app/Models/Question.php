<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'questionnaire_id',
        'section_id',
        'question_text',
        'question_type',
        'is_required',
        'order_number',
        'help_text',
        'placeholder',
        'validation_rules',
        'conditional_logic',
    ];

    protected $casts = [
        'is_required'       => 'boolean',
        'order_number'      => 'integer',
        'validation_rules'  => 'array',
        'conditional_logic' => 'array',
    ];

    /**
     * Tipe pertanyaan yang memerlukan options (radio, checkbox, select, likert).
     */
    public const OPTION_TYPES = ['radio', 'checkbox', 'select', 'likert'];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class, 'questionnaire_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireSection::class, 'section_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class, 'question_id')
                    ->orderBy('order_number');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_required', true);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('question_type', $type);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order_number');
    }

    public function scopeWithOptions(Builder $query): Builder
    {
        return $query->whereIn('question_type', self::OPTION_TYPES)
                     ->with('options');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function needsOptions(): bool
    {
        return in_array($this->question_type, self::OPTION_TYPES, true);
    }

    public function hasConditionalLogic(): bool
    {
        return ! empty($this->conditional_logic);
    }
}

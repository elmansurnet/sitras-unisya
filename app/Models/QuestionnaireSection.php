<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionnaireSection extends Model
{
    protected $fillable = [
        'questionnaire_id',
        'title',
        'description',
        'order_number',
    ];

    protected $casts = [
        'order_number' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class, 'questionnaire_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'section_id')
                    ->orderBy('order_number');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order_number');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function getQuestionCountAttribute(): int
    {
        return $this->questions()->count();
    }
}

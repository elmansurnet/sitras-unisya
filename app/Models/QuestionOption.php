<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionOption extends Model
{
    protected $fillable = [
        'question_id',
        'option_text',
        'option_value',
        'order_number',
        'is_other',
    ];

    protected $casts = [
        'order_number' => 'integer',
        'is_other'     => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
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

    public function isOther(): bool
    {
        return $this->is_other === true;
    }
}

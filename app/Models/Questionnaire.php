<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Questionnaire extends Model
{
    protected $fillable = [
        'title',
        'description',
        'type',
        'version',
        'status',
        'is_paginated',
        'estimated_minutes',
        'created_by',
        'published_at',
    ];

    protected $casts = [
        'version'           => 'integer',
        'is_paginated'      => 'boolean',
        'estimated_minutes' => 'integer',
        'published_at'      => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(QuestionnaireSection::class, 'questionnaire_id')
                    ->orderBy('order_number');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'questionnaire_id')
                    ->orderBy('order_number');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
                     ->where('status', 'aktif');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isActive(): bool
    {
        return $this->status === 'aktif';
    }

    public function isArchived(): bool
    {
        return $this->status === 'arsip';
    }

    public function getTotalQuestionsAttribute(): int
    {
        return $this->questions()->count();
    }
}

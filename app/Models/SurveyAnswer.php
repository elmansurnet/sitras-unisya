<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyAnswer extends Model
{
    protected $fillable = [
        'survey_response_id',
        'question_id',
        'answer_text',
        'answer_options',
        'answer_value',
        'file_path',
    ];

    protected $casts = [
        'answer_options' => 'array',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function surveyResponse(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Kembalikan nilai jawaban yang paling relevan berdasarkan tipe pertanyaan.
     */
    public function getResolvedValueAttribute(): mixed
    {
        if ($this->answer_text !== null) {
            return $this->answer_text;
        }

        if ($this->answer_options !== null) {
            return $this->answer_options;
        }

        return $this->answer_value;
    }
}

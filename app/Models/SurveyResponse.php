<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\SurveyResponse
 *
 * Stub model — kolom lengkap akan diisi pada sesi 3A.
 * Tabel: survey_responses (02_DATABASE.md §2.6)
 *
 * @property int         $id
 * @property int         $survey_period_id
 * @property int         $alumni_id
 * @property string      $status
 * @property \Carbon\Carbon|null $submitted_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SurveyResponse extends Model
{
    use HasFactory;

    protected $table = 'survey_responses';

    /**
     * Kolom yang boleh diisi secara mass-assignment.
     * Akan dilengkapi pada sesi 3A sesuai 02_DATABASE.md §2.6.
     */
    protected $fillable = [
        'survey_period_id',
        'alumni_id',
        'employer_id',
        'questionnaire_id',
        'status',
        'submitted_at',
        'started_at',
        'completion_time_seconds',
        'ip_address',
        'user_agent',
        'notes',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'started_at'   => 'datetime',
        'is_complete'  => 'boolean',
    ];

    // ─── Relationships (akan dilengkapi sesi 3A) ──────────────────────────────

    public function alumni()
    {
        return $this->belongsTo(Alumni::class);
    }
}

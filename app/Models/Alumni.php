<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alumni extends Model
{
    use SoftDeletes;

    /**
     * Semua kolom kecuali id, timestamps, deleted_at.
     * PERHATIAN: gpa di-cast 'decimal:2' — wajib return number (bukan string) di API.
     */
    protected $fillable = [
        'user_id',
        'nim',
        'nik',
        'fullname',
        'gender',
        'birthplace',
        'birth_date',
        'study_program_id',
        'graduation_year_id',
        'thesis_title',
        'gpa',
        'graduation_predicate',
        'address_street',
        'address_village',
        'address_district',
        'address_city',
        'address_province',
        'address_postal_code',
        'address_latitude',
        'address_longitude',
        'phone',
        'email',
        'linkedin_url',
        'photo',
        'survey_status',
        'import_batch',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected $casts = [
        // CRITICAL: 'decimal:2' menjamin gpa di-return sebagai number (float),
        // bukan string, di semua API response. Sesuai 02_DATABASE.md & 05_API.md.
        'gpa'           => 'decimal:2',
        'birth_date'    => 'date',
        'is_active'     => 'boolean',
        'address_latitude'  => 'decimal:7',
        'address_longitude' => 'decimal:7',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    /**
     * Alumni memiliki satu akun User (one-to-one).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alumni terdaftar di satu program studi.
     */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    /**
     * Alumni lulus pada tahun kelulusan tertentu.
     */
    public function graduationYear(): BelongsTo
    {
        return $this->belongsTo(GraduationYear::class);
    }

    /**
     * Alumni memiliki banyak riwayat pekerjaan.
     */
    public function workHistories(): HasMany
    {
        return $this->hasMany(AlumniWorkHistory::class);
    }

    /**
     * Alumni terdaftar dalam beberapa periode survei (pivot: alumni_survey_period).
     * Pivot menyimpan: invitation_sent_at, invitation_channel, reminder_count, last_reminder_at.
     * Sesuai 02_DATABASE.md §2.9 tabel alumni_survey_period.
     */
    public function surveyPeriods(): BelongsToMany
    {
        return $this->belongsToMany(
            SurveyPeriod::class,
            'alumni_survey_period',
            'alumni_id',
            'survey_period_id'
        )->withPivot([
            'invitation_sent_at',
            'invitation_channel',
            'reminder_count',
            'last_reminder_at',
        ])->withTimestamps();
    }

    /**
     * Alumni berelasi dengan employer melalui pivot alumni_employer.
     * Pivot menyimpan: is_verified.
     * Model Employer akan dibuat di sesi 2B.
     * Sesuai 02_DATABASE.md §2.9 tabel alumni_employer.
     */
    public function employers(): BelongsToMany
    {
        return $this->belongsToMany(
            Employer::class,
            'alumni_employer',
            'alumni_id',
            'employer_id'
        )->withPivot(['is_verified'])->withTimestamps();
    }
}

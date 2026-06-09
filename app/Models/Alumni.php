<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Model Alumni
 *
 * KRITICAL:
 *  - gpa di-cast ke 'decimal:2' agar API response return number (bukan string)
 *    Sesuai 07_SECURITY.md §2 & inkonsistensi #4 yang sudah diperbaiki v1.0.1
 *  - foto_path disimpan di storage/app/private/; akses via Storage::temporaryUrl()
 *  - Semua kolom decimal (lat/lng, gpa) return sebagai number, bukan string
 *
 * @property int         $id
 * @property int|null    $user_id
 * @property int|null    $study_program_id
 * @property int|null    $graduation_year_id
 * @property string      $nim
 * @property string      $name
 * @property string      $email
 * @property string|null $phone
 * @property string|null $whatsapp
 * @property string|null $gender
 * @property \Carbon\Carbon|null $birth_date
 * @property string|null $address
 * @property string|null $city
 * @property string|null $province
 * @property string      $degree
 * @property float|null  $gpa
 * @property \Carbon\Carbon|null $graduation_date
 * @property string|null $thesis_title
 * @property string|null $foto_path
 * @property float|null  $latitude
 * @property float|null  $longitude
 * @property string|null $employment_status
 * @property string      $survey_status
 * @property \Carbon\Carbon|null $survey_sent_at
 * @property \Carbon\Carbon|null $survey_completed_at
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Alumni extends Model
{
    use SoftDeletes;

    protected $table = 'alumni';

    /**
     * Mass assignable attributes.
     * Semua kolom kecuali id, created_at, updated_at, deleted_at.
     */
    protected $fillable = [
        'user_id',
        'study_program_id',
        'graduation_year_id',
        'nim',
        'name',
        'email',
        'phone',
        'whatsapp',
        'gender',
        'birth_date',
        'address',
        'city',
        'province',
        'degree',
        'gpa',
        'graduation_date',
        'thesis_title',
        'foto_path',
        'latitude',
        'longitude',
        'employment_status',
        'survey_status',
        'survey_sent_at',
        'survey_completed_at',
        'notes',
    ];

    /**
     * Attribute casts.
     *
     * PENTING: gpa → 'decimal:2'
     *  Eloquent secara default return DECIMAL sebagai string.
     *  Cast 'decimal:2' memastikan gpa selalu di-return sebagai
     *  float (number) di JSON response — sesuai fix INC-04 v1.0.1
     *  dan aturan SITRAS: "gpa harus number (bukan string) di API response".
     */
    protected $casts = [
        'birth_date'           => 'date',
        'graduation_date'      => 'date',
        'survey_sent_at'       => 'datetime',
        'survey_completed_at'  => 'datetime',
        'gpa'                  => 'decimal:2',   // ← KRITIS: number, bukan string
        'latitude'             => 'float',
        'longitude'            => 'float',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Alumni berasal dari satu User (akun login).
     * Nullable: admin bisa tambah alumni sebelum alumni mendaftar.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Alumni studi di satu StudyProgram.
     */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    /**
     * Alumni masuk angkatan tertentu (GraduationYear).
     */
    public function graduationYear(): BelongsTo
    {
        return $this->belongsTo(GraduationYear::class);
    }

    /**
     * Riwayat pekerjaan alumni.
     * Order by start_date DESC — pekerjaan terbaru di atas.
     */
    public function workHistories(): HasMany
    {
        return $this->hasMany(AlumniWorkHistory::class)
                    ->orderByDesc('start_date');
    }

    /**
     * Pekerjaan saat ini (is_current = true).
     */
    public function currentJob(): HasMany
    {
        return $this->hasMany(AlumniWorkHistory::class)
                    ->where('is_current', true);
    }

    /**
     * Employer yang pernah mempekerjakan alumni ini.
     * Dideklarasikan di sini; Employer model dibuat di sesi 2B.
     * Tidak menyebabkan error karena eager loading tidak otomatis.
     */
    public function employers(): BelongsToMany
    {
        return $this->belongsToMany(Employer::class, 'alumni_employer')
                    ->withTimestamps();
    }

    /**
     * Semua respons survei alumni ini.
     * Model SurveyResponse dibuat di sesi 4A.
     */
    public function surveyResponses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope: filter berdasarkan status survei.
     */
    public function scopeWithSurveyStatus($query, string $status)
    {
        return $query->where('survey_status', $status);
    }

    /**
     * Scope: alumni yang belum pernah menerima undangan survei.
     */
    public function scopeNotYetSurveyed($query)
    {
        return $query->where('survey_status', 'belum_disurvei');
    }

    /**
     * Scope: alumni yang belum submit survei (terkirim atau sedang mengisi).
     */
    public function scopePendingSurvey($query)
    {
        return $query->whereIn('survey_status', ['terkirim', 'sedang_mengisi']);
    }

    /**
     * Scope: filter by study program.
     */
    public function scopeByStudyProgram($query, int $studyProgramId)
    {
        return $query->where('study_program_id', $studyProgramId);
    }

    /**
     * Scope: filter by graduation year.
     */
    public function scopeByGraduationYear($query, int $graduationYearId)
    {
        return $query->where('graduation_year_id', $graduationYearId);
    }

    // =========================================================================
    // ACCESSORS / HELPERS
    // =========================================================================

    /**
     * Cek apakah alumni sudah pernah submit survei.
     */
    public function hasSurveyCompleted(): bool
    {
        return $this->survey_status === 'selesai';
    }

    /**
     * Cek apakah profil alumni sudah lengkap.
     * Minimal: study_program, graduation_year, phone/whatsapp, employment_status.
     */
    public function isProfileComplete(): bool
    {
        return ! is_null($this->study_program_id)
            && ! is_null($this->graduation_year_id)
            && (! is_null($this->phone) || ! is_null($this->whatsapp))
            && ! is_null($this->employment_status);
    }

    /**
     * Persentase kelengkapan profil (0–100).
     * Digunakan di Dashboard Alumni.
     */
    public function profileCompletionPercentage(): int
    {
        $fields = [
            'study_program_id', 'graduation_year_id', 'phone',
            'birth_date', 'address', 'city', 'province',
            'gpa', 'graduation_date', 'employment_status',
        ];

        $filled = collect($fields)->filter(
            fn ($f) => ! is_null($this->{$f})
        )->count();

        return (int) round(($filled / count($fields)) * 100);
    }
}

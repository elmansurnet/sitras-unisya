<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model Employer
 *
 * @property int         $id
 * @property int|null    $user_id
 * @property string      $company_name
 * @property string|null $company_type
 * @property string|null $industry_sector
 * @property string|null $company_scale
 * @property string|null $address_street
 * @property string|null $address_city
 * @property string|null $address_province
 * @property string|null $address_country
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property string|null $contact_person_name
 * @property string|null $contact_person_position
 * @property string|null $contact_person_email
 * @property string|null $contact_person_phone
 * @property string      $survey_status  belum_disurvei|terkirim|selesai
 * @property string|null $survey_token   Plaintext CSPRNG 64 char — digunakan untuk URL
 * @property \Carbon\Carbon|null $survey_token_expires_at
 * @property \Carbon\Carbon|null $survey_token_used_at
 * @property string|null $logo
 * @property string|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Employer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employers';

    /**
     * CATATAN KEAMANAN:
     * survey_token disimpan sebagai PLAINTEXT (bukan hash) karena digunakan
     * langsung dalam URL survei employer. Berbeda dengan OTP yang di-hash.
     * Lihat: 07_SECURITY.md §5.1
     */
    protected $fillable = [
        'user_id',
        'company_name',
        'company_type',
        'industry_sector',
        'company_scale',
        'address_street',
        'address_city',
        'address_province',
        'address_country',
        'phone',
        'email',
        'website',
        'contact_person_name',
        'contact_person_position',
        'contact_person_email',
        'contact_person_phone',
        'survey_status',
        'survey_token',
        'survey_token_expires_at',
        'survey_token_used_at',
        'logo',
        'notes',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected $casts = [
        'survey_token_expires_at' => 'datetime',
        'survey_token_used_at'    => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function alumni(): BelongsToMany
    {
        return $this->belongsToMany(Alumni::class, 'alumni_employer')
                    ->withPivot('is_verified', 'created_at');
        // CATATAN: withTimestamps() DIHAPUS — tabel alumni_employer hanya memiliki
        // created_at, TIDAK ADA updated_at. withTimestamps() menyebabkan
        // SQLSTATE[42S22] HTTP 500 pada GET /api/v1/admin/employers/{id}.
        // Sesuai 02_DATABASE.md §2.x skema tabel alumni_employer.
    }

    public function workHistories(): HasMany
    {
        return $this->hasMany(AlumniWorkHistory::class);
    }

    public function surveyResponses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isTokenValid(): bool
    {
        return $this->survey_token !== null
            && $this->survey_token_expires_at !== null
            && $this->survey_token_expires_at->isFuture()
            && $this->survey_status !== 'selesai';
    }

    public function hasSurveyCompleted(): bool
    {
        return $this->survey_status === 'selesai';
    }
}

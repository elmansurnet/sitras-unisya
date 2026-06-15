<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Alumni
 *
 * Tabel: alumni (02_DATABASE.md §2.3)
 *
 * Kolom foto di migration: photo (VARCHAR 255, nullable) — BUKAN photo_path.
 * Kolom is_active TIDAK ADA di tabel alumni (hanya ada di tabel users).
 *
 * @property int         $id
 * @property int         $user_id
 * @property int         $study_program_id
 * @property int         $graduation_year_id
 * @property string      $nim
 * @property string      $full_name
 * @property string|null $nik
 * @property string|null $birth_place
 * @property \Carbon\Carbon|null $birth_date
 * @property string|null $gender        L|P
 * @property string|null $religion
 * @property string|null $address_street
 * @property string|null $address_village
 * @property string|null $address_district
 * @property string|null $address_city
 * @property string|null $address_province
 * @property string|null $address_postal_code
 * @property float|null  $address_latitude
 * @property float|null  $address_longitude
 * @property string|null $phone
 * @property string|null $email
 * @property float|null  $gpa
 * @property string|null $graduation_predicate
 * @property string|null $thesis_title
 * @property string|null $linkedin_url
 * @property string|null $photo
 * @property string|null $import_batch
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Alumni extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'alumni';

    protected $fillable = [
        'user_id',
        'study_program_id',
        'graduation_year_id',
        'nim',
        'full_name',
        'nik',
        'birth_place',
        'birth_date',
        'gender',
        'religion',
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
        'gpa',
        'graduation_predicate',
        'thesis_title',
        'linkedin_url',
        'photo',
        'import_batch',
    ];

    protected $hidden = [];

    protected $casts = [
        'birth_date'        => 'date',
        'gpa'               => 'decimal:2',
        'address_latitude'  => 'float',
        'address_longitude' => 'float',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function graduationYear()
    {
        return $this->belongsTo(GraduationYear::class);
    }

    public function surveyResponses()
    {
        return $this->hasMany(SurveyResponse::class);
    }
}

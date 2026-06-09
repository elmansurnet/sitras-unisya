<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Employer
 *
 * Stub model — kolom lengkap akan diisi pada sesi 2B.
 * Tabel: employers (02_DATABASE.md §2.4)
 *
 * @property int         $id
 * @property int|null    $user_id
 * @property string      $company_name
 * @property string|null $survey_token
 * @property \Carbon\Carbon|null $token_expires_at
 * @property bool        $is_active
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Employer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employers';

    /**
     * Kolom yang boleh diisi secara mass-assignment.
     * Akan dilengkapi pada sesi 2B sesuai 02_DATABASE.md §2.4.
     */
    protected $fillable = [
        'user_id',
        'company_name',
        'company_type',
        'industry_sector_id',
        'address_street',
        'address_village',
        'address_district',
        'address_city',
        'address_province',
        'address_postal_code',
        'phone',
        'email',
        'website',
        'contact_person_name',
        'contact_person_position',
        'contact_person_phone',
        'survey_token',
        'token_expires_at',
        'token_used_at',
        'is_active',
        'notes',
    ];

    protected $hidden = [];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'token_used_at'    => 'datetime',
        'is_active'        => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

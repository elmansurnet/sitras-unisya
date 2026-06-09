<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlumniWorkHistory extends Model
{
    /**
     * Riwayat pekerjaan alumni.
     * is_relevant_to_study: NULLABLE bool — NULL = belum diisi oleh alumni.
     * employer_id: NULLABLE — employer mungkin belum terdaftar di sistem.
     */
    protected $fillable = [
        'alumni_id',
        'employer_id',
        'company_name',
        'position',
        'industry_sector',
        'employment_type',
        'start_date',
        'end_date',
        'is_current',
        'city',
        'province',
        'country',
        'monthly_salary_range',
        'is_relevant_to_study',
        'waiting_time_months',
        'description',
    ];

    protected $casts = [
        'start_date'           => 'date',
        'end_date'             => 'date',
        'is_current'           => 'boolean',
        // NULLABLE boolean: jika null -> null, jika 0/1 -> false/true
        'is_relevant_to_study' => 'boolean',
        'waiting_time_months'  => 'integer',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    /**
     * Riwayat kerja milik satu alumni.
     */
    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class);
    }

    /**
     * Riwayat kerja berelasi dengan employer (opsional).
     * Model Employer dibuat di sesi 2B.
     * withTrashed() agar relasi tetap tersedia meski employer soft-deleted.
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class)->withTrashed();
    }
}

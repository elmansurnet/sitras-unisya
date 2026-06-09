<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model AlumniWorkHistory
 *
 * Riwayat pekerjaan seorang alumni.
 * Relasi ke employers nullable — employer bisa belum terdaftar di sistem.
 * Tidak ada SoftDeletes — hapus riwayat kerja adalah operasi hard-delete.
 *
 * @property int         $id
 * @property int         $alumni_id
 * @property int|null    $employer_id
 * @property int|null    $salary_range_id
 * @property string      $company_name
 * @property string      $position
 * @property string|null $industry
 * @property string|null $city
 * @property string|null $province
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon|null $end_date
 * @property bool        $is_current
 * @property string      $source
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AlumniWorkHistory extends Model
{
    protected $table = 'alumni_work_histories';

    /**
     * Mass assignable attributes.
     * Semua kolom kecuali id, created_at, updated_at.
     */
    protected $fillable = [
        'alumni_id',
        'employer_id',
        'salary_range_id',
        'company_name',
        'position',
        'industry',
        'city',
        'province',
        'start_date',
        'end_date',
        'is_current',
        'source',
    ];

    /**
     * Attribute casts.
     */
    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'is_current'  => 'boolean',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Riwayat kerja ini milik Alumni tertentu.
     */
    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class);
    }

    /**
     * Perusahaan terdaftar di sistem (nullable).
     * Model Employer dibuat di sesi 2B.
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    /**
     * Rentang gaji.
     */
    public function salaryRange(): BelongsTo
    {
        return $this->belongsTo(SalaryRange::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope: hanya pekerjaan yang masih aktif.
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope: pekerjaan yang sudah berakhir.
     */
    public function scopePast($query)
    {
        return $query->where('is_current', false);
    }

    /**
     * Scope: filter berdasarkan sumber data.
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Durasi pekerjaan dalam format human-readable.
     * Contoh: "2 tahun 3 bulan" atau "Masih bekerja (1 tahun 5 bulan)"
     */
    public function getDurationAttribute(): string
    {
        $start = $this->start_date;
        $end   = $this->is_current ? now() : ($this->end_date ?? now());

        $years  = $start->diffInYears($end);
        $months = $start->copy()->addYears($years)->diffInMonths($end);

        $parts = [];
        if ($years > 0) {
            $parts[] = "{$years} tahun";
        }
        if ($months > 0) {
            $parts[] = "{$months} bulan";
        }

        $duration = implode(' ', $parts) ?: 'Kurang dari 1 bulan';

        return $this->is_current ? "Masih bekerja ({$duration})" : $duration;
    }
}

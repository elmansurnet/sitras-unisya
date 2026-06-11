<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model AlumniWorkHistory
 *
 * Riwayat pekerjaan seorang alumni.
 * Skema sesuai 02_DATABASE.md §2.4 dan migration _000011.
 *
 * Tidak ada SoftDeletes — hapus riwayat kerja adalah operasi hard-delete.
 *
 * @property int              $id
 * @property int              $alumni_id
 * @property int|null         $employer_id
 * @property string           $company_name
 * @property string           $position
 * @property string|null      $industry_sector
 * @property string|null      $employment_type   penuh_waktu|paruh_waktu|kontrak|freelance|wirausaha|magang
 * @property \Carbon\Carbon   $start_date
 * @property \Carbon\Carbon|null $end_date
 * @property bool             $is_current
 * @property string|null      $city
 * @property string|null      $province
 * @property string|null      $country
 * @property string|null      $monthly_salary_range  kode range VARCHAR(50)
 * @property int|null         $is_relevant_to_study  1=ya, 0=tidak, NULL=belum diisi
 * @property int|null         $waiting_time_months
 * @property string|null      $description
 * @property \Carbon\Carbon   $created_at
 * @property \Carbon\Carbon   $updated_at
 */
class AlumniWorkHistory extends Model
{
    use HasFactory;

    protected $table = 'alumni_work_histories';

    /**
     * Mass assignable — semua kolom kecuali id, created_at, updated_at.
     * Sesuai migration 2026_06_09_000011 dan 02_DATABASE.md §2.4.
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

    /**
     * Attribute casts.
     * gpa-equivalent tidak ada di sini; tapi boolean & date wajib di-cast.
     */
    protected $casts = [
        'start_date'           => 'date',
        'end_date'             => 'date',
        'is_current'           => 'boolean',
        'waiting_time_months'  => 'integer',
        // is_relevant_to_study sengaja tidak di-cast boolean karena nilai
        // valid-nya adalah 1, 0, NULL — NULL punya makna semantik "belum diisi".
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
     * FK constraint ke employers ditambahkan di sesi 2B.
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /** Hanya pekerjaan yang masih aktif. */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /** Pekerjaan yang sudah berakhir. */
    public function scopePast($query)
    {
        return $query->where('is_current', false);
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

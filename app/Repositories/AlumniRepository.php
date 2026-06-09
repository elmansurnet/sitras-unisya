<?php

namespace App\Repositories;

use App\Models\Alumni;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * AlumniRepository
 * Semua query kompleks alumni terpusat di sini.
 * Controller & Service TIDAK boleh akses Eloquent langsung.
 */
class AlumniRepository
{
    // ─── LIST WITH FILTERS ────────────────────────────────────────────────────

    /**
     * Daftar alumni dengan filter, search, dan paginasi.
     *
     * @param  array<string,mixed> $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Alumni::with([
            'studyProgram:id,name,code,degree_level,faculty_id',
            'studyProgram.faculty:id,name',
            'graduationYear:id,year,academic_year',
            'user:id,email,phone,is_active',
        ])
        ->withTrashed(isset($filters['with_trashed']) && $filters['with_trashed']);

        $this->applyFilters($query, $filters);
        $this->applySort($query, $filters);

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage);
    }

    /**
     * Terapkan semua filter ke query builder.
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nim', 'like', "%{$search}%")
                  ->orWhereHas('user', fn (Builder $u) =>
                      $u->where('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                  );
            });
        }

        if (!empty($filters['study_program_id'])) {
            $query->where('study_program_id', (int) $filters['study_program_id']);
        }

        if (!empty($filters['graduation_year_id'])) {
            $query->where('graduation_year_id', (int) $filters['graduation_year_id']);
        }

        if (!empty($filters['survey_status'])) {
            $query->where('survey_status', $filters['survey_status']);
        }

        if (!empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        if (!empty($filters['faculty_id'])) {
            $query->whereHas('studyProgram', fn (Builder $q) =>
                $q->where('faculty_id', (int) $filters['faculty_id'])
            );
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }
    }

    /**
     * Terapkan urutan ke query builder.
     */
    private function applySort(Builder $query, array $filters): void
    {
        $allowedSortFields = [
            'created_at', 'full_name', 'nim', 'gpa',
            'survey_status', 'graduation_year_id',
        ];

        $sortBy  = in_array($filters['sort_by'] ?? '', $allowedSortFields)
            ? $filters['sort_by']
            : 'created_at';

        $sortDir = in_array(strtolower($filters['sort_dir'] ?? ''), ['asc', 'desc'])
            ? $filters['sort_dir']
            : 'desc';

        $query->orderBy($sortBy, $sortDir);
    }

    // ─── SINGLE RECORD ────────────────────────────────────────────────────────

    /**
     * Ambil 1 alumni dengan relasi lengkap.
     */
    public function findWithRelations(int $id): ?Alumni
    {
        return Alumni::with([
            'user:id,email,phone,last_login_at,is_active',
            'studyProgram:id,name,code,degree_level,faculty_id',
            'studyProgram.faculty:id,name',
            'graduationYear:id,year,academic_year,semester',
            'workHistories' => fn ($q) => $q->orderByDesc('start_date'),
            'surveyResponses:id,alumni_id,status,submitted_at,survey_period_id',
        ])->find($id);
    }

    /**
     * Ambil alumni berdasarkan user_id (untuk alumni self-access).
     */
    public function findByUserId(int $userId): ?Alumni
    {
        return Alumni::with([
            'studyProgram:id,name,code,degree_level,faculty_id',
            'studyProgram.faculty:id,name',
            'graduationYear:id,year,academic_year',
            'workHistories' => fn ($q) => $q->orderByDesc('start_date'),
        ])->where('user_id', $userId)->first();
    }

    // ─── STATS ────────────────────────────────────────────────────────────────

    /**
     * Statistik ringkasan alumni.
     *
     * @return array<string,int>
     */
    public function getStats(): array
    {
        return [
            'total'          => Alumni::count(),
            'active'         => Alumni::where('is_active', true)->count(),
            'belum_disurvei' => Alumni::where('survey_status', 'belum_disurvei')->count(),
            'terkirim'       => Alumni::where('survey_status', 'terkirim')->count(),
            'sedang_mengisi' => Alumni::where('survey_status', 'sedang_mengisi')->count(),
            'selesai'        => Alumni::where('survey_status', 'selesai')->count(),
        ];
    }

    // ─── BULK FETCH (untuk export / invitation) ───────────────────────────────

    /**
     * Ambil semua alumni sesuai filter tanpa paginasi.
     * Digunakan untuk export dan bulk invitation.
     *
     * @param  array<string,mixed> $filters
     * @return \Illuminate\Database\Eloquent\Collection<int, Alumni>
     */
    public function allWithFilters(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Alumni::with([
            'studyProgram:id,name,code,degree_level',
            'graduationYear:id,year,academic_year',
            'user:id,email,phone',
        ]);

        $this->applyFilters($query, $filters);
        $this->applySort($query, $filters);

        return $query->get();
    }
}

<?php

namespace App\Repositories;

use App\Models\Alumni;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AlumniRepository
{
    /**
     * Ambil daftar alumni dengan filter, search, sort, dan paginasi.
     *
     * @param  array<string,mixed> $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Alumni::query()
            ->with(['user', 'studyProgram.faculty', 'graduationYear'])
            ->withTrashed(false);

        // Search: nama, NIM, email
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nim', 'like', "%{$search}%")
                  ->orWhereHas('user', fn(Builder $u) => $u->where('email', 'like', "%{$search}%"));
            });
        }

        if (!empty($filters['study_program_id'])) {
            $query->where('study_program_id', $filters['study_program_id']);
        }

        if (!empty($filters['graduation_year_id'])) {
            $query->where('graduation_year_id', $filters['graduation_year_id']);
        }

        if (!empty($filters['survey_status'])) {
            $query->where('survey_status', $filters['survey_status']);
        }

        if (!empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        // Sorting
        $sortBy  = in_array($filters['sort_by']  ?? '', ['nim', 'full_name', 'gpa', 'created_at'], true)
            ? $filters['sort_by']
            : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage);
    }

    /**
     * Temukan alumni by ID dengan relasi lengkap.
     */
    public function findWithRelations(int $id): Alumni
    {
        return Alumni::with([
            'user',
            'studyProgram.faculty',
            'graduationYear',
            'workHistories',
            'surveyResponses',
        ])->findOrFail($id);
    }

    /**
     * Temukan alumni by user_id (untuk self-profile).
     */
    public function findByUserId(int $userId): ?Alumni
    {
        return Alumni::with(['user', 'studyProgram.faculty', 'graduationYear', 'workHistories'])
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Ambil semua alumni (tanpa paginasi) sesuai filter — untuk export.
     *
     * @param  array<string,mixed> $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(array $filters = [])
    {
        $query = Alumni::query()->with(['studyProgram.faculty', 'graduationYear']);

        if (!empty($filters['study_program_id'])) {
            $query->where('study_program_id', $filters['study_program_id']);
        }
        if (!empty($filters['graduation_year_id'])) {
            $query->where('graduation_year_id', $filters['graduation_year_id']);
        }
        if (!empty($filters['survey_status'])) {
            $query->where('survey_status', $filters['survey_status']);
        }

        return $query->get();
    }

    /**
     * Statistik ringkas alumni untuk dashboard.
     *
     * @return array<string,int>
     */
    public function stats(): array
    {
        return [
            'total'            => Alumni::count(),
            'belum_disurvei'   => Alumni::where('survey_status', 'belum_disurvei')->count(),
            'terkirim'         => Alumni::where('survey_status', 'terkirim')->count(),
            'sedang_mengisi'   => Alumni::where('survey_status', 'sedang_mengisi')->count(),
            'selesai'          => Alumni::where('survey_status', 'selesai')->count(),
        ];
    }
}

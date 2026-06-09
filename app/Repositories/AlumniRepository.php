<?php

namespace App\Repositories;

use App\Models\Alumni;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AlumniRepository
{
    /**
     * Cari alumni berdasarkan NIM.
     */
    public function findByNim(string $nim): ?Alumni
    {
        return Alumni::where('nim', $nim)->first();
    }

    /**
     * Cari alumni dengan filter lengkap + pagination.
     *
     * @param  array<string,mixed> $filters
     *         Keys: search, faculty_id, study_program_id, graduation_year_id,
     *               employment_status, is_active, per_page, sort_by, sort_dir
     */
    public function findWithFilters(array $filters): LengthAwarePaginator
    {
        $query = Alumni::query()
            ->with(['user:id,name,email', 'studyProgram:id,name,faculty_id', 'studyProgram.faculty:id,name', 'graduationYear:id,year'])
            ->withTrashed(isset($filters['with_trashed']) && $filters['with_trashed']);

        // Full-text search: nim, full_name, email, nik
        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('nim', 'like', $term)
                  ->orWhere('full_name', 'like', $term)
                  ->orWhere('nik', 'like', $term)
                  ->orWhereHas('user', fn ($u) => $u->where('email', 'like', $term));
            });
        }

        if (!empty($filters['faculty_id'])) {
            $query->whereHas('studyProgram', fn ($q) => $q->where('faculty_id', $filters['faculty_id']));
        }

        if (!empty($filters['study_program_id'])) {
            $query->where('study_program_id', $filters['study_program_id']);
        }

        if (!empty($filters['graduation_year_id'])) {
            $query->where('graduation_year_id', $filters['graduation_year_id']);
        }

        if (isset($filters['employment_status']) && $filters['employment_status'] !== '') {
            $query->where('employment_status', $filters['employment_status']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        // Sorting
        $sortBy  = in_array($filters['sort_by'] ?? '', ['full_name', 'nim', 'gpa', 'created_at'])
            ? $filters['sort_by']
            : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage);
    }

    /**
     * Ambil koordinat alumni untuk peta distribusi.
     * Hanya alumni yang memiliki latitude & longitude.
     *
     * @return Collection<int, Alumni>
     */
    public function getMapCoordinates(?int $studyProgramId = null, ?int $graduationYearId = null): Collection
    {
        return Alumni::query()
            ->select(['id', 'full_name', 'latitude', 'longitude', 'city', 'employment_status', 'study_program_id'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('is_active', true)
            ->when($studyProgramId, fn ($q) => $q->where('study_program_id', $studyProgramId))
            ->when($graduationYearId, fn ($q) => $q->where('graduation_year_id', $graduationYearId))
            ->get();
    }

    /**
     * Statistik ringkasan alumni untuk dashboard.
     *
     * @return array<string,mixed>
     */
    public function getStats(): array
    {
        $total   = Alumni::count();
        $active  = Alumni::where('is_active', true)->count();
        $byStatus = Alumni::where('is_active', true)
            ->select('employment_status', DB::raw('COUNT(*) as total'))
            ->groupBy('employment_status')
            ->pluck('total', 'employment_status')
            ->toArray();

        $avgGpa = Alumni::where('is_active', true)
            ->whereNotNull('gpa')
            ->avg('gpa');

        $byFaculty = Alumni::query()
            ->join('study_programs', 'alumni.study_program_id', '=', 'study_programs.id')
            ->join('faculties', 'study_programs.faculty_id', '=', 'faculties.id')
            ->select('faculties.name as faculty_name', DB::raw('COUNT(alumni.id) as total'))
            ->groupBy('faculties.id', 'faculties.name')
            ->pluck('total', 'faculty_name')
            ->toArray();

        return [
            'total'               => $total,
            'active'              => $active,
            'by_employment_status' => $byStatus,
            'avg_gpa'             => $avgGpa !== null ? round((float) $avgGpa, 2) : null,
            'by_faculty'          => $byFaculty,
        ];
    }
}

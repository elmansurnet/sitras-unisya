<?php

namespace App\Repositories;

use App\Models\Alumni;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AlumniRepository
{
    /**
     * Cari alumni berdasarkan NIM (exact match).
     */
    public function findByNim(string $nim): ?Alumni
    {
        return Alumni::where('nim', $nim)->first();
    }

    /**
     * Cari alumni berdasarkan user_id.
     */
    public function findByUserId(int $userId): ?Alumni
    {
        return Alumni::where('user_id', $userId)->first();
    }

    /**
     * Ambil satu alumni by primary key, eager-load relasi.
     */
    public function findWithRelations(int $id): ?Alumni
    {
        return Alumni::with([
            'user:id,email,is_active,last_login_at',
            'studyProgram.faculty',
            'graduationYear',
        ])->find($id);
    }

    /**
     * Query alumni dengan filter dinamis + paginasi.
     *
     * Filter yang didukung (semua opsional):
     *   search          => string (nim / full_name / email)
     *   faculty_id      => int
     *   study_program_id => int
     *   graduation_year_id => int
     *   is_active       => bool
     *   gender          => M|F
     *   per_page        => int (default 15, max 100)
     *   sort_by         => kolom yang boleh disort
     *   sort_dir        => asc|desc
     *
     * @param  array<string,mixed> $filters
     */
    public function findWithFilters(array $filters = []): LengthAwarePaginator
    {
        $query = Alumni::with([
            'user:id,email,is_active',
            'studyProgram:id,name,faculty_id',
            'studyProgram.faculty:id,name',
            'graduationYear:id,year,semester',
        ]);

        $this->applyFilters($query, $filters);

        $allowedSorts = [
            'nim', 'full_name', 'gpa', 'created_at',
        ];
        $sortBy  = in_array($filters['sort_by']  ?? '', $allowedSorts, true) ? $filters['sort_by']  : 'created_at';
        $sortDir = in_array($filters['sort_dir'] ?? '', ['asc', 'desc'], true) ? $filters['sort_dir'] : 'desc';

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }

    /**
     * Ambil koordinat alumni yang punya latitude/longitude untuk peta.
     * Catatan: kolom koordinat ada di sesi mendatang, stub ini disiapkan.
     *
     * @return Collection<int, array{id: int, lat: float, lng: float, name: string}>
     */
    public function getMapCoordinates(): Collection
    {
        return Alumni::whereNotNull('address_city')
            ->where('is_active', true)
            ->select('id', 'full_name', 'address_city', 'address_province')
            ->get()
            ->map(fn ($a) => [
                'id'       => $a->id,
                'name'     => $a->full_name,
                'city'     => $a->address_city,
                'province' => $a->address_province,
            ]);
    }

    /**
     * Statistik ringkas alumni untuk dashboard.
     *
     * @return array{total: int, active: int, by_faculty: Collection, by_year: Collection}
     */
    public function getStats(): array
    {
        return [
            'total'      => Alumni::count(),
            'active'     => Alumni::where('is_active', true)->count(),
            'by_faculty' => Alumni::join('study_programs', 'alumni.study_program_id', '=', 'study_programs.id')
                ->join('faculties', 'study_programs.faculty_id', '=', 'faculties.id')
                ->selectRaw('faculties.name as faculty_name, COUNT(alumni.id) as total')
                ->groupBy('faculties.id', 'faculties.name')
                ->orderByDesc('total')
                ->get(),
            'by_year'    => Alumni::join('graduation_years', 'alumni.graduation_year_id', '=', 'graduation_years.id')
                ->selectRaw('graduation_years.year, COUNT(alumni.id) as total')
                ->groupBy('graduation_years.id', 'graduation_years.year')
                ->orderBy('graduation_years.year')
                ->get(),
        ];
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function (Builder $q) use ($search) {
                $q->where('nim', 'like', $search)
                  ->orWhere('full_name', 'like', $search)
                  ->orWhereHas('user', fn ($u) => $u->where('email', 'like', $search));
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

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (!empty($filters['gender']) && in_array($filters['gender'], ['M', 'F'], true)) {
            $query->where('gender', $filters['gender']);
        }
    }
}

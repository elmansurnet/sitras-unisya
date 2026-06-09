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
     * Cari alumni dengan filter dinamis + pagination.
     *
     * @param array $filters [
     *   'search'             => string,   // NIM/nama/email
     *   'study_program_id'   => int,
     *   'graduation_year_id' => int,
     *   'survey_status'      => string,
     *   'gender'             => string,
     * ]
     */
    public function findWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Alumni::with([
            'user:id,email,phone,is_active',
            'studyProgram:id,name,code',
            'graduationYear:id,year,semester',
        ])->withTrashed(isset($filters['with_trashed']) && $filters['with_trashed']);

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('nim', 'like', "%{$s}%")
                  ->orWhere('full_name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
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

        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortDir   = $filters['sort_dir'] ?? 'desc';
        $allowed   = ['nim', 'full_name', 'created_at', 'survey_status', 'gpa'];

        if (in_array($sortField, $allowed, true)) {
            $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Ambil koordinat alumni yang memiliki lat/lng untuk peta.
     *
     * @return Collection<int, object{id, full_name, address_city, address_latitude, address_longitude}>
     */
    public function getMapCoordinates(): Collection
    {
        return Alumni::select(
            'id',
            'full_name',
            'address_city',
            'address_province',
            'address_latitude',
            'address_longitude'
        )
        ->whereNotNull('address_latitude')
        ->whereNotNull('address_longitude')
        ->get();
    }

    /**
     * Statistik ringkasan alumni untuk dashboard.
     *
     * @return array{
     *   total: int,
     *   by_survey_status: array,
     *   by_gender: array,
     *   by_graduation_year: array,
     * }
     */
    public function getStats(): array
    {
        $total = Alumni::count();

        $bySurveyStatus = Alumni::select('survey_status', DB::raw('COUNT(*) as total'))
            ->groupBy('survey_status')
            ->pluck('total', 'survey_status')
            ->toArray();

        $byGender = Alumni::select('gender', DB::raw('COUNT(*) as total'))
            ->groupBy('gender')
            ->pluck('total', 'gender')
            ->toArray();

        $byGraduationYear = Alumni::select(
                'graduation_year_id',
                DB::raw('COUNT(*) as total')
            )
            ->with('graduationYear:id,year,semester')
            ->groupBy('graduation_year_id')
            ->get()
            ->map(fn ($row) => [
                'year'     => $row->graduationYear?->year,
                'semester' => $row->graduationYear?->semester,
                'total'    => $row->total,
            ])
            ->values()
            ->toArray();

        return [
            'total'               => $total,
            'by_survey_status'    => $bySurveyStatus,
            'by_gender'           => $byGender,
            'by_graduation_year'  => $byGraduationYear,
        ];
    }
}

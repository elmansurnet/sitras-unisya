<?php

namespace App\Repositories;

use App\Models\Employer;
use App\Repositories\Contracts\EmployerRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployerRepository implements EmployerRepositoryInterface
{
    public function findWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Employer::query()->with(['user']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('contact_person_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['company_type'])) {
            $query->where('company_type', $filters['company_type']);
        }

        if (! empty($filters['industry_sector'])) {
            $query->where('industry_sector', 'like', "%{$filters['industry_sector']}%");
        }

        if (! empty($filters['survey_status'])) {
            $query->where('survey_status', $filters['survey_status']);
        }

        if (! empty($filters['address_city'])) {
            $query->where('address_city', 'like', "%{$filters['address_city']}%");
        }

        $sortBy  = in_array($filters['sort_by'] ?? '', ['company_name', 'created_at', 'survey_status'])
            ? $filters['sort_by']
            : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Employer
    {
        return Employer::with(['user', 'alumni.user', 'workHistories'])->find($id);
    }

    public function findByToken(string $token): ?Employer
    {
        return Employer::where('survey_token', $token)->first();
    }

    public function create(array $data): Employer
    {
        return Employer::create($data);
    }

    public function update(Employer $employer, array $data): Employer
    {
        $employer->update($data);
        return $employer->fresh(['user']);
    }

    public function delete(Employer $employer): void
    {
        $employer->delete();
    }

    public function getStats(): array
    {
        return [
            'total'          => Employer::count(),
            'belum_disurvei' => Employer::where('survey_status', 'belum_disurvei')->count(),
            'terkirim'       => Employer::where('survey_status', 'terkirim')->count(),
            'selesai'        => Employer::where('survey_status', 'selesai')->count(),
        ];
    }
}

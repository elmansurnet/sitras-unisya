<?php

namespace App\Repositories\Contracts;

use App\Models\Employer;
use Illuminate\Pagination\LengthAwarePaginator;

interface EmployerRepositoryInterface
{
    public function findWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Employer;

    public function findByToken(string $token): ?Employer;

    public function create(array $data): Employer;

    public function update(Employer $employer, array $data): Employer;

    public function delete(Employer $employer): void;

    public function getStats(): array;
}

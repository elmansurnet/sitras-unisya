<?php

namespace Database\Factories;

use App\Models\GraduationYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GraduationYear>
 *
 * Sesuai skema graduation_years (02_DATABASE.md §2.2).
 */
class GraduationYearFactory extends Factory
{
    protected $model = GraduationYear::class;

    public function definition(): array
    {
        return [
            'year'       => fake()->unique()->numberBetween(2015, 2030),
            'is_active'  => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * Buat dengan tahun spesifik.
     */
    public function year(int $year): static
    {
        return $this->state(fn () => ['year' => $year]);
    }
}

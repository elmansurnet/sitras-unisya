<?php

namespace Database\Factories;

use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faculty>
 *
 * Sesuai skema faculties (02_DATABASE.md §2.2).
 */
class FacultyFactory extends Factory
{
    protected $model = Faculty::class;

    private static int $counter = 0;

    public function definition(): array
    {
        self::$counter++;
        return [
            'name' => 'Fakultas ' . fake()->unique()->word() . ' ' . self::$counter,
            'code' => strtoupper(fake()->unique()->lexify('F??')),
        ];
    }
}

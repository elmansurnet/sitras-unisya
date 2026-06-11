<?php

namespace Database\Factories;

use App\Models\Faculty;
use App\Models\StudyProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyProgram>
 *
 * Sesuai skema study_programs (02_DATABASE.md §2.2).
 * Membutuhkan faculty_id — buat Faculty otomatis jika tidak disuplai.
 */
class StudyProgramFactory extends Factory
{
    protected $model = StudyProgram::class;

    private static int $counter = 0;

    public function definition(): array
    {
        self::$counter++;
        return [
            'faculty_id'    => Faculty::factory(),
            'name'          => 'Program Studi ' . fake()->unique()->word() . ' ' . self::$counter,
            'code'          => strtoupper(fake()->unique()->lexify('PS??')),
            'degree_level'  => fake()->randomElement(['D3', 'S1', 'S2']),
            'is_active'     => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}

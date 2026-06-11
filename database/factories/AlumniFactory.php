<?php

namespace Database\Factories;

use App\Models\Alumni;
use App\Models\GraduationYear;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alumni>
 *
 * Sesuai skema alumni (02_DATABASE.md §2.3) dan Alumni model sesi 2A.
 * user_id, study_program_id, graduation_year_id di-generate otomatis
 * kecuali disuplai secara eksplisit.
 */
class AlumniFactory extends Factory
{
    protected $model = Alumni::class;

    private static int $nimCounter = 1000;

    public function definition(): array
    {
        self::$nimCounter++;
        return [
            'user_id'             => User::factory()->alumni(),
            'study_program_id'    => StudyProgram::factory(),
            'graduation_year_id'  => GraduationYear::factory(),
            'nim'                 => (string) self::$nimCounter,
            'full_name'           => fake()->name(),
            'nik'                 => null,
            'birth_place'         => fake()->city(),
            'birth_date'          => fake()->dateTimeBetween('-35 years', '-20 years')->format('Y-m-d'),
            'gender'              => fake()->randomElement(['M', 'F']),
            'religion'            => fake()->randomElement(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Budha']),
            'address_street'      => fake()->streetAddress(),
            'address_village'     => fake()->word(),
            'address_district'    => fake()->word(),
            'address_city'        => fake()->city(),
            'address_province'    => fake()->state(),
            'address_postal_code' => fake()->postcode(),
            'phone'               => '08' . fake()->numerify('#########'),
            'gpa'                 => fake()->randomFloat(2, 2.00, 4.00),
            'graduation_predicate'=> fake()->randomElement(['Memuaskan', 'Sangat Memuaskan', 'Pujian']),
            'thesis_title'        => fake()->sentence(8),
            'linkedin_url'        => null,
            'photo_path'          => null,
            'import_batch'        => null,
            'is_active'           => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withPhoto(): static
    {
        return $this->state(fn () => [
            'photo_path' => 'alumni/photos/sample.jpg',
        ]);
    }
}

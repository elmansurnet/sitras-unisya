<?php

namespace Database\Factories;

use App\Models\Alumni;
use App\Models\AlumniWorkHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlumniWorkHistory>
 *
 * Sesuai skema alumni_work_histories (02_DATABASE.md §2.4)
 * dan migration _000011.
 *
 * ENUM employment_type values:
 *   penuh_waktu | paruh_waktu | kontrak | freelance | wirausaha | magang
 */
class AlumniWorkHistoryFactory extends Factory
{
    protected $model = AlumniWorkHistory::class;

    public function definition(): array
    {
        $isCurrent = fake()->boolean(30); // 30% kemungkinan masih aktif
        $startDate = fake()->dateTimeBetween('-5 years', '-6 months')->format('Y-m-d');
        $endDate   = $isCurrent
            ? null
            : fake()->dateTimeBetween($startDate, 'now')->format('Y-m-d');

        return [
            'alumni_id'            => Alumni::factory(),
            'employer_id'          => null, // FK ke employers — sesi 2B
            'company_name'         => fake()->company(),
            'position'             => fake()->jobTitle(),
            'industry_sector'      => fake()->randomElement([
                'Teknologi Informasi', 'Pendidikan', 'Kesehatan',
                'Perbankan', 'Manufaktur', 'Pemerintahan', null,
            ]),
            'employment_type'      => fake()->randomElement([
                'penuh_waktu', 'paruh_waktu', 'kontrak',
                'freelance', 'wirausaha', 'magang', null,
            ]),
            'start_date'           => $startDate,
            'end_date'             => $endDate,
            'is_current'           => $isCurrent,
            'city'                 => fake()->city(),
            'province'             => fake()->state(),
            'country'              => 'Indonesia',
            'monthly_salary_range' => fake()->randomElement([
                'di_bawah_1jt', '1_3jt', '3_5jt', '5_10jt', 'di_atas_10jt', null,
            ]),
            'is_relevant_to_study' => fake()->randomElement([1, 0, null]),
            'waiting_time_months'  => fake()->optional(0.7)->numberBetween(0, 24),
            'description'          => fake()->optional(0.4)->sentence(12),
        ];
    }

    // ── State helpers ──────────────────────────────────────────────────────

    /** Pekerjaan yang masih berlangsung. */
    public function current(): static
    {
        return $this->state(fn () => [
            'is_current' => true,
            'end_date'   => null,
        ]);
    }

    /** Pekerjaan yang sudah selesai. */
    public function past(): static
    {
        return $this->state(fn () => [
            'is_current' => false,
            'end_date'   => fake()->dateTimeBetween('-2 years', '-1 month')->format('Y-m-d'),
        ]);
    }

    /** Tipe pekerjaan penuh waktu. */
    public function fullTime(): static
    {
        return $this->state(fn () => ['employment_type' => 'penuh_waktu']);
    }

    /** Tipe wirausaha/self-employed. */
    public function entrepreneur(): static
    {
        return $this->state(fn () => ['employment_type' => 'wirausaha']);
    }
}

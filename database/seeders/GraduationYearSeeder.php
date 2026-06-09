<?php

namespace Database\Seeders;

use App\Models\GraduationYear;
use Illuminate\Database\Seeder;

/**
 * GraduationYearSeeder
 *
 * Data tahun kelulusan angkatan 2020–2024.
 * Kolom sesuai migration 000006:
 *   year, academic_year, semester, is_active
 *
 * UNIQUE constraint di migration: UNIQUE(year, semester)
 */
class GraduationYearSeeder extends Seeder
{
    public function run(): void
    {
        $years = [
            [
                'year'          => 2020,
                'academic_year' => '2019/2020',
                'semester'      => 'Genap',
                'is_active'     => true,
            ],
            [
                'year'          => 2021,
                'academic_year' => '2020/2021',
                'semester'      => 'Genap',
                'is_active'     => true,
            ],
            [
                'year'          => 2022,
                'academic_year' => '2021/2022',
                'semester'      => 'Genap',
                'is_active'     => true,
            ],
            [
                'year'          => 2023,
                'academic_year' => '2022/2023',
                'semester'      => 'Genap',
                'is_active'     => true,
            ],
            [
                'year'          => 2024,
                'academic_year' => '2023/2024',
                'semester'      => 'Genap',
                'is_active'     => true,
            ],
        ];

        foreach ($years as $year) {
            GraduationYear::updateOrCreate(
                ['year' => $year['year'], 'semester' => $year['semester']],
                $year
            );
        }
    }
}

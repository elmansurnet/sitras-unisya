<?php

namespace Database\Seeders;

use App\Models\GraduationYear;
use Illuminate\Database\Seeder;

class GraduationYearSeeder extends Seeder
{
    /**
     * Seed angkatan wisuda 2020–2024, semester Ganjil & Genap.
     */
    public function run(): void
    {
        $years = range(2020, 2024);
        $semesters = [
            ['semester' => 'Ganjil', 'suffix' => '/I'],
            ['semester' => 'Genap',  'suffix' => '/II'],
        ];

        foreach ($years as $year) {
            foreach ($semesters as $sem) {
                GraduationYear::updateOrCreate(
                    ['year' => $year, 'semester' => $sem['semester']],
                    [
                        'year'          => $year,
                        'academic_year' => $year.'/'.(($year + 1)).$sem['suffix'],
                        'semester'      => $sem['semester'],
                        'is_active'     => true,
                    ]
                );
            }
        }
    }
}

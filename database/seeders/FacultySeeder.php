<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Seeder;

/**
 * FacultySeeder
 *
 * Data fakultas Universitas Islam Syarifuddin Lumajang.
 * Kolom sesuai migration 000004: code, name, dean_name, is_active.
 */
class FacultySeeder extends Seeder
{
    public function run(): void
    {
        $faculties = [
            [
                'code'      => 'FAI',
                'name'      => 'Fakultas Agama Islam',
                'dean_name' => null,
                'is_active' => true,
            ],
            [
                'code'      => 'FT',
                'name'      => 'Fakultas Teknik',
                'dean_name' => null,
                'is_active' => true,
            ],
            [
                'code'      => 'FE',
                'name'      => 'Fakultas Ekonomi',
                'dean_name' => null,
                'is_active' => true,
            ],
            [
                'code'      => 'FKIP',
                'name'      => 'Fakultas Keguruan dan Ilmu Pendidikan',
                'dean_name' => null,
                'is_active' => true,
            ],
            [
                'code'      => 'FH',
                'name'      => 'Fakultas Hukum',
                'dean_name' => null,
                'is_active' => true,
            ],
        ];

        foreach ($faculties as $faculty) {
            Faculty::updateOrCreate(
                ['code' => $faculty['code']],
                $faculty
            );
        }
    }
}

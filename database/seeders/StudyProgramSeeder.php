<?php

namespace Database\Seeders;

use App\Models\Faculty;
use App\Models\StudyProgram;
use Illuminate\Database\Seeder;

/**
 * StudyProgramSeeder
 *
 * Data program studi UNISYA.
 * Kolom sesuai migration 000005:
 *   faculty_id, code, name, degree_level, accreditation, head_name, is_active
 */
class StudyProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            'FAI' => [
                [
                    'code'          => 'PAI',
                    'name'          => 'Pendidikan Agama Islam',
                    'degree_level'  => 'S1',
                    'accreditation' => 'B',
                    'head_name'     => null,
                    'is_active'     => true,
                ],
                [
                    'code'          => 'HES',
                    'name'          => 'Hukum Ekonomi Syariah',
                    'degree_level'  => 'S1',
                    'accreditation' => 'B',
                    'head_name'     => null,
                    'is_active'     => true,
                ],
                [
                    'code'          => 'MDA',
                    'name'          => 'Manajemen Dakwah',
                    'degree_level'  => 'S1',
                    'accreditation' => 'B',
                    'head_name'     => null,
                    'is_active'     => true,
                ],
            ],
            'FT' => [
                [
                    'code'          => 'TI',
                    'name'          => 'Teknik Informatika',
                    'degree_level'  => 'S1',
                    'accreditation' => 'B',
                    'head_name'     => null,
                    'is_active'     => true,
                ],
                [
                    'code'          => 'SI',
                    'name'          => 'Sistem Informasi',
                    'degree_level'  => 'S1',
                    'accreditation' => 'B',
                    'head_name'     => null,
                    'is_active'     => true,
                ],
            ],
            'FE' => [
                [
                    'code'          => 'MNJ',
                    'name'          => 'Manajemen',
                    'degree_level'  => 'S1',
                    'accreditation' => 'B',
                    'head_name'     => null,
                    'is_active'     => true,
                ],
                [
                    'code'          => 'AKT',
                    'name'          => 'Akuntansi',
                    'degree_level'  => 'S1',
                    'accreditation' => 'B',
                    'head_name'     => null,
                    'is_active'     => true,
                ],
            ],
            'FKIP' => [
                [
                    'code'          => 'PGSD',
                    'name'          => 'Pendidikan Guru Sekolah Dasar',
                    'degree_level'  => 'S1',
                    'accreditation' => 'B',
                    'head_name'     => null,
                    'is_active'     => true,
                ],
            ],
        ];

        foreach ($programs as $facultyCode => $prodiList) {
            $faculty = Faculty::where('code', $facultyCode)->first();

            if (! $faculty) {
                $this->command->warn("Faculty [{$facultyCode}] not found — skip prodi under it.");
                continue;
            }

            foreach ($prodiList as $prodi) {
                StudyProgram::updateOrCreate(
                    ['code' => $prodi['code']],
                    array_merge($prodi, ['faculty_id' => $faculty->id])
                );
            }
        }
    }
}

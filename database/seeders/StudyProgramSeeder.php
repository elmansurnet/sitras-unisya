<?php

namespace Database\Seeders;

use App\Models\Faculty;
use App\Models\StudyProgram;
use Illuminate\Database\Seeder;

class StudyProgramSeeder extends Seeder
{
    /**
     * Data program studi UNISYA, minimal 8 prodi sesuai spesifikasi.
     */
    public function run(): void
    {
        $programs = [
            // Fakultas Tarbiyah dan Ilmu Keguruan
            ['faculty_code' => 'FTIK', 'code' => 'PAI',   'name' => 'Pendidikan Agama Islam',               'degree_level' => 'S1'],
            ['faculty_code' => 'FTIK', 'code' => 'PGMI',  'name' => 'Pendidikan Guru Madrasah Ibtidaiyah',  'degree_level' => 'S1'],
            ['faculty_code' => 'FTIK', 'code' => 'PIAUD', 'name' => 'Pendidikan Islam Anak Usia Dini',       'degree_level' => 'S1'],

            // Fakultas Syariah dan Hukum
            ['faculty_code' => 'FSH',  'code' => 'HES',   'name' => 'Hukum Ekonomi Syariah',                 'degree_level' => 'S1'],
            ['faculty_code' => 'FSH',  'code' => 'AS',    'name' => 'Ahwal Syakhsiyyah',                     'degree_level' => 'S1'],

            // Fakultas Ekonomi dan Bisnis Islam
            ['faculty_code' => 'FEBI', 'code' => 'PBS',   'name' => 'Perbankan Syariah',                     'degree_level' => 'S1'],
            ['faculty_code' => 'FEBI', 'code' => 'ES',    'name' => 'Ekonomi Syariah',                       'degree_level' => 'S1'],

            // Fakultas Dakwah dan Ilmu Komunikasi
            ['faculty_code' => 'FDIK', 'code' => 'KPI',   'name' => 'Komunikasi dan Penyiaran Islam',        'degree_level' => 'S1'],
            ['faculty_code' => 'FDIK', 'code' => 'BKI',   'name' => 'Bimbingan dan Konseling Islam',          'degree_level' => 'S1'],
        ];

        foreach ($programs as $program) {
            $faculty = Faculty::where('code', $program['faculty_code'])->firstOrFail();

            StudyProgram::updateOrCreate(
                ['code' => $program['code']],
                [
                    'faculty_id'    => $faculty->id,
                    'code'          => $program['code'],
                    'name'          => $program['name'],
                    'degree_level'  => $program['degree_level'],
                    'accreditation' => null,
                    'head_name'     => null,
                    'is_active'     => true,
                ]
            );
        }
    }
}

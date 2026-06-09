<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Urutan seeder PENTING karena ada FK dependencies:
     * 1. SystemSetting, IndustrySector, SalaryRange — tidak ada FK
     * 2. Faculty — tidak ada FK
     * 3. StudyProgram — FK ke faculty
     * 4. GraduationYear — tidak ada FK
     * 5. SuperadminSeeder — users table
     */
    public function run(): void
    {
        $this->call([
            SystemSettingSeeder::class,
            IndustrySectorSeeder::class,
            SalaryRangeSeeder::class,
            FacultySeeder::class,
            StudyProgramSeeder::class,
            GraduationYearSeeder::class,
            SuperadminSeeder::class,
        ]);
    }
}

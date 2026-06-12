<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Urutan seeder PENTING karena ada FK dependencies:
     *
     *  1. SystemSettingSeeder     — tidak ada FK
     *  2. IndustrySectorSeeder    — tidak ada FK
     *  3. SalaryRangeSeeder       — tidak ada FK
     *  4. FacultySeeder           — tidak ada FK
     *  5. StudyProgramSeeder      — FK ke faculties
     *  6. GraduationYearSeeder    — tidak ada FK
     *  7. SuperadminSeeder        — FK ke users (create user superadmin)
     *  8. NotificationTemplateSeeder — tidak ada FK (standalone master data)
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
            NotificationTemplateSeeder::class,
        ]);
    }
}

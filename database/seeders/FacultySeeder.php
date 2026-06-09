<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Seeder;

class FacultySeeder extends Seeder
{
    /**
     * Data fakultas Universitas Islam Syarifuddin (UNISYA)
     */
    public function run(): void
    {
        $faculties = [
            [
                'code'      => 'FTIK',
                'name'      => 'Fakultas Tarbiyah dan Ilmu Keguruan',
                'dean_name' => null,
                'is_active' => true,
            ],
            [
                'code'      => 'FSH',
                'name'      => 'Fakultas Syariah dan Hukum',
                'dean_name' => null,
                'is_active' => true,
            ],
            [
                'code'      => 'FEBI',
                'name'      => 'Fakultas Ekonomi dan Bisnis Islam',
                'dean_name' => null,
                'is_active' => true,
            ],
            [
                'code'      => 'FDIK',
                'name'      => 'Fakultas Dakwah dan Ilmu Komunikasi',
                'dean_name' => null,
                'is_active' => true,
            ],
        ];

        foreach ($faculties as $data) {
            Faculty::updateOrCreate(['code' => $data['code']], $data);
        }
    }
}

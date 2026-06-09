<?php

namespace Database\Seeders;

use App\Models\SalaryRange;
use Illuminate\Database\Seeder;

class SalaryRangeSeeder extends Seeder
{
    public function run(): void
    {
        $ranges = [
            ['label' => '< Rp 1.000.000',                   'min_salary' => null,      'max_salary' => 999999,   'sort_order' => 1],
            ['label' => 'Rp 1.000.000 – Rp 2.000.000',     'min_salary' => 1000000,   'max_salary' => 2000000,  'sort_order' => 2],
            ['label' => 'Rp 2.000.001 – Rp 3.500.000',     'min_salary' => 2000001,   'max_salary' => 3500000,  'sort_order' => 3],
            ['label' => 'Rp 3.500.001 – Rp 5.000.000',     'min_salary' => 3500001,   'max_salary' => 5000000,  'sort_order' => 4],
            ['label' => 'Rp 5.000.001 – Rp 7.500.000',     'min_salary' => 5000001,   'max_salary' => 7500000,  'sort_order' => 5],
            ['label' => 'Rp 7.500.001 – Rp 10.000.000',    'min_salary' => 7500001,   'max_salary' => 10000000, 'sort_order' => 6],
            ['label' => '> Rp 10.000.000',                  'min_salary' => 10000001,  'max_salary' => null,     'sort_order' => 7],
        ];

        foreach ($ranges as $data) {
            SalaryRange::updateOrCreate(
                ['label' => $data['label']],
                array_merge($data, ['is_active' => true])
            );
        }
    }
}

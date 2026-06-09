<?php

namespace Database\Seeders;

use App\Models\SalaryRange;
use Illuminate\Database\Seeder;

/**
 * SalaryRangeSeeder
 *
 * Master rentang gaji untuk dropdown pilihan alumni.
 * Kolom sesuai migration 000009 & 02_DATABASE.md §2.8:
 *   label, min_value, max_value, order_number, is_active
 */
class SalaryRangeSeeder extends Seeder
{
    public function run(): void
    {
        $ranges = [
            [
                'label'        => '< Rp 1 Juta',
                'min_value'    => 0,
                'max_value'    => 999999,
                'order_number' => 1,
                'is_active'    => true,
            ],
            [
                'label'        => 'Rp 1 - 2 Juta',
                'min_value'    => 1000000,
                'max_value'    => 1999999,
                'order_number' => 2,
                'is_active'    => true,
            ],
            [
                'label'        => 'Rp 2 - 3 Juta',
                'min_value'    => 2000000,
                'max_value'    => 2999999,
                'order_number' => 3,
                'is_active'    => true,
            ],
            [
                'label'        => 'Rp 3 - 5 Juta',
                'min_value'    => 3000000,
                'max_value'    => 4999999,
                'order_number' => 4,
                'is_active'    => true,
            ],
            [
                'label'        => 'Rp 5 - 7 Juta',
                'min_value'    => 5000000,
                'max_value'    => 6999999,
                'order_number' => 5,
                'is_active'    => true,
            ],
            [
                'label'        => 'Rp 7 - 10 Juta',
                'min_value'    => 7000000,
                'max_value'    => 9999999,
                'order_number' => 6,
                'is_active'    => true,
            ],
            [
                'label'        => 'Rp 10 - 15 Juta',
                'min_value'    => 10000000,
                'max_value'    => 14999999,
                'order_number' => 7,
                'is_active'    => true,
            ],
            [
                'label'        => '> Rp 15 Juta',
                'min_value'    => 15000000,
                'max_value'    => null,
                'order_number' => 8,
                'is_active'    => true,
            ],
        ];

        foreach ($ranges as $range) {
            SalaryRange::updateOrCreate(
                ['label' => $range['label']],
                $range
            );
        }
    }
}

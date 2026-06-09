<?php

namespace Database\Seeders;

use App\Models\IndustrySector;
use Illuminate\Database\Seeder;

/**
 * IndustrySectorSeeder
 *
 * Master sektor industri untuk dropdown.
 * Kolom sesuai migration 000008: name, code, is_active.
 */
class IndustrySectorSeeder extends Seeder
{
    public function run(): void
    {
        $sectors = [
            ['code' => 'PEND', 'name' => 'Pendidikan',                    'is_active' => true],
            ['code' => 'KES',  'name' => 'Kesehatan',                      'is_active' => true],
            ['code' => 'TEK',  'name' => 'Teknologi Informasi',            'is_active' => true],
            ['code' => 'KEU',  'name' => 'Keuangan & Perbankan',           'is_active' => true],
            ['code' => 'PEM',  'name' => 'Pemerintahan & Layanan Publik',  'is_active' => true],
            ['code' => 'PRT',  'name' => 'Pertanian & Perkebunan',         'is_active' => true],
            ['code' => 'MFG',  'name' => 'Manufaktur & Industri',          'is_active' => true],
            ['code' => 'DAG',  'name' => 'Perdagangan & Retail',           'is_active' => true],
            ['code' => 'KON',  'name' => 'Konstruksi & Properti',          'is_active' => true],
            ['code' => 'TRS',  'name' => 'Transportasi & Logistik',        'is_active' => true],
            ['code' => 'HOS',  'name' => 'Perhotelan & Pariwisata',        'is_active' => true],
            ['code' => 'MED',  'name' => 'Media & Komunikasi',             'is_active' => true],
            ['code' => 'HKM',  'name' => 'Hukum & Konsultasi',             'is_active' => true],
            ['code' => 'AGM',  'name' => 'Keagamaan & Sosial',             'is_active' => true],
            ['code' => 'WRS',  'name' => 'Wirausaha / Usaha Mandiri',      'is_active' => true],
            ['code' => 'LIN',  'name' => 'Lainnya',                        'is_active' => true],
        ];

        foreach ($sectors as $sector) {
            IndustrySector::updateOrCreate(
                ['code' => $sector['code']],
                $sector
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\IndustrySector;
use Illuminate\Database\Seeder;

class IndustrySectorSeeder extends Seeder
{
    public function run(): void
    {
        $sectors = [
            ['code' => 'PENDIDIKAN',   'name' => 'Pendidikan dan Pelatihan'],
            ['code' => 'PEMERINTAHAN', 'name' => 'Pemerintahan dan Lembaga Publik'],
            ['code' => 'PERBANKAN',    'name' => 'Perbankan dan Keuangan Syariah'],
            ['code' => 'HUKUM',        'name' => 'Hukum dan Konsultan Hukum'],
            ['code' => 'NGO',          'name' => 'LSM dan Organisasi Nirlaba'],
            ['code' => 'PERDAGANGAN',  'name' => 'Perdagangan dan Retail'],
            ['code' => 'KESEHATAN',    'name' => 'Kesehatan dan Farmasi'],
            ['code' => 'MEDIA',        'name' => 'Media, Komunikasi dan Jurnalistik'],
            ['code' => 'TEKNOLOGI',    'name' => 'Teknologi Informasi dan Komunikasi'],
            ['code' => 'PERTANIAN',    'name' => 'Pertanian, Perkebunan dan Perikanan'],
            ['code' => 'MANUFAKTUR',   'name' => 'Manufaktur dan Industri'],
            ['code' => 'PARIWISATA',   'name' => 'Pariwisata dan Perhotelan'],
            ['code' => 'WIRASWASTA',   'name' => 'Wirausaha / Usaha Mandiri'],
            ['code' => 'LAINNYA',      'name' => 'Lainnya'],
        ];

        foreach ($sectors as $data) {
            IndustrySector::updateOrCreate(['code' => $data['code']], array_merge($data, ['is_active' => true]));
        }
    }
}

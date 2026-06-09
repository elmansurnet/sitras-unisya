<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@unisya.ac.id'],
            [
                'name'              => 'Super Administrator',
                'email'             => 'superadmin@unisya.ac.id',
                'phone'             => null,
                'role'              => 'superadmin',
                // bcrypt dengan cost factor 12 sesuai spesifikasi keamanan
                'password'          => Hash::make(
                    env('SUPERADMIN_PASSWORD', 'SitrasSuperAdmin@2026!'),
                    ['rounds' => 12]
                ),
                'email_verified_at' => now(),
                'is_active'         => true,
                'login_attempts'    => 0,
                'locked_until'      => null,
            ]
        );
    }
}

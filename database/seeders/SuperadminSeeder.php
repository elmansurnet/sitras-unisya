<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SuperadminSeeder
 *
 * Membuat 1 akun superadmin default.
 * Password di-hash dengan bcrypt cost factor 12 sesuai 07_SECURITY.md.
 *
 * Kolom sesuai migration users (0001_01_01_000000):
 *   name, email, phone, role, password, is_active,
 *   login_attempts, locked_until, email_verified_at
 */
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
                'password'          => Hash::make('SuperAdmin@UNISYA2026!', ['rounds' => 12]),
                'is_active'         => true,
                'login_attempts'    => 0,
                'locked_until'      => null,
                'email_verified_at' => now(),
            ]
        );
    }
}

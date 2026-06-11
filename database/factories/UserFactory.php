<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 *
 * Sesuai skema users (02_DATABASE.md §2.1) dan User model sesi 1A.
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'role'              => 'alumni',
            'is_active'         => true,
            'login_attempts'    => 0,
            'locked_until'      => null,
            'last_login_at'     => null,
            'remember_token'    => Str::random(10),
        ];
    }

    // ── State helpers ──────────────────────────────────────────────────────

    public function superadmin(): static
    {
        return $this->state(fn () => ['role' => 'superadmin']);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    public function alumni(): static
    {
        return $this->state(fn () => ['role' => 'alumni']);
    }

    public function employer(): static
    {
        return $this->state(fn () => ['role' => 'employer']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function locked(): static
    {
        return $this->state(fn () => [
            'login_attempts' => 5,
            'locked_until'   => now()->addMinutes(15),
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}

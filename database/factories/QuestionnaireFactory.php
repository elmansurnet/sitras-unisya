<?php

namespace Database\Factories;

use App\Models\Questionnaire;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Questionnaire>
 *
 * Sesuai skema questionnaires (02_DATABASE.md §2.16) dan Questionnaire model (3A.2).
 */
class QuestionnaireFactory extends Factory
{
    protected $model = Questionnaire::class;

    public function definition(): array
    {
        return [
            'title'             => fake()->sentence(4),
            'description'       => fake()->paragraph(),
            'type'              => fake()->randomElement(['alumni', 'employer']),
            'version'           => 1,
            'status'            => 'draft',
            'is_paginated'      => false,
            'estimated_minutes' => fake()->numberBetween(5, 30),
            'published_at'      => null,
            'created_by'        => User::factory()->admin(),
        ];
    }

    // ── State helpers ──────────────────────────────────────────────────────

    /** Kuesioner berstatus draft (default). */
    public function draft(): static
    {
        return $this->state(fn () => [
            'status'       => 'draft',
            'published_at' => null,
        ]);
    }

    /** Kuesioner berstatus aktif (sudah dipublikasikan). */
    public function aktif(): static
    {
        return $this->state(fn () => [
            'status'       => 'aktif',
            'published_at' => now()->subDay(),
        ]);
    }

    /** Kuesioner berstatus arsip. */
    public function arsip(): static
    {
        return $this->state(fn () => [
            'status'       => 'arsip',
            'published_at' => now()->subMonth(),
        ]);
    }

    /** Kuesioner bertipe alumni. */
    public function forAlumni(): static
    {
        return $this->state(fn () => ['type' => 'alumni']);
    }

    /** Kuesioner bertipe employer. */
    public function forEmployer(): static
    {
        return $this->state(fn () => ['type' => 'employer']);
    }

    /** Kuesioner dengan is_paginated = true. */
    public function paginated(): static
    {
        return $this->state(fn () => ['is_paginated' => true]);
    }

    /** Kuesioner dibuat oleh user tertentu. */
    public function createdBy(int $userId): static
    {
        return $this->state(fn () => ['created_by' => $userId]);
    }
}

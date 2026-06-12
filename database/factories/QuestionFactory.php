<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Questionnaire;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 *
 * Sesuai skema questions (02_DATABASE.md §2.18) dan Question model (3A.4).
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'questionnaire_id'  => Questionnaire::factory(),
            'section_id'        => null,
            'question_text'     => fake()->sentence() . '?',
            'question_type'     => 'text',
            'is_required'       => true,
            'order_number'      => fake()->numberBetween(1, 100),
            'help_text'         => null,
            'placeholder'       => null,
            'validation_rules'  => null,
            'conditional_logic' => null,
        ];
    }

    // ── State helpers per question_type ────────────────────────────────────

    public function text(): static
    {
        return $this->state(fn () => ['question_type' => 'text']);
    }

    public function textarea(): static
    {
        return $this->state(fn () => ['question_type' => 'textarea']);
    }

    public function radio(): static
    {
        return $this->state(fn () => ['question_type' => 'radio']);
    }

    public function checkbox(): static
    {
        return $this->state(fn () => ['question_type' => 'checkbox']);
    }

    public function select(): static
    {
        return $this->state(fn () => ['question_type' => 'select']);
    }

    public function scale(): static
    {
        return $this->state(fn () => ['question_type' => 'scale']);
    }

    public function date(): static
    {
        return $this->state(fn () => ['question_type' => 'date']);
    }

    public function optional(): static
    {
        return $this->state(fn () => ['is_required' => false]);
    }

    public function inSection(int $sectionId): static
    {
        return $this->state(fn () => ['section_id' => $sectionId]);
    }

    public function ordered(int $orderNumber): static
    {
        return $this->state(fn () => ['order_number' => $orderNumber]);
    }
}

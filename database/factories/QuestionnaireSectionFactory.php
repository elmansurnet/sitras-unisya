<?php

namespace Database\Factories;

use App\Models\Questionnaire;
use App\Models\QuestionnaireSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionnaireSection>
 *
 * Sesuai skema questionnaire_sections (02_DATABASE.md §2.17).
 */
class QuestionnaireSectionFactory extends Factory
{
    protected $model = QuestionnaireSection::class;

    public function definition(): array
    {
        static $order = 1;

        return [
            'questionnaire_id' => Questionnaire::factory(),
            'title'            => fake()->sentence(3),
            'description'      => fake()->optional(0.6)->sentence(),
            'order_number'     => $order++,
        ];
    }

    /** Reset counter order_number (panggil di setUp test). */
    public function ordered(int $orderNumber): static
    {
        return $this->state(fn () => ['order_number' => $orderNumber]);
    }
}

<?php

namespace Tests\Feature\Survey;

use App\Models\Employer;
use App\Models\Questionnaire;
use App\Models\QuestionnaireSection;
use App\Models\Question;
use App\Models\SurveyPeriod;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployerSurveyTest extends TestCase
{
    use RefreshDatabase;

    private Employer $employer;
    private string $token;
    private Questionnaire $questionnaire;
    private SurveyPeriod $period;
    private Question $question;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpEmployerWithSurvey();
    }

    private function setUpEmployerWithSurvey(): void
    {
        $this->employer = Employer::factory()->create([
            'survey_status'            => 'terkirim',
            'survey_token'             => $rawToken = \Illuminate\Support\Str::random(64),
            'survey_token_expires_at'  => now()->addDays(30),
        ]);
        $this->token = $rawToken;

        $this->questionnaire = Questionnaire::factory()->create([
            'type'   => 'employer',
            'status' => 'aktif',
        ]);

        $section = QuestionnaireSection::factory()->create([
            'questionnaire_id' => $this->questionnaire->id,
            'order_number'     => 1,
        ]);

        $this->question = Question::factory()->create([
            'questionnaire_id' => $this->questionnaire->id,
            'section_id'       => $section->id,
            'question_type'    => 'text',
            'is_required'      => true,
            'order_number'     => 1,
        ]);

        $this->period = SurveyPeriod::factory()->create(['status' => 'active']);
    }

    // ─────────────────────────────────────────
    // GET /api/v1/employer/survey?token=xxx
    // ─────────────────────────────────────────

    /** @test */
    public function employer_can_get_survey_with_valid_token(): void
    {
        $response = $this->getJson("/api/v1/employer/survey?token={$this->token}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'employer',
                    'questionnaire' => ['id', 'title', 'sections'],
                    'response',
                ],
            ]);
    }

    /** @test */
    public function employer_survey_fails_with_invalid_token(): void
    {
        $this->getJson('/api/v1/employer/survey?token=invalid-token-xyz')
            ->assertUnauthorized();
    }

    /** @test */
    public function employer_survey_fails_with_expired_token(): void
    {
        $this->employer->update(['survey_token_expires_at' => now()->subDay()]);

        $this->getJson("/api/v1/employer/survey?token={$this->token}")
            ->assertUnauthorized();
    }

    // ─────────────────────────────────────────
    // POST /api/v1/employer/survey/draft
    // ─────────────────────────────────────────

    /** @test */
    public function employer_can_save_draft(): void
    {
        $payload = [
            'token'            => $this->token,
            'questionnaire_id' => $this->questionnaire->id,
            'survey_period_id' => $this->period->id,
            'answers'          => [
                ['question_id' => $this->question->id, 'answer_text' => 'Jawaban draft'],
            ],
        ];

        $response = $this->postJson('/api/v1/employer/survey/draft', $payload);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('survey_responses', [
            'employer_id'      => $this->employer->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status'           => 'draft',
        ]);
    }

    /** @test */
    public function employer_draft_with_invalid_token_is_unauthorized(): void
    {
        $payload = [
            'token'            => 'bad-token',
            'questionnaire_id' => $this->questionnaire->id,
            'survey_period_id' => $this->period->id,
            'answers'          => [],
        ];

        $this->postJson('/api/v1/employer/survey/draft', $payload)
            ->assertUnauthorized();
    }

    // ─────────────────────────────────────────
    // POST /api/v1/employer/survey/submit
    // ─────────────────────────────────────────

    /** @test */
    public function employer_can_submit_survey_with_valid_token(): void
    {
        $payload = [
            'token'            => $this->token,
            'questionnaire_id' => $this->questionnaire->id,
            'survey_period_id' => $this->period->id,
            'answers'          => [
                ['question_id' => $this->question->id, 'answer_text' => 'Jawaban submit'],
            ],
        ];

        $response = $this->postJson('/api/v1/employer/survey/submit', $payload);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('survey_responses', [
            'employer_id' => $this->employer->id,
            'status'      => 'selesai',
        ]);
    }

    /** @test */
    public function submit_updates_employer_survey_status_to_selesai(): void
    {
        $payload = [
            'token'            => $this->token,
            'questionnaire_id' => $this->questionnaire->id,
            'survey_period_id' => $this->period->id,
            'answers'          => [
                ['question_id' => $this->question->id, 'answer_text' => 'Jawaban'],
            ],
        ];

        $this->postJson('/api/v1/employer/survey/submit', $payload);

        $this->assertDatabaseHas('employers', [
            'id'            => $this->employer->id,
            'survey_status' => 'selesai',
        ]);
    }

    /** @test */
    public function employer_cannot_submit_survey_twice(): void
    {
        SurveyResponse::factory()->create([
            'employer_id'      => $this->employer->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status'           => 'selesai',
        ]);
        $this->employer->update(['survey_status' => 'selesai']);

        $payload = [
            'token'            => $this->token,
            'questionnaire_id' => $this->questionnaire->id,
            'survey_period_id' => $this->period->id,
            'answers'          => [
                ['question_id' => $this->question->id, 'answer_text' => 'Duplikat'],
            ],
        ];

        $this->postJson('/api/v1/employer/survey/submit', $payload)
            ->assertStatus(409);
    }

    /** @test */
    public function submit_without_required_answer_returns_422(): void
    {
        $payload = [
            'token'            => $this->token,
            'questionnaire_id' => $this->questionnaire->id,
            'survey_period_id' => $this->period->id,
            'answers'          => [],
        ];

        $this->postJson('/api/v1/employer/survey/submit', $payload)
            ->assertUnprocessable();
    }
}

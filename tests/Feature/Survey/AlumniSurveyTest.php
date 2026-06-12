<?php

namespace Tests\Feature\Survey;

use App\Models\Alumni;
use App\Models\GraduationYear;
use App\Models\Questionnaire;
use App\Models\QuestionnaireSection;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\StudyProgram;
use App\Models\SurveyPeriod;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumniSurveyTest extends TestCase
{
    use RefreshDatabase;

    private User $alumniUser;
    private Alumni $alumni;
    private Questionnaire $questionnaire;
    private SurveyPeriod $period;
    private Question $question;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAlumniWithSurvey();
    }

    private function setUpAlumniWithSurvey(): void
    {
        $faculty = \App\Models\Faculty::factory()->create();
        $studyProgram = StudyProgram::factory()->create(['faculty_id' => $faculty->id]);
        $graduationYear = GraduationYear::factory()->create();

        $this->alumniUser = User::factory()->create(['role' => 'alumni']);
        $this->alumni = Alumni::factory()->create([
            'user_id'            => $this->alumniUser->id,
            'study_program_id'   => $studyProgram->id,
            'graduation_year_id' => $graduationYear->id,
            'survey_status'      => 'terkirim',
        ]);

        $this->questionnaire = Questionnaire::factory()->create([
            'type'   => 'alumni',
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

        $this->period = SurveyPeriod::factory()->create([
            'status'                  => 'active',
            'target_graduation_years' => [$graduationYear->id],
        ]);

        // Attach alumni to period
        $this->period->alumni()->attach($this->alumni->id, [
            'invitation_sent_at' => now(),
            'invitation_channel' => 'whatsapp',
        ]);
    }

    // ─────────────────────────────────────────
    // GET /api/v1/alumni/survey
    // ─────────────────────────────────────────

    /** @test */
    public function alumni_can_get_their_active_survey(): void
    {
        $response = $this->actingAs($this->alumniUser, 'sanctum')
            ->getJson('/api/v1/alumni/survey');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period',
                    'questionnaire' => [
                        'id',
                        'title',
                        'sections',
                    ],
                    'response',
                ],
            ]);
    }

    /** @test */
    public function alumni_without_active_period_gets_404(): void
    {
        $this->period->update(['status' => 'closed']);

        $response = $this->actingAs($this->alumniUser, 'sanctum')
            ->getJson('/api/v1/alumni/survey');

        $response->assertNotFound();
    }

    /** @test */
    public function unauthenticated_user_cannot_access_survey(): void
    {
        $this->getJson('/api/v1/alumni/survey')
            ->assertUnauthorized();
    }

    /** @test */
    public function non_alumni_role_cannot_access_alumni_survey(): void
    {
        $adminUser = User::factory()->create(['role' => 'admin']);

        $this->actingAs($adminUser, 'sanctum')
            ->getJson('/api/v1/alumni/survey')
            ->assertForbidden();
    }

    // ─────────────────────────────────────────
    // POST /api/v1/alumni/survey/draft
    // ─────────────────────────────────────────

    /** @test */
    public function alumni_can_save_draft_with_partial_answers(): void
    {
        $payload = [
            'survey_period_id'  => $this->period->id,
            'questionnaire_id'  => $this->questionnaire->id,
            'answers'           => [
                [
                    'question_id' => $this->question->id,
                    'answer_text' => 'Jawaban sementara',
                ],
            ],
        ];

        $response = $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/v1/alumni/survey/draft', $payload);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('survey_responses', [
            'alumni_id'        => $this->alumni->id,
            'survey_period_id' => $this->period->id,
            'status'           => 'draft',
        ]);
    }

    /** @test */
    public function save_draft_updates_alumni_survey_status_to_sedang_mengisi(): void
    {
        $payload = [
            'survey_period_id' => $this->period->id,
            'questionnaire_id' => $this->questionnaire->id,
            'answers'          => [
                ['question_id' => $this->question->id, 'answer_text' => 'Jawaban'],
            ],
        ];

        $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/v1/alumni/survey/draft', $payload);

        $this->assertDatabaseHas('alumni', [
            'id'            => $this->alumni->id,
            'survey_status' => 'sedangmengisi',
        ]);
    }

    /** @test */
    public function save_draft_with_invalid_questionnaire_returns_422(): void
    {
        $payload = [
            'survey_period_id' => $this->period->id,
            'questionnaire_id' => 9999,
            'answers'          => [],
        ];

        $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/v1/alumni/survey/draft', $payload)
            ->assertUnprocessable();
    }

    // ─────────────────────────────────────────
    // POST /api/v1/alumni/survey/submit
    // ─────────────────────────────────────────

    /** @test */
    public function alumni_can_submit_survey_with_all_required_answers(): void
    {
        $payload = [
            'survey_period_id' => $this->period->id,
            'questionnaire_id' => $this->questionnaire->id,
            'answers'          => [
                ['question_id' => $this->question->id, 'answer_text' => 'Jawaban lengkap'],
            ],
        ];

        $response = $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/v1/alumni/survey/submit', $payload);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('survey_responses', [
            'alumni_id'        => $this->alumni->id,
            'survey_period_id' => $this->period->id,
            'status'           => 'selesai',
        ]);
    }

    /** @test */
    public function submit_updates_alumni_survey_status_to_selesai(): void
    {
        $payload = [
            'survey_period_id' => $this->period->id,
            'questionnaire_id' => $this->questionnaire->id,
            'answers'          => [
                ['question_id' => $this->question->id, 'answer_text' => 'Jawaban'],
            ],
        ];

        $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/v1/alumni/survey/submit', $payload);

        $this->assertDatabaseHas('alumni', [
            'id'            => $this->alumni->id,
            'survey_status' => 'selesai',
        ]);
    }

    /** @test */
    public function submit_without_required_answer_returns_422(): void
    {
        $payload = [
            'survey_period_id' => $this->period->id,
            'questionnaire_id' => $this->questionnaire->id,
            'answers'          => [], // pertanyaan required tidak dijawab
        ];

        $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/v1/alumni/survey/submit', $payload)
            ->assertUnprocessable();
    }

    /** @test */
    public function alumni_cannot_submit_survey_twice(): void
    {
        // Submit pertama
        SurveyResponse::factory()->create([
            'alumni_id'        => $this->alumni->id,
            'survey_period_id' => $this->period->id,
            'questionnaire_id' => $this->questionnaire->id,
            'status'           => 'selesai',
        ]);
        $this->alumni->update(['survey_status' => 'selesai']);

        $payload = [
            'survey_period_id' => $this->period->id,
            'questionnaire_id' => $this->questionnaire->id,
            'answers'          => [
                ['question_id' => $this->question->id, 'answer_text' => 'Duplikat'],
            ],
        ];

        $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/v1/alumni/survey/submit', $payload)
            ->assertStatus(409);
    }

    /** @test */
    public function draft_is_idempotent_on_second_save(): void
    {
        $payload = [
            'survey_period_id' => $this->period->id,
            'questionnaire_id' => $this->questionnaire->id,
            'answers'          => [
                ['question_id' => $this->question->id, 'answer_text' => 'Draft pertama'],
            ],
        ];

        $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/v1/alumni/survey/draft', $payload);

        // Simpan draft kedua kali — harus update, bukan create baru
        $payload['answers'][0]['answer_text'] = 'Draft kedua';
        $this->actingAs($this->alumniUser, 'sanctum')
            ->postJson('/api/v1/alumni/survey/draft', $payload)
            ->assertOk();

        $this->assertDatabaseCount('survey_responses', 1);
    }
}

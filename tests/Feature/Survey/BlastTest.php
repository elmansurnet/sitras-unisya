<?php

namespace Tests\Feature\Survey;

use App\Jobs\ProcessSurveyBlast;
use App\Models\Alumni;
use App\Models\Faculty;
use App\Models\GraduationYear;
use App\Models\Questionnaire;
use App\Models\StudyProgram;
use App\Models\SurveyPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BlastTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private SurveyPeriod $period;
    private Questionnaire $questionnaire;
    private GraduationYear $graduationYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'admin']);

        $faculty           = Faculty::factory()->create();
        $studyProgram      = StudyProgram::factory()->create(['faculty_id' => $faculty->id]);
        $this->graduationYear = GraduationYear::factory()->create();

        $this->questionnaire = Questionnaire::factory()->create([
            'type'   => 'alumni',
            'status' => 'aktif',
        ]);

        $this->period = SurveyPeriod::factory()->create([
            'status'                  => 'active',
            'target_graduation_years' => [$this->graduationYear->id],
        ]);

        // Buat 3 alumni di target angkatan
        Alumni::factory(3)->create([
            'study_program_id'   => $studyProgram->id,
            'graduation_year_id' => $this->graduationYear->id,
            'survey_status'      => 'belumdisurvei',
        ]);
    }

    /** @test */
    public function admin_can_send_blast_invitations_for_active_period(): void
    {
        Queue::fake();

        $payload = [
            'questionnaire_id' => $this->questionnaire->id,
            'channel'          => 'whatsapp',
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/v1/admin/survey-periods/{$this->period->id}/send-invitations", $payload);

        $response->assertOk()
            ->assertJsonPath('success', true);

        Queue::assertPushed(ProcessSurveyBlast::class);
    }

    /** @test */
    public function blast_dispatches_one_job_per_blast_call(): void
    {
        Queue::fake();

        $payload = [
            'questionnaire_id' => $this->questionnaire->id,
            'channel'          => 'whatsapp',
        ];

        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/v1/admin/survey-periods/{$this->period->id}/send-invitations", $payload);

        Queue::assertPushed(ProcessSurveyBlast::class, 1);
    }

    /** @test */
    public function blast_is_pushed_to_low_queue(): void
    {
        Queue::fake();

        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/v1/admin/survey-periods/{$this->period->id}/send-invitations", [
                'questionnaire_id' => $this->questionnaire->id,
                'channel'          => 'whatsapp',
            ]);

        Queue::assertPushedOn('low', ProcessSurveyBlast::class);
    }

    /** @test */
    public function blast_for_closed_period_returns_422(): void
    {
        Queue::fake();
        $this->period->update(['status' => 'closed']);

        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/v1/admin/survey-periods/{$this->period->id}/send-invitations", [
                'questionnaire_id' => $this->questionnaire->id,
                'channel'          => 'whatsapp',
            ])
            ->assertUnprocessable();

        Queue::assertNotPushed(ProcessSurveyBlast::class);
    }

    /** @test */
    public function blast_for_draft_period_returns_422(): void
    {
        Queue::fake();
        $this->period->update(['status' => 'draft']);

        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/v1/admin/survey-periods/{$this->period->id}/send-invitations", [
                'questionnaire_id' => $this->questionnaire->id,
                'channel'          => 'whatsapp',
            ])
            ->assertUnprocessable();

        Queue::assertNotPushed(ProcessSurveyBlast::class);
    }

    /** @test */
    public function blast_requires_questionnaire_id(): void
    {
        Queue::fake();

        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/v1/admin/survey-periods/{$this->period->id}/send-invitations", [
                'channel' => 'whatsapp',
            ])
            ->assertUnprocessable();
    }

    /** @test */
    public function blast_requires_valid_channel(): void
    {
        Queue::fake();

        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/v1/admin/survey-periods/{$this->period->id}/send-invitations", [
                'questionnaire_id' => $this->questionnaire->id,
                'channel'          => 'telegram', // invalid
            ])
            ->assertUnprocessable();
    }

    /** @test */
    public function non_admin_cannot_send_blast(): void
    {
        Queue::fake();
        $alumniUser = User::factory()->create(['role' => 'alumni']);

        $this->actingAs($alumniUser, 'sanctum')
            ->postJson("/api/v1/admin/survey-periods/{$this->period->id}/send-invitations", [
                'questionnaire_id' => $this->questionnaire->id,
                'channel'          => 'whatsapp',
            ])
            ->assertForbidden();

        Queue::assertNotPushed(ProcessSurveyBlast::class);
    }

    /** @test */
    public function alumni_survey_status_is_updated_to_terkirim_after_blast_job_runs(): void
    {
        // Jalankan job secara sinkron (tanpa Queue::fake)
        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/v1/admin/survey-periods/{$this->period->id}/send-invitations", [
                'questionnaire_id' => $this->questionnaire->id,
                'channel'          => 'whatsapp',
            ]);

        // Jalankan job yang dipush ke queue
        $this->artisan('queue:work', [
            '--queue' => 'low',
            '--once'  => true,
        ]);

        $this->assertDatabaseHas('alumni', [
            'graduation_year_id' => $this->graduationYear->id,
            'survey_status'      => 'terkirim',
        ]);
    }
}

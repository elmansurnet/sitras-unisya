<?php

namespace Tests\Feature\Alumni;

use App\Models\Alumni;
use App\Models\AlumniWorkHistory;
use App\Models\Faculty;
use App\Models\GraduationYear;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature Test: Alumni Work History API
 *
 * Endpoint yang diuji:
 *   GET    /api/v1/alumni/work-histories                    — 05_API.md §11.4
 *   POST   /api/v1/alumni/work-histories/{alumni}           — 05_API.md §11.5
 *   PUT    /api/v1/alumni/work-histories/{alumni}/{wh}      — 05_API.md §11.6
 *   DELETE /api/v1/alumni/work-histories/{alumni}/{wh}      — 05_API.md §11.7
 */
class WorkHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $alumniUser;
    private Alumni $alumni;
    private string $token;
    private AlumniWorkHistory $workHistory;

    protected function setUp(): void
    {
        parent::setUp();

        $faculty        = Faculty::factory()->create();
        $studyProgram   = StudyProgram::factory()->create(['faculty_id' => $faculty->id]);
        $graduationYear = GraduationYear::factory()->create(['year' => 2022]);

        $this->alumniUser = User::factory()->create([
            'role'      => 'alumni',
            'is_active' => true,
        ]);

        $this->alumni = Alumni::factory()->create([
            'user_id'            => $this->alumniUser->id,
            'study_program_id'   => $studyProgram->id,
            'graduation_year_id' => $graduationYear->id,
            'nim'                => '20210001',
            'full_name'          => 'Budi Santoso',
            'is_active'          => true,
        ]);

        $this->workHistory = AlumniWorkHistory::factory()->create([
            'alumni_id'    => $this->alumni->id,
            'company_name' => 'PT Maju Bersama',
            'position'     => 'Software Engineer',
            'start_date'   => '2022-08-01',
            'is_current'   => true,
        ]);

        $this->token = $this->alumniUser->createToken('test')->plainTextToken;
    }

    // =========================================================================
    // GET /api/v1/alumni/work-histories
    // =========================================================================

    /** @test */
    public function alumni_can_list_own_work_histories(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/alumni/work-histories');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id', 'company_name', 'position',
                        'start_date', 'is_current',
                    ],
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function unauthenticated_user_cannot_list_work_histories(): void
    {
        $this->getJson('/api/v1/alumni/work-histories')
            ->assertUnauthorized();
    }

    // =========================================================================
    // POST /api/v1/alumni/work-histories/{alumni}
    // =========================================================================

    /** @test */
    public function alumni_can_store_work_history(): void
    {
        $payload = [
            'company_name'    => 'PT Teknologi Maju',
            'position'        => 'Backend Developer',
            'employment_type' => 'penuh_waktu',
            'start_date'      => '2023-01-01',
            'is_current'      => false,
            'end_date'        => '2024-01-01',
            'city'            => 'Jakarta',
            'province'        => 'DKI Jakarta',
        ];

        $response = $this->withToken($this->token)
            ->postJson("/api/v1/alumni/work-histories/{$this->alumni->id}", $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.company_name', 'PT Teknologi Maju')
            ->assertJsonPath('data.employment_type', 'penuh_waktu');

        $this->assertDatabaseHas('alumni_work_histories', [
            'alumni_id'    => $this->alumni->id,
            'company_name' => 'PT Teknologi Maju',
            'position'     => 'Backend Developer',
        ]);
    }

    /** @test */
    public function store_sets_other_jobs_as_not_current_when_is_current_true(): void
    {
        // workHistory lama is_current = true
        $this->assertTrue((bool) $this->workHistory->fresh()->is_current);

        $this->withToken($this->token)
            ->postJson("/api/v1/alumni/work-histories/{$this->alumni->id}", [
                'company_name' => 'PT Baru Sekali',
                'position'     => 'CTO',
                'start_date'   => '2024-06-01',
                'is_current'   => true,
            ])
            ->assertCreated();

        // workHistory lama harus di-set false
        $this->assertDatabaseHas('alumni_work_histories', [
            'id'         => $this->workHistory->id,
            'is_current' => false,
        ]);
    }

    /** @test */
    public function store_validates_required_fields(): void
    {
        $this->withToken($this->token)
            ->postJson("/api/v1/alumni/work-histories/{$this->alumni->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['company_name', 'position', 'start_date', 'is_current']);
    }

    /** @test */
    public function store_validates_employment_type_enum(): void
    {
        $this->withToken($this->token)
            ->postJson("/api/v1/alumni/work-histories/{$this->alumni->id}", [
                'company_name'    => 'PT Test',
                'position'        => 'Dev',
                'start_date'      => '2024-01-01',
                'is_current'      => true,
                'employment_type' => 'full_time', // nilai lama — seharusnya penuh_waktu
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['employment_type']);
    }

    /** @test */
    public function store_rejects_end_date_before_start_date(): void
    {
        $this->withToken($this->token)
            ->postJson("/api/v1/alumni/work-histories/{$this->alumni->id}", [
                'company_name' => 'PT Test',
                'position'     => 'Dev',
                'start_date'   => '2024-06-01',
                'end_date'     => '2024-01-01', // sebelum start_date
                'is_current'   => false,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    }

    /** @test */
    public function alumni_cannot_store_work_history_for_other_alumni(): void
    {
        $otherUser = User::factory()->create(['role' => 'alumni', 'is_active' => true]);
        $faculty   = Faculty::first();
        $sp        = StudyProgram::first();
        $gy        = GraduationYear::first();

        $otherAlumni = Alumni::factory()->create([
            'user_id'            => $otherUser->id,
            'study_program_id'   => $sp->id,
            'graduation_year_id' => $gy->id,
            'nim'                => '20210099',
        ]);

        $this->withToken($this->token) // token milik $this->alumniUser
            ->postJson("/api/v1/alumni/work-histories/{$otherAlumni->id}", [
                'company_name' => 'PT Test',
                'position'     => 'Dev',
                'start_date'   => '2024-01-01',
                'is_current'   => true,
            ])
            ->assertForbidden();
    }

    // =========================================================================
    // PUT /api/v1/alumni/work-histories/{alumni}/{workHistory}
    // =========================================================================

    /** @test */
    public function alumni_can_update_own_work_history(): void
    {
        $response = $this->withToken($this->token)
            ->putJson("/api/v1/alumni/work-histories/{$this->alumni->id}/{$this->workHistory->id}", [
                'position'    => 'Senior Software Engineer',
                'city'        => 'Bandung',
                'description' => 'Mengerjakan backend microservices.',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.position', 'Senior Software Engineer');

        $this->assertDatabaseHas('alumni_work_histories', [
            'id'       => $this->workHistory->id,
            'position' => 'Senior Software Engineer',
            'city'     => 'Bandung',
        ]);
    }

    /** @test */
    public function alumni_cannot_update_work_history_of_other_alumni(): void
    {
        $otherUser   = User::factory()->create(['role' => 'alumni', 'is_active' => true]);
        $faculty     = Faculty::first();
        $sp          = StudyProgram::first();
        $gy          = GraduationYear::first();

        $otherAlumni = Alumni::factory()->create([
            'user_id'            => $otherUser->id,
            'study_program_id'   => $sp->id,
            'graduation_year_id' => $gy->id,
            'nim'                => '20210088',
        ]);

        $otherWh = AlumniWorkHistory::factory()->create([
            'alumni_id'    => $otherAlumni->id,
            'company_name' => 'PT Orang Lain',
            'position'     => 'Analyst',
            'start_date'   => '2023-01-01',
            'is_current'   => false,
        ]);

        $this->withToken($this->token)
            ->putJson("/api/v1/alumni/work-histories/{$otherAlumni->id}/{$otherWh->id}", [
                'position' => 'Hacked',
            ])
            ->assertForbidden();
    }

    /** @test */
    public function update_rejects_mismatched_alumni_work_history(): void
    {
        // workHistory milik alumni lain, tapi URL alumni-nya benar milik tester
        $otherUser   = User::factory()->create(['role' => 'alumni', 'is_active' => true]);
        $otherAlumni = Alumni::factory()->create([
            'user_id'            => $otherUser->id,
            'study_program_id'   => StudyProgram::first()->id,
            'graduation_year_id' => GraduationYear::first()->id,
            'nim'                => '20210077',
        ]);
        $otherWh = AlumniWorkHistory::factory()->create([
            'alumni_id'    => $otherAlumni->id,
            'company_name' => 'PT X',
            'position'     => 'PM',
            'start_date'   => '2023-01-01',
            'is_current'   => false,
        ]);

        // URL: alumni = $this->alumni, tapi workHistory milik otherAlumni → 403
        $this->withToken($this->token)
            ->putJson("/api/v1/alumni/work-histories/{$this->alumni->id}/{$otherWh->id}", [
                'position' => 'Hacked',
            ])
            ->assertForbidden();
    }

    // =========================================================================
    // DELETE /api/v1/alumni/work-histories/{alumni}/{workHistory}
    // =========================================================================

    /** @test */
    public function alumni_can_delete_own_work_history(): void
    {
        $this->withToken($this->token)
            ->deleteJson("/api/v1/alumni/work-histories/{$this->alumni->id}/{$this->workHistory->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('alumni_work_histories', [
            'id' => $this->workHistory->id,
        ]);
    }

    /** @test */
    public function alumni_cannot_delete_work_history_of_other_alumni(): void
    {
        $otherUser   = User::factory()->create(['role' => 'alumni', 'is_active' => true]);
        $otherAlumni = Alumni::factory()->create([
            'user_id'            => $otherUser->id,
            'study_program_id'   => StudyProgram::first()->id,
            'graduation_year_id' => GraduationYear::first()->id,
            'nim'                => '20210066',
        ]);
        $otherWh = AlumniWorkHistory::factory()->create([
            'alumni_id'    => $otherAlumni->id,
            'company_name' => 'PT Aman',
            'position'     => 'Designer',
            'start_date'   => '2023-01-01',
            'is_current'   => false,
        ]);

        $this->withToken($this->token)
            ->deleteJson("/api/v1/alumni/work-histories/{$otherAlumni->id}/{$otherWh->id}")
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_delete_any_alumni_work_history(): void
    {
        $adminUser  = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $adminToken = $adminUser->createToken('test')->plainTextToken;

        $this->withToken($adminToken)
            ->deleteJson("/api/v1/alumni/work-histories/{$this->alumni->id}/{$this->workHistory->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('alumni_work_histories', [
            'id' => $this->workHistory->id,
        ]);
    }

    /** @test */
    public function audit_log_is_created_on_store(): void
    {
        $this->withToken($this->token)
            ->postJson("/api/v1/alumni/work-histories/{$this->alumni->id}", [
                'company_name' => 'PT Audit Test',
                'position'     => 'QA',
                'start_date'   => '2024-01-01',
                'is_current'   => false,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'create_work_history',
            'module'     => 'alumni',
            'model_id'   => $this->alumni->id,
            'model_type' => Alumni::class,
        ]);
    }

    /** @test */
    public function audit_log_is_created_on_delete(): void
    {
        $this->withToken($this->token)
            ->deleteJson("/api/v1/alumni/work-histories/{$this->alumni->id}/{$this->workHistory->id}")
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action'   => 'delete_work_history',
            'module'   => 'alumni',
            'model_id' => $this->alumni->id,
        ]);
    }
}

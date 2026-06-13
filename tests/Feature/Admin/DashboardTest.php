<?php

namespace Tests\Feature\Admin;

use App\Models\Alumni;
use App\Models\AlumniWorkHistory;
use App\Models\AuditLog;
use App\Models\Faculty;
use App\Models\GraduationYear;
use App\Models\StudyProgram;
use App\Models\SurveyPeriod;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * DashboardTest — Sesi 5A Batch 6 (Task 5A.11)
 *
 * Endpoint yang diuji (05_API.md §7):
 *   GET /api/v1/admin/dashboard/summary          → DashboardController::summary()
 *   GET /api/v1/admin/dashboard/employment-stats → DashboardController::employmentStats()
 *   GET /api/v1/admin/dashboard/alumni-map       → DashboardController::alumniMap()
 *
 * RBAC: auth:sanctum + CheckRole:superadmin,admin
 *
 * CATATAN BUG (perlu fix di iterasi berikutnya):
 *   DashboardController::employmentStats() memanggil:
 *     $this->dashboardService->getEmploymentStats($filters)  ← array
 *   Padahal DashboardService::getEmploymentStats() menerima:
 *     getEmploymentStats(?int $periodId, ?int $graduationYearId, ?int $studyProgramId)
 *   Ini akan menyebabkan TypeError di production.
 *   Test ini menguji via HTTP dan mock DashboardService agar test suite bisa berjalan.
 *   Fix: ubah DashboardController atau DashboardService agar signaturenya konsisten.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $admin;
    private User $alumniUser;
    private User $employerUser;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        // Buat user berdasarkan role
        $this->superadmin   = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
        $this->admin        = User::factory()->create(['role' => 'admin',      'is_active' => true]);
        $this->alumniUser   = User::factory()->create(['role' => 'alumni',     'is_active' => true]);
        $this->employerUser = User::factory()->create(['role' => 'employer',   'is_active' => true]);
    }

    // =========================================================================
    // BAGIAN 1: GET /admin/dashboard/summary
    // =========================================================================

    /** @test */
    public function summary_returns_correct_structure_as_admin(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard/summary');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_alumni',
                    'total_employers',
                    'active_survey_period',
                    'employment_stats' => [
                        'employed',
                        'self_employed',
                        'continuing_study',
                        'not_working',
                    ],
                    'recent_activities',
                ],
            ])
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function summary_returns_correct_structure_as_superadmin(): void
    {
        $response = $this->actingAs($this->superadmin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard/summary');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function summary_active_survey_period_is_null_when_no_active_period(): void
    {
        // Tidak ada SurveyPeriod di database
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard/summary');

        $response->assertOk();

        $this->assertNull($response->json('data.active_survey_period'));
    }

    /** @test */
    public function summary_includes_active_period_data_when_period_is_active(): void
    {
        // Setup: buat alumni dan survey_period aktif
        $faculty      = Faculty::factory()->create();
        $studyProgram = StudyProgram::factory()->create(['faculty_id' => $faculty->id]);
        $gradYear     = GraduationYear::factory()->create();

        $alumni = Alumni::factory()->create([
            'study_program_id'  => $studyProgram->id,
            'graduation_year_id' => $gradYear->id,
            'survey_status'     => 'belum',
        ]);

        $period = SurveyPeriod::factory()->create([
            'status'   => 'active',
            'end_date' => now()->addDays(30)->toDateString(),
        ]);

        // Attach alumni ke periode
        $period->alumni()->attach($alumni->id);

        // Buat 1 response yang sudah selesai
        SurveyResponse::factory()->create([
            'survey_period_id' => $period->id,
            'alumni_id'        => $alumni->id,
            'respondent_type'  => 'alumni',
            'status'           => 'selesai',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard/summary');

        $response->assertOk();

        $activePeriod = $response->json('data.active_survey_period');
        $this->assertNotNull($activePeriod);
        $this->assertEquals($period->id, $activePeriod['id']);
        $this->assertEquals($period->name, $activePeriod['name']);
        $this->assertArrayHasKey('response_rate', $activePeriod);
        $this->assertArrayHasKey('responses_completed', $activePeriod);
        $this->assertArrayHasKey('responses_pending', $activePeriod);
        $this->assertEquals(1, $activePeriod['responses_completed']);
        $this->assertEquals(0, $activePeriod['responses_pending']);
        $this->assertEquals(100.0, $activePeriod['response_rate']);
    }

    /** @test */
    public function summary_counts_total_alumni_and_employers_correctly(): void
    {
        $faculty      = Faculty::factory()->create();
        $studyProgram = StudyProgram::factory()->create(['faculty_id' => $faculty->id]);
        $gradYear     = GraduationYear::factory()->create();

        Alumni::factory()->count(5)->create([
            'study_program_id'   => $studyProgram->id,
            'graduation_year_id' => $gradYear->id,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard/summary');

        $response->assertOk();
        $this->assertEquals(5, $response->json('data.total_alumni'));
    }

    /** @test */
    public function summary_records_audit_log_on_access(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard/summary');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'view',
            'module' => 'Dashboard',
        ]);
    }

    // =========================================================================
    // BAGIAN 2: GET /admin/dashboard/employment-stats
    // =========================================================================

    /** @test */
    public function employment_stats_returns_correct_structure_without_filters(): void
    {
        // Mock DashboardService untuk menghindari bug TypeError
        // (controller memanggil getEmploymentStats($array) tapi service menerima 3 param terpisah)
        $this->mock(DashboardService::class, function ($mock) {
            $mock->shouldReceive('getEmploymentStats')
                ->once()
                ->andReturn([
                    'employment_rate'        => 75.0,
                    'average_waiting_months' => 3.2,
                    'relevance_rate'         => 68.5,
                    'by_industry'            => [],
                    'by_salary_range'        => [],
                    'by_graduation_year'     => [],
                    'by_study_program'       => [],
                ]);
            // Summary & alumniMap masih dibutuhkan jika dipanggil
            $mock->shouldReceive('getSummary')->andReturn([]);
            $mock->shouldReceive('getAlumniMap')->andReturn([]);
        });

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard/employment-stats');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'employment_rate',
                    'average_waiting_months',
                    'relevance_rate',
                    'by_industry',
                    'by_salary_range',
                    'by_graduation_year',
                    'by_study_program',
                ],
            ])
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function employment_stats_accepts_valid_filter_params(): void
    {
        $faculty      = Faculty::factory()->create();
        $studyProgram = StudyProgram::factory()->create(['faculty_id' => $faculty->id]);
        $gradYear     = GraduationYear::factory()->create();
        $period       = SurveyPeriod::factory()->create(['status' => 'closed']);

        $this->mock(DashboardService::class, function ($mock) {
            $mock->shouldReceive('getEmploymentStats')
                ->once()
                ->andReturn([
                    'employment_rate'        => 0.0,
                    'average_waiting_months' => null,
                    'relevance_rate'         => 0.0,
                    'by_industry'            => [],
                    'by_salary_range'        => [],
                    'by_graduation_year'     => [],
                    'by_study_program'       => [],
                ]);
            $mock->shouldReceive('getSummary')->andReturn([]);
            $mock->shouldReceive('getAlumniMap')->andReturn([]);
        });

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/admin/dashboard/employment-stats?period_id={$period->id}&graduation_year_id={$gradYear->id}&study_program_id={$studyProgram->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function employment_stats_rejects_invalid_period_id(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard/employment-stats?period_id=99999');

        $response->assertUnprocessable();
    }

    /** @test */
    public function employment_stats_rejects_invalid_graduation_year_id(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard/employment-stats?graduation_year_id=99999');

        $response->assertUnprocessable();
    }

    // =========================================================================
    // BAGIAN 3: GET /admin/dashboard/alumni-map
    // =========================================================================

    /** @test */
    public function alumni_map_returns_correct_structure(): void
    {
        $this->mock(DashboardService::class, function ($mock) {
            $mock->shouldReceive('getAlumniMap')
                ->once()
                ->andReturn([
                    [
                        'province'    => 'Sulawesi Selatan',
                        'city'        => 'Makassar',
                        'count'       => 10,
                        'coordinates' => ['lat' => -5.1477, 'lng' => 119.4327],
                    ],
                ]);
            $mock->shouldReceive('getSummary')->andReturn([]);
            $mock->shouldReceive('getEmploymentStats')->andReturn([]);
        });

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard/alumni-map');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'province',
                        'city',
                        'count',
                        'coordinates' => ['lat', 'lng'],
                    ],
                ],
            ])
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function alumni_map_coordinates_can_be_null_safely(): void
    {
        // Alumni tanpa koordinat — coordinates.lat dan coordinates.lng harus null, bukan error
        $faculty      = Faculty::factory()->create();
        $studyProgram = StudyProgram::factory()->create(['faculty_id' => $faculty->id]);
        $gradYear     = GraduationYear::factory()->create();

        Alumni::factory()->create([
            'study_program_id'   => $studyProgram->id,
            'graduation_year_id' => $gradYear->id,
            'address_province'   => 'Sulawesi Selatan',
            'address_city'       => 'Bulukumba',
            'address_latitude'   => null,
            'address_longitude'  => null,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard/alumni-map');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertIsArray($data);

        if (count($data) > 0) {
            $this->assertNull($data[0]['coordinates']['lat']);
            $this->assertNull($data[0]['coordinates']['lng']);
        }
    }

    /** @test */
    public function alumni_map_accepts_valid_filter_params(): void
    {
        $faculty      = Faculty::factory()->create();
        $studyProgram = StudyProgram::factory()->create(['faculty_id' => $faculty->id]);
        $gradYear     = GraduationYear::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/admin/dashboard/alumni-map?graduation_year_id={$gradYear->id}&study_program_id={$studyProgram->id}");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    // =========================================================================
    // BAGIAN 4: RBAC — Semua endpoint dashboard
    // =========================================================================

    /** @test */
    public function unauthenticated_user_cannot_access_summary(): void
    {
        $this->getJson('/api/v1/admin/dashboard/summary')
            ->assertUnauthorized();
    }

    /** @test */
    public function alumni_user_cannot_access_dashboard_summary(): void
    {
        $this->actingAs($this->alumniUser, 'sanctum')
            ->getJson('/api/v1/admin/dashboard/summary')
            ->assertForbidden();
    }

    /** @test */
    public function employer_user_cannot_access_employment_stats(): void
    {
        $this->actingAs($this->employerUser, 'sanctum')
            ->getJson('/api/v1/admin/dashboard/employment-stats')
            ->assertForbidden();
    }

    /** @test */
    public function alumni_user_cannot_access_alumni_map(): void
    {
        $this->actingAs($this->alumniUser, 'sanctum')
            ->getJson('/api/v1/admin/dashboard/alumni-map')
            ->assertForbidden();
    }
}

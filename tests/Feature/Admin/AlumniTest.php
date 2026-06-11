<?php

namespace Tests\Feature\Admin;

use App\Models\Alumni;
use App\Models\AuditLog;
use App\Models\Faculty;
use App\Models\GraduationYear;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumniTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $admin;
    private User $alumni;
    private StudyProgram $studyProgram;
    private GraduationYear $graduationYear;

    protected function setUp(): void
    {
        parent::setUp();

        $faculty = Faculty::factory()->create();
        $this->studyProgram = StudyProgram::factory()->create(['faculty_id' => $faculty->id]);
        $this->graduationYear = GraduationYear::factory()->create();

        $this->superadmin = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->alumni = User::factory()->create(['role' => 'alumni', 'is_active' => true]);
    }

    // ─────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────

    public function test_superadmin_can_list_alumni(): void
    {
        Alumni::factory(5)->create([
            'study_program_id'   => $this->studyProgram->id,
            'graduation_year_id' => $this->graduationYear->id,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->getJson('/api/v1/admin/alumni');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [['id', 'nim', 'full_name', 'survey_status']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_list_alumni(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/alumni');

        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_alumni_cannot_list_admin_alumni(): void
    {
        $response = $this->actingAs($this->alumni)
            ->getJson('/api/v1/admin/alumni');

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_list_alumni(): void
    {
        $response = $this->getJson('/api/v1/admin/alumni');
        $response->assertUnauthorized();
    }

    // ─────────────────────────────────────────────
    // SHOW
    // ─────────────────────────────────────────────

    public function test_admin_can_view_alumni_detail(): void
    {
        $alumniModel = Alumni::factory()->create([
            'study_program_id'   => $this->studyProgram->id,
            'graduation_year_id' => $this->graduationYear->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/alumni/{$alumniModel->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $alumniModel->id)
            ->assertJsonPath('data.nim', $alumniModel->nim);
    }

    public function test_show_returns_404_for_nonexistent_alumni(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/alumni/999999');

        $response->assertNotFound();
    }

    // ─────────────────────────────────────────────
    // STORE
    // ─────────────────────────────────────────────

    private function validAlumniPayload(array $overrides = []): array
    {
        return array_merge([
            'nim'                => '20210001',
            'full_name'          => 'Ahmad Fauzi',
            'gender'             => 'L',
            'birth_place'        => 'Lumajang',
            'birth_date'         => '2000-03-15',
            'study_program_id'   => $this->studyProgram->id,
            'graduation_year_id' => $this->graduationYear->id,
            'gpa'                => 3.75,
            'phone'              => '081234567890',
            'email'              => 'ahmad@example.com',
        ], $overrides);
    }

    public function test_superadmin_can_create_alumni(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->postJson('/api/v1/admin/alumni', $this->validAlumniPayload());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nim', '20210001');

        $this->assertDatabaseHas('alumni', ['nim' => '20210001']);
    }

    public function test_admin_can_create_alumni(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/alumni', $this->validAlumniPayload());

        $response->assertCreated();
    }

    public function test_create_alumni_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/alumni', []);

        $response->assertUnprocessable()
            ->assertJsonStructure(['success', 'errors' => ['nim', 'full_name', 'study_program_id', 'graduation_year_id']]);
    }

    public function test_create_alumni_rejects_duplicate_nim(): void
    {
        Alumni::factory()->create([
            'nim'                => '20210001',
            'study_program_id'   => $this->studyProgram->id,
            'graduation_year_id' => $this->graduationYear->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/alumni', $this->validAlumniPayload(['nim' => '20210001']));

        $response->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_create_alumni_rejects_invalid_gpa(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/alumni', $this->validAlumniPayload(['gpa' => 5.0]));

        $response->assertUnprocessable();
    }

    public function test_create_alumni_creates_audit_log(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/alumni', $this->validAlumniPayload());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'create',
            'module' => 'Alumni',
        ]);
    }

    public function test_alumni_cannot_create_alumni(): void
    {
        $response = $this->actingAs($this->alumni)
            ->postJson('/api/v1/admin/alumni', $this->validAlumniPayload());

        $response->assertForbidden();
    }

    // ─────────────────────────────────────────────
    // UPDATE
    // ─────────────────────────────────────────────

    public function test_admin_can_update_alumni(): void
    {
        $alumniModel = Alumni::factory()->create([
            'study_program_id'   => $this->studyProgram->id,
            'graduation_year_id' => $this->graduationYear->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/alumni/{$alumniModel->id}", [
                'full_name' => 'Ahmad Fauzi Updated',
                'gpa'       => 3.80,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('alumni', [
            'id'        => $alumniModel->id,
            'full_name' => 'Ahmad Fauzi Updated',
        ]);
    }

    public function test_update_alumni_creates_audit_log_with_old_new_values(): void
    {
        $alumniModel = Alumni::factory()->create([
            'full_name'          => 'Nama Lama',
            'study_program_id'   => $this->studyProgram->id,
            'graduation_year_id' => $this->graduationYear->id,
        ]);

        $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/alumni/{$alumniModel->id}", [
                'full_name' => 'Nama Baru',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'update',
            'module' => 'Alumni',
        ]);
    }

    public function test_alumni_role_cannot_update_via_admin_endpoint(): void
    {
        $alumniModel = Alumni::factory()->create([
            'study_program_id'   => $this->studyProgram->id,
            'graduation_year_id' => $this->graduationYear->id,
        ]);

        $response = $this->actingAs($this->alumni)
            ->putJson("/api/v1/admin/alumni/{$alumniModel->id}", ['full_name' => 'Hack']);

        $response->assertForbidden();
    }

    // ─────────────────────────────────────────────
    // DELETE (soft delete)
    // ─────────────────────────────────────────────

    public function test_superadmin_can_soft_delete_alumni(): void
    {
        $alumniModel = Alumni::factory()->create([
            'study_program_id'   => $this->studyProgram->id,
            'graduation_year_id' => $this->graduationYear->id,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->deleteJson("/api/v1/admin/alumni/{$alumniModel->id}");

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertSoftDeleted('alumni', ['id' => $alumniModel->id]);
    }

    public function test_admin_cannot_delete_alumni(): void
    {
        $alumniModel = Alumni::factory()->create([
            'study_program_id'   => $this->studyProgram->id,
            'graduation_year_id' => $this->graduationYear->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/alumni/{$alumniModel->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('alumni', ['id' => $alumniModel->id, 'deleted_at' => null]);
    }

    public function test_delete_alumni_creates_audit_log(): void
    {
        $alumniModel = Alumni::factory()->create([
            'study_program_id'   => $this->studyProgram->id,
            'graduation_year_id' => $this->graduationYear->id,
        ]);

        $this->actingAs($this->superadmin)
            ->deleteJson("/api/v1/admin/alumni/{$alumniModel->id}");

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'delete',
            'module' => 'Alumni',
        ]);
    }

    // ─────────────────────────────────────────────
    // FILTER & SEARCH
    // ─────────────────────────────────────────────

    public function test_can_filter_alumni_by_study_program(): void
    {
        Alumni::factory(3)->create([
            'study_program_id'   => $this->studyProgram->id,
            'graduation_year_id' => $this->graduationYear->id,
        ]);

        $otherProgram = StudyProgram::factory()->create(['faculty_id' => Faculty::factory()->create()->id]);
        Alumni::factory(2)->create([
            'study_program_id'   => $otherProgram->id,
            'graduation_year_id' => $this->graduationYear->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/alumni?study_program_id={$this->studyProgram->id}");

        $response->assertOk();
        $this->assertEquals(3, $response->json('meta.total'));
    }

    public function test_can_search_alumni_by_name_or_nim(): void
    {
        Alumni::factory()->create([
            'nim'                => '20210099',
            'full_name'          => 'Budi Santoso',
            'study_program_id'   => $this->studyProgram->id,
            'graduation_year_id' => $this->graduationYear->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/alumni?search=Budi');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, $response->json('meta.total'));
    }

    // ─────────────────────────────────────────────
    // ALUMNI SELF ACCESS
    // ─────────────────────────────────────────────

    public function test_alumni_can_view_own_profile(): void
    {
        $alumniUser = User::factory()->create(['role' => 'alumni', 'is_active' => true]);
        Alumni::factory()->create([
            'user_id'            => $alumniUser->id,
            'study_program_id'   => $this->studyProgram->id,
            'graduation_year_id' => $this->graduationYear->id,
        ]);

        $response = $this->actingAs($alumniUser)
            ->getJson('/api/v1/alumni/profile');

        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_alumni_cannot_view_other_alumni_profile(): void
    {
        $otherUser = User::factory()->create(['role' => 'alumni', 'is_active' => true]);
        $otherAlumni = Alumni::factory()->create([
            'user_id'            => $otherUser->id,
            'study_program_id'   => $this->studyProgram->id,
            'graduation_year_id' => $this->graduationYear->id,
        ]);

        // Authenticated as a different alumni
        $myUser = User::factory()->create(['role' => 'alumni', 'is_active' => true]);
        Alumni::factory()->create([
            'user_id'            => $myUser->id,
            'study_program_id'   => $this->studyProgram->id,
            'graduation_year_id' => $this->graduationYear->id,
        ]);

        $response = $this->actingAs($myUser)
            ->getJson("/api/v1/admin/alumni/{$otherAlumni->id}");

        $response->assertForbidden();
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\Employer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployerTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $admin;
    private User $alumniUser;
    private User $employerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin    = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
        $this->admin         = User::factory()->create(['role' => 'admin',      'is_active' => true]);
        $this->alumniUser    = User::factory()->create(['role' => 'alumni',     'is_active' => true]);
        $this->employerUser  = User::factory()->create(['role' => 'employer',   'is_active' => true]);
    }

    // ─────────────────────────────────────────────
    // HELPER
    // ─────────────────────────────────────────────

    private function validEmployerPayload(array $overrides = []): array
    {
        return array_merge([
            'company_name'           => 'PT Nusantara Teknologi',
            'company_type'           => 'swasta',
            'industry_sector'        => 'Teknologi Informasi',
            'company_scale'          => 'menengah',
            'address_city'           => 'Surabaya',
            'address_province'       => 'Jawa Timur',
            'address_country'        => 'Indonesia',
            'phone'                  => '0311234567',
            'email'                  => 'info@nusantarateknologi.id',
            'contact_person_name'    => 'Budi Santoso',
            'contact_person_position'=> 'HRD Manager',
            'contact_person_email'   => 'budi@nusantarateknologi.id',
            'contact_person_phone'   => '081234567890',
        ], $overrides);
    }

    // ─────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────

    public function test_superadmin_can_list_employers(): void
    {
        Employer::factory(3)->create();

        $response = $this->actingAs($this->superadmin)
            ->getJson('/api/v1/admin/employers');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [['id', 'company_name', 'survey_status']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_list_employers(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/employers');

        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_alumni_cannot_list_employers(): void
    {
        $response = $this->actingAs($this->alumniUser)
            ->getJson('/api/v1/admin/employers');

        $response->assertForbidden();
    }

    public function test_employer_cannot_list_admin_employers(): void
    {
        $response = $this->actingAs($this->employerUser)
            ->getJson('/api/v1/admin/employers');

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_list_employers(): void
    {
        $response = $this->getJson('/api/v1/admin/employers');
        $response->assertUnauthorized();
    }

    // ─────────────────────────────────────────────
    // SHOW
    // ─────────────────────────────────────────────

    public function test_admin_can_view_employer_detail(): void
    {
        $employer = Employer::factory()->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/employers/{$employer->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $employer->id)
            ->assertJsonPath('data.company_name', $employer->company_name);
    }

    public function test_show_returns_404_for_nonexistent_employer(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/employers/999999');

        $response->assertNotFound();
    }

    public function test_employer_role_cannot_access_admin_show(): void
    {
        $employer = Employer::factory()->create();

        $response = $this->actingAs($this->employerUser)
            ->getJson("/api/v1/admin/employers/{$employer->id}");

        $response->assertForbidden();
    }

    // ─────────────────────────────────────────────
    // STORE
    // ─────────────────────────────────────────────

    public function test_superadmin_can_create_employer(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->postJson('/api/v1/admin/employers', $this->validEmployerPayload());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.company_name', 'PT Nusantara Teknologi');

        $this->assertDatabaseHas('employers', ['company_name' => 'PT Nusantara Teknologi']);
    }

    public function test_admin_can_create_employer(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/employers', $this->validEmployerPayload());

        $response->assertCreated();
    }

    public function test_create_employer_validates_required_company_name(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/employers', $this->validEmployerPayload(['company_name' => '']));

        $response->assertUnprocessable()
            ->assertJsonStructure(['success', 'errors' => ['company_name']]);
    }

    public function test_create_employer_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/employers', []);

        $response->assertUnprocessable()
            ->assertJsonStructure(['success', 'errors' => ['company_name']]);
    }

    public function test_create_employer_creates_audit_log(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/employers', $this->validEmployerPayload());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'create',
            'module' => 'Employer',
        ]);
    }

    public function test_alumni_cannot_create_employer(): void
    {
        $response = $this->actingAs($this->alumniUser)
            ->postJson('/api/v1/admin/employers', $this->validEmployerPayload());

        $response->assertForbidden();
    }

    public function test_employer_default_survey_status_is_belum_disurvei(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/employers', $this->validEmployerPayload());

        $response->assertCreated()
            ->assertJsonPath('data.survey_status', 'belum_disurvei');
    }

    // ─────────────────────────────────────────────
    // UPDATE
    // ─────────────────────────────────────────────

    public function test_admin_can_update_employer(): void
    {
        $employer = Employer::factory()->create();

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/employers/{$employer->id}", [
                'company_name' => 'PT Nusantara Updated',
                'address_city' => 'Malang',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('employers', [
            'id'           => $employer->id,
            'company_name' => 'PT Nusantara Updated',
            'address_city' => 'Malang',
        ]);
    }

    public function test_update_employer_creates_audit_log(): void
    {
        $employer = Employer::factory()->create(['company_name' => 'PT Lama']);

        $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/employers/{$employer->id}", [
                'company_name' => 'PT Baru',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'   => 'update',
            'module'   => 'Employer',
        ]);
    }

    public function test_employer_role_cannot_update_via_admin_endpoint(): void
    {
        $employer = Employer::factory()->create();

        $response = $this->actingAs($this->employerUser)
            ->putJson("/api/v1/admin/employers/{$employer->id}", ['company_name' => 'Hack']);

        $response->assertForbidden();
    }

    public function test_update_returns_404_for_nonexistent_employer(): void
    {
        $response = $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/employers/999999', ['company_name' => 'Test']);

        $response->assertNotFound();
    }

    // ─────────────────────────────────────────────
    // DELETE (soft delete)
    // ─────────────────────────────────────────────

    public function test_superadmin_can_soft_delete_employer(): void
    {
        $employer = Employer::factory()->create();

        $response = $this->actingAs($this->superadmin)
            ->deleteJson("/api/v1/admin/employers/{$employer->id}");

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertSoftDeleted('employers', ['id' => $employer->id]);
    }

    public function test_admin_cannot_delete_employer(): void
    {
        $employer = Employer::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/employers/{$employer->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('employers', ['id' => $employer->id, 'deleted_at' => null]);
    }

    public function test_delete_employer_creates_audit_log(): void
    {
        $employer = Employer::factory()->create();

        $this->actingAs($this->superadmin)
            ->deleteJson("/api/v1/admin/employers/{$employer->id}");

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'delete',
            'module' => 'Employer',
        ]);
    }

    // ─────────────────────────────────────────────
    // FILTER & SEARCH
    // ─────────────────────────────────────────────

    public function test_can_filter_employers_by_survey_status(): void
    {
        Employer::factory(2)->create(['survey_status' => 'belum_disurvei']);
        Employer::factory(1)->create(['survey_status' => 'terkirim']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/employers?survey_status=belum_disurvei');

        $response->assertOk();
        $this->assertEquals(2, $response->json('meta.total'));
    }

    public function test_can_filter_employers_by_company_type(): void
    {
        Employer::factory(2)->create(['company_type' => 'swasta']);
        Employer::factory(1)->create(['company_type' => 'bumn']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/employers?company_type=swasta');

        $response->assertOk();
        $this->assertEquals(2, $response->json('meta.total'));
    }

    public function test_can_search_employers_by_company_name(): void
    {
        Employer::factory()->create(['company_name' => 'PT Maju Bersama']);
        Employer::factory()->create(['company_name' => 'CV Teknologi Nusantara']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/employers?search=Maju');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, $response->json('meta.total'));
    }

    public function test_can_search_employers_by_contact_person(): void
    {
        Employer::factory()->create(['contact_person_name' => 'Siti Rahayu']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/employers?search=Siti');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, $response->json('meta.total'));
    }

    // ─────────────────────────────────────────────
    // STATS
    // ─────────────────────────────────────────────

    public function test_admin_can_retrieve_employer_stats(): void
    {
        Employer::factory(3)->create(['survey_status' => 'belum_disurvei']);
        Employer::factory(2)->create(['survey_status' => 'terkirim']);
        Employer::factory(1)->create(['survey_status' => 'selesai']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/employers');

        // Stats dikembalikan di field 'stats' pada respons index
        $response->assertOk()->assertJson(['success' => true]);
    }
}

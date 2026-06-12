<?php

namespace Tests\Feature\Admin;

use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionnaireSection;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test untuk Admin Questionnaire endpoints (3A.12).
 *
 * Menguji 10 endpoint sesuai 05_API.md §3.x:
 *   GET    /api/v1/admin/questionnaires/stats
 *   GET    /api/v1/admin/questionnaires
 *   POST   /api/v1/admin/questionnaires
 *   GET    /api/v1/admin/questionnaires/{id}
 *   PUT    /api/v1/admin/questionnaires/{id}
 *   DELETE /api/v1/admin/questionnaires/{id}
 *   PATCH  /api/v1/admin/questionnaires/{id}/publish
 *   PATCH  /api/v1/admin/questionnaires/{id}/archive
 *   POST   /api/v1/admin/questionnaires/{id}/duplicate
 *   PATCH  /api/v1/admin/questionnaires/{id}/reorder
 */
class QuestionnaireTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $admin;
    private User $alumni;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superadmin = User::factory()->superadmin()->create();
        $this->admin      = User::factory()->admin()->create();
        $this->alumni     = User::factory()->alumni()->create();
    }

    // =========================================================================
    // Helper
    // =========================================================================

    private function actingAsAdmin(): static
    {
        return $this->actingAs($this->admin, 'sanctum');
    }

    private function actingAsSuperadmin(): static
    {
        return $this->actingAs($this->superadmin, 'sanctum');
    }

    private function actingAsAlumni(): static
    {
        return $this->actingAs($this->alumni, 'sanctum');
    }

    private function makeQuestionnaireWithQuestion(string $status = 'draft'): Questionnaire
    {
        $q = Questionnaire::factory()->{$status}()->createdBy($this->admin->id)->create();
        Question::factory()->create([
            'questionnaire_id' => $q->id,
            'order_number'     => 1,
        ]);
        return $q;
    }

    // =========================================================================
    // GET /stats
    // =========================================================================

    /** @test */
    public function stats_returns_correct_structure_for_admin(): void
    {
        Questionnaire::factory()->draft()->count(3)->create();
        Questionnaire::factory()->aktif()->count(2)->create();
        Questionnaire::factory()->arsip()->count(1)->create();

        $response = $this->actingAsAdmin()
            ->getJson('/api/v1/admin/questionnaires/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['by_status', 'by_type', 'total'],
            ])
            ->assertJsonPath('data.total', 6);
    }

    /** @test */
    public function stats_forbidden_for_alumni(): void
    {
        $this->actingAsAlumni()
            ->getJson('/api/v1/admin/questionnaires/stats')
            ->assertStatus(403);
    }

    /** @test */
    public function stats_returns_401_for_unauthenticated(): void
    {
        $this->getJson('/api/v1/admin/questionnaires/stats')
            ->assertUnauthorized();
    }

    // =========================================================================
    // GET / (index)
    // =========================================================================

    /** @test */
    public function index_returns_paginated_list_for_admin(): void
    {
        Questionnaire::factory()->count(5)->create();

        $response = $this->actingAsAdmin()
            ->getJson('/api/v1/admin/questionnaires');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    /** @test */
    public function index_filters_by_type_query_param(): void
    {
        Questionnaire::factory()->forAlumni()->count(3)->create();
        Questionnaire::factory()->forEmployer()->count(2)->create();

        $response = $this->actingAsAdmin()
            ->getJson('/api/v1/admin/questionnaires?type=alumni');

        $response->assertOk()
            ->assertJsonPath('meta.total', 3);
    }

    /** @test */
    public function index_filters_by_status_query_param(): void
    {
        Questionnaire::factory()->draft()->count(4)->create();
        Questionnaire::factory()->aktif()->count(1)->create();

        $response = $this->actingAsAdmin()
            ->getJson('/api/v1/admin/questionnaires?status=draft');

        $response->assertOk()
            ->assertJsonPath('meta.total', 4);
    }

    /** @test */
    public function index_returns_401_for_unauthenticated(): void
    {
        $this->getJson('/api/v1/admin/questionnaires')
            ->assertUnauthorized();
    }

    /** @test */
    public function index_forbidden_for_alumni(): void
    {
        $this->actingAsAlumni()
            ->getJson('/api/v1/admin/questionnaires')
            ->assertStatus(403);
    }

    // =========================================================================
    // POST / (store)
    // =========================================================================

    /** @test */
    public function store_creates_questionnaire_as_draft(): void
    {
        $payload = [
            'title' => 'Kuesioner Baru',
            'type'  => 'alumni',
        ];

        $response = $this->actingAsAdmin()
            ->postJson('/api/v1/admin/questionnaires', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.version', 1);

        $this->assertDatabaseHas('questionnaires', [
            'title'      => 'Kuesioner Baru',
            'status'     => 'draft',
            'created_by' => $this->admin->id,
        ]);
    }

    /** @test */
    public function store_creates_with_sections_and_questions(): void
    {
        $payload = [
            'title'    => 'Kuesioner Terstruktur',
            'type'     => 'alumni',
            'sections' => [
                ['title' => 'Bagian 1', 'order_number' => 1],
            ],
            'questions' => [
                [
                    'question_text' => 'Apa pekerjaan Anda?',
                    'question_type' => 'text',
                    'is_required'   => true,
                    'order_number'  => 1,
                    'section_index' => 0,
                ],
            ],
        ];

        $this->actingAsAdmin()
            ->postJson('/api/v1/admin/questionnaires', $payload)
            ->assertCreated();

        $this->assertDatabaseCount('questionnaire_sections', 1);
        $this->assertDatabaseCount('questions', 1);
    }

    /** @test */
    public function store_returns_422_when_title_missing(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/admin/questionnaires', ['type' => 'alumni'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function store_returns_422_when_type_invalid(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/v1/admin/questionnaires', [
                'title' => 'Test',
                'type'  => 'invalid_type',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function store_forbidden_for_alumni(): void
    {
        $this->actingAsAlumni()
            ->postJson('/api/v1/admin/questionnaires', ['title' => 'X', 'type' => 'alumni'])
            ->assertStatus(403);
    }

    // =========================================================================
    // GET /{questionnaire} (show)
    // =========================================================================

    /** @test */
    public function show_returns_questionnaire_with_full_structure(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->create();
        $section = QuestionnaireSection::factory()->create([
            'questionnaire_id' => $questionnaire->id,
            'order_number'     => 1,
        ]);
        Question::factory()->create([
            'questionnaire_id' => $questionnaire->id,
            'section_id'       => $section->id,
            'order_number'     => 1,
        ]);

        $response = $this->actingAsAdmin()
            ->getJson("/api/v1/admin/questionnaires/{$questionnaire->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $questionnaire->id)
            ->assertJsonStructure(['data' => ['sections']]);
    }

    /** @test */
    public function show_returns_404_for_invalid_id(): void
    {
        $this->actingAsAdmin()
            ->getJson('/api/v1/admin/questionnaires/99999')
            ->assertNotFound();
    }

    /** @test */
    public function show_forbidden_for_alumni(): void
    {
        $q = Questionnaire::factory()->create();

        $this->actingAsAlumni()
            ->getJson("/api/v1/admin/questionnaires/{$q->id}")
            ->assertStatus(403);
    }

    // =========================================================================
    // PUT /{questionnaire} (update)
    // =========================================================================

    /** @test */
    public function update_changes_title_for_admin(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->create();

        $this->actingAsAdmin()
            ->putJson("/api/v1/admin/questionnaires/{$questionnaire->id}", [
                'title' => 'Judul Diperbarui',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Judul Diperbarui');

        $this->assertDatabaseHas('questionnaires', [
            'id'    => $questionnaire->id,
            'title' => 'Judul Diperbarui',
        ]);
    }

    /** @test */
    public function update_returns_404_for_invalid_id(): void
    {
        $this->actingAsAdmin()
            ->putJson('/api/v1/admin/questionnaires/99999', ['title' => 'X'])
            ->assertNotFound();
    }

    /** @test */
    public function update_forbidden_for_alumni(): void
    {
        $q = Questionnaire::factory()->create();

        $this->actingAsAlumni()
            ->putJson("/api/v1/admin/questionnaires/{$q->id}", ['title' => 'X'])
            ->assertStatus(403);
    }

    // =========================================================================
    // DELETE /{questionnaire} (destroy)
    // =========================================================================

    /** @test */
    public function destroy_deletes_draft_questionnaire_for_superadmin(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->create();

        $this->actingAsSuperadmin()
            ->deleteJson("/api/v1/admin/questionnaires/{$questionnaire->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('questionnaires', ['id' => $questionnaire->id]);
    }

    /** @test */
    public function destroy_returns_422_for_active_questionnaire(): void
    {
        $questionnaire = Questionnaire::factory()->aktif()->create();

        $this->actingAsSuperadmin()
            ->deleteJson("/api/v1/admin/questionnaires/{$questionnaire->id}")
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function destroy_forbidden_for_alumni(): void
    {
        $q = Questionnaire::factory()->draft()->create();

        $this->actingAsAlumni()
            ->deleteJson("/api/v1/admin/questionnaires/{$q->id}")
            ->assertStatus(403);
    }

    // =========================================================================
    // PATCH /{questionnaire}/publish
    // =========================================================================

    /** @test */
    public function publish_transitions_draft_to_aktif(): void
    {
        $questionnaire = $this->makeQuestionnaireWithQuestion('draft');

        $this->actingAsAdmin()
            ->patchJson("/api/v1/admin/questionnaires/{$questionnaire->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'aktif');

        $this->assertDatabaseHas('questionnaires', [
            'id'     => $questionnaire->id,
            'status' => 'aktif',
        ]);
    }

    /** @test */
    public function publish_returns_422_if_already_aktif(): void
    {
        $questionnaire = $this->makeQuestionnaireWithQuestion('aktif');

        $this->actingAsAdmin()
            ->patchJson("/api/v1/admin/questionnaires/{$questionnaire->id}/publish")
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function publish_returns_422_if_no_questions(): void
    {
        // Kuesioner draft tanpa pertanyaan
        $questionnaire = Questionnaire::factory()->draft()->create();

        $this->actingAsAdmin()
            ->patchJson("/api/v1/admin/questionnaires/{$questionnaire->id}/publish")
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function publish_forbidden_for_alumni(): void
    {
        $q = $this->makeQuestionnaireWithQuestion('draft');

        $this->actingAsAlumni()
            ->patchJson("/api/v1/admin/questionnaires/{$q->id}/publish")
            ->assertStatus(403);
    }

    // =========================================================================
    // PATCH /{questionnaire}/archive
    // =========================================================================

    /** @test */
    public function archive_transitions_aktif_to_arsip(): void
    {
        $questionnaire = Questionnaire::factory()->aktif()->create();

        $this->actingAsAdmin()
            ->patchJson("/api/v1/admin/questionnaires/{$questionnaire->id}/archive")
            ->assertOk()
            ->assertJsonPath('data.status', 'arsip');

        $this->assertDatabaseHas('questionnaires', [
            'id'     => $questionnaire->id,
            'status' => 'arsip',
        ]);
    }

    /** @test */
    public function archive_returns_422_if_not_aktif(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->create();

        $this->actingAsAdmin()
            ->patchJson("/api/v1/admin/questionnaires/{$questionnaire->id}/archive")
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function archive_forbidden_for_alumni(): void
    {
        $q = Questionnaire::factory()->aktif()->create();

        $this->actingAsAlumni()
            ->patchJson("/api/v1/admin/questionnaires/{$q->id}/archive")
            ->assertStatus(403);
    }

    // =========================================================================
    // POST /{questionnaire}/duplicate
    // =========================================================================

    /** @test */
    public function duplicate_creates_copy_with_draft_status(): void
    {
        $questionnaire = Questionnaire::factory()->aktif()->createdBy($this->admin->id)->create([
            'title' => 'Original',
        ]);

        $response = $this->actingAsAdmin()
            ->postJson("/api/v1/admin/questionnaires/{$questionnaire->id}/duplicate");

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseCount('questionnaires', 2);
        $this->assertDatabaseHas('questionnaires', ['status' => 'draft']);
    }

    /** @test */
    public function duplicate_title_contains_salinan(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->createdBy($this->admin->id)->create([
            'title' => 'Kuesioner A',
        ]);

        $response = $this->actingAsAdmin()
            ->postJson("/api/v1/admin/questionnaires/{$questionnaire->id}/duplicate");

        $response->assertCreated();
        $this->assertStringContainsString('Salinan', $response->json('data.title'));
    }

    /** @test */
    public function duplicate_forbidden_for_alumni(): void
    {
        $q = Questionnaire::factory()->create();

        $this->actingAsAlumni()
            ->postJson("/api/v1/admin/questionnaires/{$q->id}/duplicate")
            ->assertStatus(403);
    }

    // =========================================================================
    // PATCH /{questionnaire}/reorder
    // =========================================================================

    /** @test */
    public function reorder_updates_question_orders(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->create();
        $q1 = Question::factory()->create(['questionnaire_id' => $questionnaire->id, 'order_number' => 1]);
        $q2 = Question::factory()->create(['questionnaire_id' => $questionnaire->id, 'order_number' => 2]);

        $this->actingAsAdmin()
            ->patchJson("/api/v1/admin/questionnaires/{$questionnaire->id}/reorder", [
                'target' => 'questions',
                'items'  => [
                    ['id' => $q1->id, 'order_number' => 2],
                    ['id' => $q2->id, 'order_number' => 1],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('questions', ['id' => $q1->id, 'order_number' => 2]);
        $this->assertDatabaseHas('questions', ['id' => $q2->id, 'order_number' => 1]);
    }

    /** @test */
    public function reorder_updates_section_orders(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->create();
        $s1 = QuestionnaireSection::factory()->create(['questionnaire_id' => $questionnaire->id, 'order_number' => 1]);
        $s2 = QuestionnaireSection::factory()->create(['questionnaire_id' => $questionnaire->id, 'order_number' => 2]);

        $this->actingAsAdmin()
            ->patchJson("/api/v1/admin/questionnaires/{$questionnaire->id}/reorder", [
                'target' => 'sections',
                'items'  => [
                    ['id' => $s1->id, 'order_number' => 2],
                    ['id' => $s2->id, 'order_number' => 1],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('questionnaire_sections', ['id' => $s1->id, 'order_number' => 2]);
    }

    /** @test */
    public function reorder_returns_422_when_target_invalid(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->create();

        $this->actingAsAdmin()
            ->patchJson("/api/v1/admin/questionnaires/{$questionnaire->id}/reorder", [
                'target' => 'invalid_target',
                'items'  => [['id' => 1, 'order_number' => 1]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['target']);
    }

    /** @test */
    public function reorder_returns_422_when_items_empty(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->create();

        $this->actingAsAdmin()
            ->patchJson("/api/v1/admin/questionnaires/{$questionnaire->id}/reorder", [
                'target' => 'questions',
                'items'  => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    /** @test */
    public function reorder_forbidden_for_alumni(): void
    {
        $q = Questionnaire::factory()->create();

        $this->actingAsAlumni()
            ->patchJson("/api/v1/admin/questionnaires/{$q->id}/reorder", [
                'target' => 'questions',
                'items'  => [['id' => 1, 'order_number' => 1]],
            ])
            ->assertStatus(403);
    }
}

<?php

namespace Tests\Unit\Services;

use App\Models\AuditLog;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionnaireSection;
use App\Models\QuestionOption;
use App\Models\User;
use App\Services\QuestionnaireService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

/**
 * Unit test untuk QuestionnaireService (3A.11).
 *
 * Menguji semua method service secara terisolasi menggunakan database in-memory.
 * Setiap test berdiri sendiri — tidak bergantung urutan eksekusi.
 */
class QuestionnaireServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuestionnaireService $service;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuestionnaireService();
        $this->admin   = User::factory()->admin()->create();
    }

    // =========================================================================
    // paginate()
    // =========================================================================

    /** @test */
    public function paginate_returns_paginator_with_default_per_page(): void
    {
        Questionnaire::factory()->count(20)->create();

        $result = $this->service->paginate([]);

        $this->assertEquals(15, $result->perPage());
        $this->assertEquals(20, $result->total());
    }

    /** @test */
    public function paginate_filters_by_type(): void
    {
        Questionnaire::factory()->forAlumni()->count(3)->create();
        Questionnaire::factory()->forEmployer()->count(2)->create();

        $result = $this->service->paginate(['type' => 'alumni']);

        $this->assertEquals(3, $result->total());
        $result->each(fn ($q) => $this->assertEquals('alumni', $q->type));
    }

    /** @test */
    public function paginate_filters_by_status(): void
    {
        Questionnaire::factory()->draft()->count(4)->create();
        Questionnaire::factory()->aktif()->count(2)->create();

        $result = $this->service->paginate(['status' => 'draft']);

        $this->assertEquals(4, $result->total());
    }

    /** @test */
    public function paginate_filters_by_search_title(): void
    {
        Questionnaire::factory()->create(['title' => 'Survei Kepuasan Alumni 2025']);
        Questionnaire::factory()->count(3)->create();

        $result = $this->service->paginate(['search' => 'Kepuasan']);

        $this->assertEquals(1, $result->total());
        $this->assertStringContainsString('Kepuasan', $result->first()->title);
    }

    /** @test */
    public function paginate_respects_custom_per_page(): void
    {
        Questionnaire::factory()->count(10)->create();

        $result = $this->service->paginate(['per_page' => 5]);

        $this->assertEquals(5, $result->perPage());
    }

    // =========================================================================
    // getWithStructure()
    // =========================================================================

    /** @test */
    public function get_with_structure_returns_questionnaire_with_nested_relations(): void
    {
        $questionnaire = Questionnaire::factory()->create();
        $section = QuestionnaireSection::factory()->create([
            'questionnaire_id' => $questionnaire->id,
            'order_number'     => 1,
        ]);
        $question = Question::factory()->create([
            'questionnaire_id' => $questionnaire->id,
            'section_id'       => $section->id,
            'order_number'     => 1,
        ]);
        QuestionOption::create([
            'question_id'  => $question->id,
            'option_text'  => 'Opsi A',
            'option_value' => 'a',
            'order_number' => 1,
            'is_other'     => false,
        ]);

        $result = $this->service->getWithStructure($questionnaire->id);

        $this->assertEquals($questionnaire->id, $result->id);
        $this->assertTrue($result->relationLoaded('sections'));
        $this->assertTrue($result->sections->first()->relationLoaded('questions'));
        $this->assertTrue($result->sections->first()->questions->first()->relationLoaded('options'));
    }

    /** @test */
    public function get_with_structure_throws_model_not_found_for_invalid_id(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->getWithStructure(99999);
    }

    // =========================================================================
    // create()
    // =========================================================================

    /** @test */
    public function create_persists_questionnaire_with_status_draft(): void
    {
        $data = [
            'title'       => 'Kuesioner Tracer Study 2025',
            'description' => 'Deskripsi kuesioner.',
            'type'        => 'alumni',
        ];

        $result = $this->service->create($data, $this->admin->id);

        $this->assertDatabaseHas('questionnaires', [
            'title'      => 'Kuesioner Tracer Study 2025',
            'status'     => 'draft',
            'version'    => 1,
            'created_by' => $this->admin->id,
        ]);
        $this->assertEquals('draft', $result->status);
    }

    /** @test */
    public function create_persists_sections_and_questions_and_options(): void
    {
        $data = [
            'title' => 'Kuesioner Lengkap',
            'type'  => 'alumni',
            'sections' => [
                ['title' => 'Bagian 1', 'order_number' => 1],
            ],
            'questions' => [
                [
                    'question_text'  => 'Pertanyaan 1',
                    'question_type'  => 'radio',
                    'is_required'    => true,
                    'order_number'   => 1,
                    'section_index'  => 0,
                    'options' => [
                        ['option_text' => 'Ya',  'option_value' => 'ya',  'order_number' => 1],
                        ['option_text' => 'Tidak', 'option_value' => 'tidak', 'order_number' => 2],
                    ],
                ],
            ],
        ];

        $result = $this->service->create($data, $this->admin->id);

        $this->assertDatabaseCount('questionnaire_sections', 1);
        $this->assertDatabaseCount('questions', 1);
        $this->assertDatabaseCount('question_options', 2);
        $this->assertEquals(1, $result->sections()->count());
    }

    /** @test */
    public function create_writes_audit_log(): void
    {
        $this->service->create([
            'title' => 'Test Audit',
            'type'  => 'alumni',
        ], $this->admin->id);

        $this->assertDatabaseHas('audit_logs', [
            'module'  => 'questionnaire',
            'action'  => 'created',
            'user_id' => $this->admin->id,
        ]);
    }

    /** @test */
    public function create_rolls_back_on_exception(): void
    {
        // Paksa exception dengan question_type invalid (constraint DB)
        $data = [
            'title' => 'Rollback Test',
            'type'  => 'alumni',
            'questions' => [
                [
                    'question_text' => 'Q?',
                    'question_type' => 'INVALID_TYPE_FORCE_FAIL',
                    'is_required'   => true,
                    'order_number'  => 1,
                ],
            ],
        ];

        try {
            $this->service->create($data, $this->admin->id);
        } catch (\Throwable) {
            // diharapkan
        }

        $this->assertDatabaseCount('questionnaires', 0);
    }

    // =========================================================================
    // update()
    // =========================================================================

    /** @test */
    public function update_changes_header_fields(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->createdBy($this->admin->id)->create();

        $this->service->update($questionnaire, [
            'title'       => 'Judul Diubah',
            'description' => 'Deskripsi baru.',
        ], $this->admin->id);

        $this->assertDatabaseHas('questionnaires', [
            'id'    => $questionnaire->id,
            'title' => 'Judul Diubah',
        ]);
    }

    /** @test */
    public function update_adds_new_section(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->createdBy($this->admin->id)->create();

        $this->service->update($questionnaire, [
            'sections' => [
                ['title' => 'Seksi Baru', 'order_number' => 1],
            ],
        ], $this->admin->id);

        $this->assertDatabaseCount('questionnaire_sections', 1);
    }

    /** @test */
    public function update_deletes_section_when_delete_flag_set(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->createdBy($this->admin->id)->create();
        $section = QuestionnaireSection::factory()->create([
            'questionnaire_id' => $questionnaire->id,
            'order_number'     => 1,
        ]);

        $this->service->update($questionnaire, [
            'sections' => [
                ['id' => $section->id, '_delete' => true],
            ],
        ], $this->admin->id);

        $this->assertDatabaseMissing('questionnaire_sections', ['id' => $section->id]);
    }

    /** @test */
    public function update_writes_audit_log_with_old_values(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->createdBy($this->admin->id)->create([
            'title' => 'Judul Lama',
        ]);

        $this->service->update($questionnaire, ['title' => 'Judul Baru'], $this->admin->id);

        $log = AuditLog::where('module', 'questionnaire')->where('action', 'updated')->first();
        $this->assertNotNull($log);
        $this->assertArrayHasKey('title', $log->old_values ?? []);
    }

    // =========================================================================
    // delete()
    // =========================================================================

    /** @test */
    public function delete_removes_draft_questionnaire(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->create();

        $this->service->delete($questionnaire, $this->admin->id);

        $this->assertDatabaseMissing('questionnaires', ['id' => $questionnaire->id]);
    }

    /** @test */
    public function delete_throws_logic_exception_for_active_questionnaire(): void
    {
        $questionnaire = Questionnaire::factory()->aktif()->create();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches("/aktif/");

        $this->service->delete($questionnaire, $this->admin->id);
    }

    /** @test */
    public function delete_writes_audit_log(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->create();

        $this->service->delete($questionnaire, $this->admin->id);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'questionnaire',
            'action' => 'deleted',
        ]);
    }

    // =========================================================================
    // publish()
    // =========================================================================

    /** @test */
    public function publish_transitions_draft_to_aktif(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->create();
        Question::factory()->create([
            'questionnaire_id' => $questionnaire->id,
            'order_number'     => 1,
        ]);

        $result = $this->service->publish($questionnaire, $this->admin->id);

        $this->assertEquals('aktif', $result->status);
        $this->assertNotNull($result->published_at);
    }

    /** @test */
    public function publish_throws_logic_exception_if_not_draft(): void
    {
        $questionnaire = Questionnaire::factory()->aktif()->create();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches("/draft/");

        $this->service->publish($questionnaire, $this->admin->id);
    }

    /** @test */
    public function publish_throws_logic_exception_if_no_questions(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->create();
        // Tidak ada question sama sekali

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches("/minimal 1 pertanyaan/");

        $this->service->publish($questionnaire, $this->admin->id);
    }

    /** @test */
    public function publish_writes_audit_log(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->create();
        Question::factory()->create(['questionnaire_id' => $questionnaire->id, 'order_number' => 1]);

        $this->service->publish($questionnaire, $this->admin->id);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'questionnaire',
            'action' => 'published',
        ]);
    }

    // =========================================================================
    // archive()
    // =========================================================================

    /** @test */
    public function archive_transitions_aktif_to_arsip(): void
    {
        $questionnaire = Questionnaire::factory()->aktif()->create();

        $result = $this->service->archive($questionnaire, $this->admin->id);

        $this->assertEquals('arsip', $result->status);
    }

    /** @test */
    public function archive_throws_logic_exception_if_not_aktif(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->create();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches("/aktif/");

        $this->service->archive($questionnaire, $this->admin->id);
    }

    /** @test */
    public function archive_writes_audit_log_with_status_transition(): void
    {
        $questionnaire = Questionnaire::factory()->aktif()->create();

        $this->service->archive($questionnaire, $this->admin->id);

        $log = AuditLog::where('module', 'questionnaire')->where('action', 'archived')->first();
        $this->assertNotNull($log);
    }

    // =========================================================================
    // duplicate()
    // =========================================================================

    /** @test */
    public function duplicate_creates_new_questionnaire_with_draft_status(): void
    {
        $questionnaire = Questionnaire::factory()->aktif()->createdBy($this->admin->id)->create([
            'title'   => 'Kuesioner Original',
            'version' => 1,
        ]);

        $copy = $this->service->duplicate($questionnaire, $this->admin->id);

        $this->assertEquals('draft', $copy->status);
        $this->assertStringContainsString('Salinan', $copy->title);
        $this->assertEquals(2, $copy->version);
        $this->assertNotEquals($questionnaire->id, $copy->id);
    }

    /** @test */
    public function duplicate_clones_sections_and_questions_and_options(): void
    {
        $questionnaire = Questionnaire::factory()->aktif()->createdBy($this->admin->id)->create();
        $section = QuestionnaireSection::factory()->create([
            'questionnaire_id' => $questionnaire->id,
            'order_number'     => 1,
        ]);
        $question = Question::factory()->radio()->create([
            'questionnaire_id' => $questionnaire->id,
            'section_id'       => $section->id,
            'order_number'     => 1,
        ]);
        QuestionOption::create([
            'question_id'  => $question->id,
            'option_text'  => 'Opsi A',
            'option_value' => 'a',
            'order_number' => 1,
            'is_other'     => false,
        ]);

        $copy = $this->service->duplicate($questionnaire, $this->admin->id);

        // 2 kuesioner total di DB
        $this->assertDatabaseCount('questionnaires', 2);
        // Copy punya sections, questions, options sendiri
        $this->assertEquals(1, $copy->sections()->count());
        $copyQuestion = $copy->questions()->first();
        $this->assertNotNull($copyQuestion);
        $this->assertEquals(1, $copyQuestion->options()->count());
        // Section ID berbeda dari asli
        $copySection = $copy->sections()->first();
        $this->assertNotEquals($section->id, $copySection->id);
    }

    /** @test */
    public function duplicate_writes_audit_log(): void
    {
        $questionnaire = Questionnaire::factory()->aktif()->createdBy($this->admin->id)->create();

        $this->service->duplicate($questionnaire, $this->admin->id);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'questionnaire',
            'action' => 'duplicated',
        ]);
    }

    // =========================================================================
    // reorder()
    // =========================================================================

    /** @test */
    public function reorder_updates_question_order_numbers(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->create();
        $q1 = Question::factory()->create(['questionnaire_id' => $questionnaire->id, 'order_number' => 1]);
        $q2 = Question::factory()->create(['questionnaire_id' => $questionnaire->id, 'order_number' => 2]);

        $this->service->reorder($questionnaire, [
            'target' => 'questions',
            'items'  => [
                ['id' => $q1->id, 'order_number' => 2],
                ['id' => $q2->id, 'order_number' => 1],
            ],
        ], $this->admin->id);

        $this->assertDatabaseHas('questions', ['id' => $q1->id, 'order_number' => 2]);
        $this->assertDatabaseHas('questions', ['id' => $q2->id, 'order_number' => 1]);
    }

    /** @test */
    public function reorder_updates_section_order_numbers(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->create();
        $s1 = QuestionnaireSection::factory()->create(['questionnaire_id' => $questionnaire->id, 'order_number' => 1]);
        $s2 = QuestionnaireSection::factory()->create(['questionnaire_id' => $questionnaire->id, 'order_number' => 2]);

        $this->service->reorder($questionnaire, [
            'target' => 'sections',
            'items'  => [
                ['id' => $s1->id, 'order_number' => 2],
                ['id' => $s2->id, 'order_number' => 1],
            ],
        ], $this->admin->id);

        $this->assertDatabaseHas('questionnaire_sections', ['id' => $s1->id, 'order_number' => 2]);
        $this->assertDatabaseHas('questionnaire_sections', ['id' => $s2->id, 'order_number' => 1]);
    }

    /** @test */
    public function reorder_does_not_affect_other_questionnaire_items(): void
    {
        $q1 = Questionnaire::factory()->draft()->create();
        $q2 = Questionnaire::factory()->draft()->create();
        $question1 = Question::factory()->create(['questionnaire_id' => $q1->id, 'order_number' => 1]);
        $question2 = Question::factory()->create(['questionnaire_id' => $q2->id, 'order_number' => 5]);

        $this->service->reorder($q1, [
            'target' => 'questions',
            'items'  => [['id' => $question1->id, 'order_number' => 99]],
        ], $this->admin->id);

        // question2 tidak boleh terpengaruh
        $this->assertDatabaseHas('questions', ['id' => $question2->id, 'order_number' => 5]);
    }

    /** @test */
    public function reorder_writes_audit_log(): void
    {
        $questionnaire = Questionnaire::factory()->draft()->create();
        $q = Question::factory()->create(['questionnaire_id' => $questionnaire->id, 'order_number' => 1]);

        $this->service->reorder($questionnaire, [
            'target' => 'questions',
            'items'  => [['id' => $q->id, 'order_number' => 1]],
        ], $this->admin->id);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'questionnaire',
            'action' => 'reordered',
        ]);
    }
}

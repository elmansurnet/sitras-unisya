<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Question;
use App\Models\QuestionnaireSection;
use App\Models\QuestionOption;
use App\Models\Questionnaire;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use LogicException;

class QuestionnaireService
{
    // -------------------------------------------------------------------------
    // Read
    // -------------------------------------------------------------------------

    /**
     * Paginate daftar kuesioner dengan filter opsional.
     *
     * @param array{type?: string, status?: string, search?: string, per_page?: int} $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Questionnaire::with('creator:id,name')
            ->withCount('questions');

        if (! empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where('title', 'like', $search);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Ambil satu kuesioner beserta seluruh struktur (sections > questions > options).
     */
    public function getWithStructure(int $id): Questionnaire
    {
        return Questionnaire::with([
            'creator:id,name',
            'sections' => fn ($q) => $q->ordered(),
            'sections.questions' => fn ($q) => $q->ordered(),
            'sections.questions.options' => fn ($q) => $q->ordered(),
            'questions' => fn ($q) => $q->whereNull('section_id')->ordered(),
            'questions.options' => fn ($q) => $q->ordered(),
        ])->findOrFail($id);
    }

    // -------------------------------------------------------------------------
    // Write
    // -------------------------------------------------------------------------

    /**
     * Buat kuesioner baru beserta sections, questions, dan options.
     *
     * @param array $data Data tervalidasi dari StoreQuestionnaireRequest
     * @param int   $createdBy ID user yang membuat
     */
    public function create(array $data, int $createdBy): Questionnaire
    {
        return DB::transaction(function () use ($data, $createdBy) {
            // 1. Buat header kuesioner
            $questionnaire = Questionnaire::create([
                'title'             => $data['title'],
                'description'       => $data['description'] ?? null,
                'type'              => $data['type'],
                'version'           => 1,
                'status'            => 'draft',
                'is_paginated'      => $data['is_paginated'] ?? false,
                'estimated_minutes' => $data['estimated_minutes'] ?? null,
                'created_by'        => $createdBy,
            ]);

            // 2. Buat sections dan bangun mapping index -> ID
            $sectionIdMap = [];
            foreach ($data['sections'] ?? [] as $idx => $sectionData) {
                $section = $questionnaire->sections()->create([
                    'title'        => $sectionData['title'],
                    'description'  => $sectionData['description'] ?? null,
                    'order_number' => $sectionData['order_number'],
                ]);
                $sectionIdMap[$idx] = $section->id;
            }

            // 3. Buat questions (dan options)
            foreach ($data['questions'] ?? [] as $questionData) {
                $this->createQuestion($questionnaire->id, $questionData, $sectionIdMap);
            }

            // 4. Audit log
            AuditLog::record(
                module: 'questionnaire',
                action: 'created',
                description: "Kuesioner '{$questionnaire->title}' (ID:{$questionnaire->id}) dibuat.",
                newValues: ['id' => $questionnaire->id, 'title' => $questionnaire->title, 'type' => $questionnaire->type],
                userId: $createdBy,
            );

            return $questionnaire->load('sections.questions.options');
        });
    }

    /**
     * Update kuesioner: partial update header + sync sections/questions/options.
     *
     * @param array $data Data tervalidasi dari UpdateQuestionnaireRequest
     * @param int   $updatedBy ID user yang mengubah
     */
    public function update(Questionnaire $questionnaire, array $data, int $updatedBy): Questionnaire
    {
        return DB::transaction(function () use ($questionnaire, $data, $updatedBy) {
            $oldValues = $questionnaire->only(['title', 'description', 'type', 'status']);

            // 1. Update header (only fields yang dikirim)
            $headerFields = Arr::only($data, [
                'title', 'description', 'type', 'is_paginated', 'estimated_minutes',
            ]);
            if (! empty($headerFields)) {
                $questionnaire->update($headerFields);
            }

            // 2. Sync sections
            $sectionIdMap = [];
            foreach ($data['sections'] ?? [] as $idx => $sectionData) {
                if (! empty($sectionData['_delete']) && ! empty($sectionData['id'])) {
                    QuestionnaireSection::where('id', $sectionData['id'])
                        ->where('questionnaire_id', $questionnaire->id)
                        ->delete();
                    continue;
                }

                if (! empty($sectionData['id'])) {
                    // Update existing
                    $section = QuestionnaireSection::where('id', $sectionData['id'])
                        ->where('questionnaire_id', $questionnaire->id)
                        ->firstOrFail();
                    $section->update(Arr::only($sectionData, ['title', 'description', 'order_number']));
                } else {
                    // Create new
                    $section = $questionnaire->sections()->create([
                        'title'        => $sectionData['title'],
                        'description'  => $sectionData['description'] ?? null,
                        'order_number' => $sectionData['order_number'],
                    ]);
                }
                $sectionIdMap[$idx] = $section->id;
            }

            // 3. Sync questions
            foreach ($data['questions'] ?? [] as $questionData) {
                if (! empty($questionData['_delete']) && ! empty($questionData['id'])) {
                    Question::where('id', $questionData['id'])
                        ->where('questionnaire_id', $questionnaire->id)
                        ->delete();
                    continue;
                }

                if (! empty($questionData['id'])) {
                    $this->updateQuestion($questionnaire->id, $questionData, $sectionIdMap);
                } else {
                    $this->createQuestion($questionnaire->id, $questionData, $sectionIdMap);
                }
            }

            // 4. Audit log
            AuditLog::record(
                module: 'questionnaire',
                action: 'updated',
                description: "Kuesioner '{$questionnaire->title}' (ID:{$questionnaire->id}) diperbarui.",
                oldValues: $oldValues,
                newValues: $questionnaire->fresh()->only(['title', 'description', 'type', 'status']),
                userId: $updatedBy,
            );

            return $questionnaire->fresh()->load('sections.questions.options');
        });
    }

    /**
     * Hapus kuesioner. Menolak jika berstatus aktif.
     *
     * @throws LogicException
     */
    public function delete(Questionnaire $questionnaire, int $deletedBy): void
    {
        if ($questionnaire->isActive()) {
            throw new LogicException(
                "Kuesioner berstatus 'aktif' tidak dapat dihapus. Arsipkan terlebih dahulu."
            );
        }

        DB::transaction(function () use ($questionnaire, $deletedBy) {
            $title = $questionnaire->title;
            $id    = $questionnaire->id;

            // Cascade delete: options -> questions -> sections -> questionnaire
            // DB migration sudah cascadeOnDelete, cukup hapus questionnaire
            $questionnaire->delete();

            AuditLog::record(
                module: 'questionnaire',
                action: 'deleted',
                description: "Kuesioner '{$title}' (ID:{$id}) dihapus.",
                oldValues: ['id' => $id, 'title' => $title],
                userId: $deletedBy,
            );
        });
    }

    // -------------------------------------------------------------------------
    // Status Transitions
    // -------------------------------------------------------------------------

    /**
     * Publikasikan kuesioner: draft -> aktif.
     *
     * @throws LogicException
     */
    public function publish(Questionnaire $questionnaire, int $publishedBy): Questionnaire
    {
        if (! $questionnaire->isDraft()) {
            throw new LogicException(
                "Hanya kuesioner berstatus 'draft' yang dapat dipublikasikan."
            );
        }

        if ($questionnaire->questions()->count() === 0) {
            throw new LogicException(
                'Kuesioner harus memiliki minimal 1 pertanyaan sebelum dipublikasikan.'
            );
        }

        $questionnaire->update([
            'status'       => 'aktif',
            'published_at' => now(),
        ]);

        AuditLog::record(
            module: 'questionnaire',
            action: 'published',
            description: "Kuesioner '{$questionnaire->title}' (ID:{$questionnaire->id}) dipublikasikan.",
            newValues: ['status' => 'aktif', 'published_at' => $questionnaire->published_at],
            userId: $publishedBy,
        );

        return $questionnaire->fresh();
    }

    /**
     * Arsipkan kuesioner: aktif -> arsip.
     *
     * @throws LogicException
     */
    public function archive(Questionnaire $questionnaire, int $archivedBy): Questionnaire
    {
        if (! $questionnaire->isActive()) {
            throw new LogicException(
                "Hanya kuesioner berstatus 'aktif' yang dapat diarsipkan."
            );
        }

        $questionnaire->update(['status' => 'arsip']);

        AuditLog::record(
            module: 'questionnaire',
            action: 'archived',
            description: "Kuesioner '{$questionnaire->title}' (ID:{$questionnaire->id}) diarsipkan.",
            oldValues: ['status' => 'aktif'],
            newValues: ['status' => 'arsip'],
            userId: $archivedBy,
        );

        return $questionnaire->fresh();
    }

    // -------------------------------------------------------------------------
    // Duplicate
    // -------------------------------------------------------------------------

    /**
     * Duplikasi kuesioner beserta seluruh strukturnya.
     * Hasil duplikasi selalu berstatus 'draft' dengan versi +1.
     */
    public function duplicate(Questionnaire $questionnaire, int $duplicatedBy): Questionnaire
    {
        return DB::transaction(function () use ($questionnaire, $duplicatedBy) {
            // 1. Clone header
            $newQuestionnaire = Questionnaire::create([
                'title'             => $questionnaire->title . ' (Salinan)',
                'description'       => $questionnaire->description,
                'type'              => $questionnaire->type,
                'version'           => $questionnaire->version + 1,
                'status'            => 'draft',
                'is_paginated'      => $questionnaire->is_paginated,
                'estimated_minutes' => $questionnaire->estimated_minutes,
                'created_by'        => $duplicatedBy,
            ]);

            // 2. Load struktur asli
            $original = $this->getWithStructure($questionnaire->id);
            $sectionIdMap = [];

            // 3. Clone sections
            foreach ($original->sections as $section) {
                $newSection = $newQuestionnaire->sections()->create([
                    'title'        => $section->title,
                    'description'  => $section->description,
                    'order_number' => $section->order_number,
                ]);
                $sectionIdMap[$section->id] = $newSection->id;
            }

            // 4. Clone questions (yang punya section dan yang tidak)
            $allQuestions = $original->questions()->with('options')->ordered()->get();
            foreach ($allQuestions as $question) {
                $newQuestion = Question::create([
                    'questionnaire_id'  => $newQuestionnaire->id,
                    'section_id'        => $question->section_id
                        ? ($sectionIdMap[$question->section_id] ?? null)
                        : null,
                    'question_text'     => $question->question_text,
                    'question_type'     => $question->question_type,
                    'is_required'       => $question->is_required,
                    'order_number'      => $question->order_number,
                    'help_text'         => $question->help_text,
                    'placeholder'       => $question->placeholder,
                    'validation_rules'  => $question->validation_rules,
                    'conditional_logic' => $question->conditional_logic,
                ]);

                // 5. Clone options
                foreach ($question->options as $option) {
                    QuestionOption::create([
                        'question_id'  => $newQuestion->id,
                        'option_text'  => $option->option_text,
                        'option_value' => $option->option_value,
                        'order_number' => $option->order_number,
                        'is_other'     => $option->is_other,
                    ]);
                }
            }

            AuditLog::record(
                module: 'questionnaire',
                action: 'duplicated',
                description: "Kuesioner ID:{$questionnaire->id} diduplikasi menjadi ID:{$newQuestionnaire->id}.",
                newValues: ['source_id' => $questionnaire->id, 'new_id' => $newQuestionnaire->id],
                userId: $duplicatedBy,
            );

            return $newQuestionnaire->load('sections.questions.options');
        });
    }

    // -------------------------------------------------------------------------
    // Reorder
    // -------------------------------------------------------------------------

    /**
     * Urutkan ulang questions atau sections.
     *
     * @param array{target: string, items: array{id: int, order_number: int}[]} $data
     */
    public function reorder(Questionnaire $questionnaire, array $data, int $reorderedBy): void
    {
        DB::transaction(function () use ($questionnaire, $data, $reorderedBy) {
            $table = $data['target'] === 'sections' ? 'questionnaire_sections' : 'questions';

            foreach ($data['items'] as $item) {
                DB::table($table)
                    ->where('id', $item['id'])
                    ->where('questionnaire_id', $questionnaire->id)
                    ->update(['order_number' => $item['order_number']]);
            }

            AuditLog::record(
                module: 'questionnaire',
                action: 'reordered',
                description: "Urutan {$data['target']} pada kuesioner ID:{$questionnaire->id} diperbarui.",
                newValues: ['target' => $data['target'], 'count' => count($data['items'])],
                userId: $reorderedBy,
            );
        });
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Buat satu question beserta options-nya.
     */
    private function createQuestion(int $questionnaireId, array $data, array $sectionIdMap): Question
    {
        $sectionId = null;
        if (isset($data['section_index']) && isset($sectionIdMap[$data['section_index']])) {
            $sectionId = $sectionIdMap[$data['section_index']];
        }

        $question = Question::create([
            'questionnaire_id'  => $questionnaireId,
            'section_id'        => $sectionId,
            'question_text'     => $data['question_text'],
            'question_type'     => $data['question_type'],
            'is_required'       => $data['is_required'] ?? true,
            'order_number'      => $data['order_number'],
            'help_text'         => $data['help_text'] ?? null,
            'placeholder'       => $data['placeholder'] ?? null,
            'validation_rules'  => $data['validation_rules'] ?? null,
            'conditional_logic' => $data['conditional_logic'] ?? null,
        ]);

        foreach ($data['options'] ?? [] as $optionData) {
            QuestionOption::create([
                'question_id'  => $question->id,
                'option_text'  => $optionData['option_text'],
                'option_value' => $optionData['option_value'],
                'order_number' => $optionData['order_number'],
                'is_other'     => $optionData['is_other'] ?? false,
            ]);
        }

        return $question;
    }

    /**
     * Update satu question beserta sync options-nya.
     */
    private function updateQuestion(int $questionnaireId, array $data, array $sectionIdMap): void
    {
        $question = Question::where('id', $data['id'])
            ->where('questionnaire_id', $questionnaireId)
            ->firstOrFail();

        $sectionId = $question->section_id; // pertahankan section lama jika tidak diubah
        if (array_key_exists('section_index', $data)) {
            $sectionId = isset($sectionIdMap[$data['section_index']])
                ? $sectionIdMap[$data['section_index']]
                : null;
        }

        $question->update(array_merge(
            Arr::only($data, [
                'question_text', 'question_type', 'is_required',
                'order_number', 'help_text', 'placeholder',
                'validation_rules', 'conditional_logic',
            ]),
            ['section_id' => $sectionId]
        ));

        // Sync options
        foreach ($data['options'] ?? [] as $optionData) {
            if (! empty($optionData['_delete']) && ! empty($optionData['id'])) {
                QuestionOption::where('id', $optionData['id'])
                    ->where('question_id', $question->id)
                    ->delete();
                continue;
            }

            if (! empty($optionData['id'])) {
                QuestionOption::where('id', $optionData['id'])
                    ->where('question_id', $question->id)
                    ->update(Arr::only($optionData, ['option_text', 'option_value', 'order_number', 'is_other']));
            } else {
                QuestionOption::create([
                    'question_id'  => $question->id,
                    'option_text'  => $optionData['option_text'],
                    'option_value' => $optionData['option_value'],
                    'order_number' => $optionData['order_number'],
                    'is_other'     => $optionData['is_other'] ?? false,
                ]);
            }
        }
    }
}

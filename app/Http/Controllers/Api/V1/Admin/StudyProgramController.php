<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudyProgram\StoreStudyProgramRequest;
use App\Http\Requests\StudyProgram\UpdateStudyProgramRequest;
use App\Models\AuditLog;
use App\Models\StudyProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudyProgramController extends Controller
{
    /**
     * GET /api/v1/admin/study-programs
     */
    public function index(Request $request): JsonResponse
    {
        $query = StudyProgram::with('faculty')->withCount('alumni');

        if ($request->filled('faculty_id')) {
            $query->where('faculty_id', $request->integer('faculty_id'));
        }

        if ($request->filled('level')) {
            $query->where('level', $request->input('level'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $studyPrograms = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data'    => $studyPrograms,
        ]);
    }

    /**
     * GET /api/v1/admin/study-programs/{study_program}
     */
    public function show(StudyProgram $studyProgram): JsonResponse
    {
        $studyProgram->load('faculty');
        $studyProgram->loadCount('alumni');

        return response()->json([
            'success' => true,
            'data'    => $studyProgram,
        ]);
    }

    /**
     * POST /api/v1/admin/study-programs
     */
    public function store(StoreStudyProgramRequest $request): JsonResponse
    {
        $studyProgram = StudyProgram::create($request->validated());

        AuditLog::record(
            action:    'created',
            module:    'study_program',
            modelType: StudyProgram::class,
            modelId:   $studyProgram->id,
            newValues: $studyProgram->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Program studi berhasil ditambahkan.',
            'data'    => $studyProgram->load('faculty'),
        ], 201);
    }

    /**
     * PUT /api/v1/admin/study-programs/{study_program}
     */
    public function update(UpdateStudyProgramRequest $request, StudyProgram $studyProgram): JsonResponse
    {
        $oldValues = $studyProgram->toArray();

        $studyProgram->update($request->validated());

        AuditLog::record(
            action:    'updated',
            module:    'study_program',
            modelType: StudyProgram::class,
            modelId:   $studyProgram->id,
            oldValues: $oldValues,
            newValues: $studyProgram->fresh()->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Program studi berhasil diperbarui.',
            'data'    => $studyProgram->fresh()->load('faculty'),
        ]);
    }

    /**
     * DELETE /api/v1/admin/study-programs/{study_program}
     */
    public function destroy(StudyProgram $studyProgram): JsonResponse
    {
        if ($studyProgram->alumni()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Program studi tidak dapat dihapus karena masih memiliki data alumni.',
            ], 422);
        }

        $oldValues = $studyProgram->toArray();
        $spId      = $studyProgram->id;
        $studyProgram->delete();

        AuditLog::record(
            action:    'deleted',
            module:    'study_program',
            modelType: StudyProgram::class,
            modelId:   $spId,
            oldValues: $oldValues
        );

        return response()->json([
            'success' => true,
            'message' => 'Program studi berhasil dihapus.',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Faculty\StoreFacultyRequest;
use App\Http\Requests\Faculty\UpdateFacultyRequest;
use App\Models\AuditLog;
use App\Models\Faculty;
use Illuminate\Http\JsonResponse;

class FacultyController extends Controller
{
    /**
     * GET /api/v1/admin/faculties
     */
    public function index(): JsonResponse
    {
        $faculties = Faculty::withCount('studyPrograms')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $faculties,
        ]);
    }

    /**
     * GET /api/v1/admin/faculties/{faculty}
     */
    public function show(Faculty $faculty): JsonResponse
    {
        $faculty->loadCount('studyPrograms');
        $faculty->load('studyPrograms');

        return response()->json([
            'success' => true,
            'data'    => $faculty,
        ]);
    }

    /**
     * POST /api/v1/admin/faculties
     */
    public function store(StoreFacultyRequest $request): JsonResponse
    {
        $faculty = Faculty::create($request->validated());

        AuditLog::record(
            action:    'created',
            module:    'faculty',
            modelType: Faculty::class,
            modelId:   $faculty->id,
            newValues: $faculty->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Fakultas berhasil ditambahkan.',
            'data'    => $faculty->loadCount('studyPrograms'),
        ], 201);
    }

    /**
     * PUT /api/v1/admin/faculties/{faculty}
     */
    public function update(UpdateFacultyRequest $request, Faculty $faculty): JsonResponse
    {
        $oldValues = $faculty->toArray();

        $faculty->update($request->validated());

        AuditLog::record(
            action:    'updated',
            module:    'faculty',
            modelType: Faculty::class,
            modelId:   $faculty->id,
            oldValues: $oldValues,
            newValues: $faculty->fresh()->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Fakultas berhasil diperbarui.',
            'data'    => $faculty->fresh()->loadCount('studyPrograms')->load('studyPrograms'),
        ]);
    }

    /**
     * DELETE /api/v1/admin/faculties/{faculty}
     */
    public function destroy(Faculty $faculty): JsonResponse
    {
        if ($faculty->studyPrograms()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Fakultas tidak dapat dihapus karena masih memiliki program studi.',
            ], 422);
        }

        $oldValues = $faculty->toArray();
        $facId     = $faculty->id;
        $faculty->delete();

        AuditLog::record(
            action:    'deleted',
            module:    'faculty',
            modelType: Faculty::class,
            modelId:   $facId,
            oldValues: $oldValues
        );

        return response()->json([
            'success' => true,
            'message' => 'Fakultas berhasil dihapus.',
        ]);
    }
}

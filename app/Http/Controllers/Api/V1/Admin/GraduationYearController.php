<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GraduationYear\StoreGraduationYearRequest;
use App\Http\Requests\GraduationYear\UpdateGraduationYearRequest;
use App\Models\AuditLog;
use App\Models\GraduationYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GraduationYearController extends Controller
{
    /**
     * GET /api/v1/admin/graduation-years
     */
    public function index(Request $request): JsonResponse
    {
        $query = GraduationYear::withCount('alumni');

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $years = $query->orderByDesc('year')->get();

        return response()->json([
            'success' => true,
            'data'    => $years,
        ]);
    }

    /**
     * GET /api/v1/admin/graduation-years/{graduation_year}
     */
    public function show(GraduationYear $graduationYear): JsonResponse
    {
        $graduationYear->loadCount('alumni');

        return response()->json([
            'success' => true,
            'data'    => $graduationYear,
        ]);
    }

    /**
     * POST /api/v1/admin/graduation-years
     */
    public function store(StoreGraduationYearRequest $request): JsonResponse
    {
        $graduationYear = GraduationYear::create($request->validated());

        AuditLog::record(
            module: 'graduation_year',
            action: 'created',
            targetType: GraduationYear::class,
            targetId: $graduationYear->id,
            newValues: $graduationYear->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Tahun kelulusan berhasil ditambahkan.',
            'data'    => $graduationYear,
        ], 201);
    }

    /**
     * PUT /api/v1/admin/graduation-years/{graduation_year}
     */
    public function update(UpdateGraduationYearRequest $request, GraduationYear $graduationYear): JsonResponse
    {
        $oldValues = $graduationYear->toArray();

        $graduationYear->update($request->validated());

        AuditLog::record(
            module: 'graduation_year',
            action: 'updated',
            targetType: GraduationYear::class,
            targetId: $graduationYear->id,
            oldValues: $oldValues,
            newValues: $graduationYear->fresh()->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Tahun kelulusan berhasil diperbarui.',
            'data'    => $graduationYear->fresh(),
        ]);
    }

    /**
     * DELETE /api/v1/admin/graduation-years/{graduation_year}
     */
    public function destroy(GraduationYear $graduationYear): JsonResponse
    {
        if ($graduationYear->alumni()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tahun kelulusan tidak dapat dihapus karena masih memiliki data alumni.',
            ], 422);
        }

        $oldValues = $graduationYear->toArray();
        $graduationYear->delete();

        AuditLog::record(
            module: 'graduation_year',
            action: 'deleted',
            targetType: GraduationYear::class,
            targetId: $graduationYear->id,
            oldValues: $oldValues
        );

        return response()->json([
            'success' => true,
            'message' => 'Tahun kelulusan berhasil dihapus.',
        ]);
    }
}

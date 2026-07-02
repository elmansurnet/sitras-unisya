<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalaryRange\StoreSalaryRangeRequest;
use App\Http\Requests\SalaryRange\UpdateSalaryRangeRequest;
use App\Models\AuditLog;
use App\Models\SalaryRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SalaryRangeController
 * CRUD admin untuk tabel salary_ranges.
 * Routes: /api/v1/admin/salary-ranges
 */
class SalaryRangeController extends Controller
{
    /**
     * GET /api/v1/admin/salary-ranges
     */
    public function index(Request $request): JsonResponse
    {
        $query = SalaryRange::query();

        if ($request->filled('search')) {
            $query->where('label', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', (bool) $request->status);
        }

        $ranges = $query->orderBy('order_number')->orderBy('label')->get();

        return response()->json([
            'success' => true,
            'data'    => $ranges,
        ]);
    }

    /**
     * GET /api/v1/admin/salary-ranges/{salaryRange}
     */
    public function show(SalaryRange $salaryRange): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $salaryRange,
        ]);
    }

    /**
     * POST /api/v1/admin/salary-ranges
     */
    public function store(StoreSalaryRangeRequest $request): JsonResponse
    {
        $range = SalaryRange::create($request->validated());

        AuditLog::record(
            action:    'created',
            module:    'salary_range',
            modelType: SalaryRange::class,
            modelId:   $range->id,
            newValues: $range->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Rentang gaji berhasil ditambahkan.',
            'data'    => $range,
        ], 201);
    }

    /**
     * PUT /api/v1/admin/salary-ranges/{salaryRange}
     */
    public function update(UpdateSalaryRangeRequest $request, SalaryRange $salaryRange): JsonResponse
    {
        $oldValues = $salaryRange->toArray();

        $salaryRange->update($request->validated());

        AuditLog::record(
            action:    'updated',
            module:    'salary_range',
            modelType: SalaryRange::class,
            modelId:   $salaryRange->id,
            oldValues: $oldValues,
            newValues: $salaryRange->fresh()->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Rentang gaji berhasil diperbarui.',
            'data'    => $salaryRange->fresh(),
        ]);
    }

    /**
     * DELETE /api/v1/admin/salary-ranges/{salaryRange}
     */
    public function destroy(SalaryRange $salaryRange): JsonResponse
    {
        if ($salaryRange->alumni()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Rentang gaji tidak dapat dihapus karena masih digunakan oleh data alumni.',
            ], 422);
        }

        $oldValues = $salaryRange->toArray();
        $srId      = $salaryRange->id;
        $salaryRange->delete();

        AuditLog::record(
            action:    'deleted',
            module:    'salary_range',
            modelType: SalaryRange::class,
            modelId:   $srId,
            oldValues: $oldValues
        );

        return response()->json([
            'success' => true,
            'message' => 'Rentang gaji berhasil dihapus.',
        ]);
    }
}

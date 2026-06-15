<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndustrySector\StoreIndustrySectorRequest;
use App\Http\Requests\IndustrySector\UpdateIndustrySectorRequest;
use App\Models\AuditLog;
use App\Models\IndustrySector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * IndustrySectorController
 * CRUD admin untuk tabel industry_sectors.
 * Routes: /api/v1/admin/industry-sectors
 */
class IndustrySectorController extends Controller
{
    /**
     * GET /api/v1/admin/industry-sectors
     * Dukung query params: search, status (1|0), per_page
     */
    public function index(Request $request): JsonResponse
    {
        $query = IndustrySector::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', (bool) $request->status);
        }

        $sectors = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data'    => $sectors,
        ]);
    }

    /**
     * GET /api/v1/admin/industry-sectors/{industrySector}
     */
    public function show(IndustrySector $industrySector): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $industrySector,
        ]);
    }

    /**
     * POST /api/v1/admin/industry-sectors
     */
    public function store(StoreIndustrySectorRequest $request): JsonResponse
    {
        $sector = IndustrySector::create($request->validated());

        AuditLog::record(
            module: 'industry_sector',
            action: 'created',
            targetType: IndustrySector::class,
            targetId: $sector->id,
            newValues: $sector->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Sektor industri berhasil ditambahkan.',
            'data'    => $sector,
        ], 201);
    }

    /**
     * PUT /api/v1/admin/industry-sectors/{industrySector}
     */
    public function update(UpdateIndustrySectorRequest $request, IndustrySector $industrySector): JsonResponse
    {
        $oldValues = $industrySector->toArray();

        $industrySector->update($request->validated());

        AuditLog::record(
            module: 'industry_sector',
            action: 'updated',
            targetType: IndustrySector::class,
            targetId: $industrySector->id,
            oldValues: $oldValues,
            newValues: $industrySector->fresh()->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Sektor industri berhasil diperbarui.',
            'data'    => $industrySector->fresh(),
        ]);
    }

    /**
     * DELETE /api/v1/admin/industry-sectors/{industrySector}
     * Restrict: tidak dapat dihapus jika masih direferensi data alumni.
     */
    public function destroy(IndustrySector $industrySector): JsonResponse
    {
        if ($industrySector->alumni()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Sektor industri tidak dapat dihapus karena masih digunakan oleh data alumni.',
            ], 422);
        }

        $oldValues = $industrySector->toArray();
        $industrySector->delete();

        AuditLog::record(
            module: 'industry_sector',
            action: 'deleted',
            targetType: IndustrySector::class,
            targetId: $industrySector->id,
            oldValues: $oldValues
        );

        return response()->json([
            'success' => true,
            'message' => 'Sektor industri berhasil dihapus.',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\StoreWorkHistoryRequest;
use App\Models\Alumni;
use App\Models\AlumniWorkHistory;
use App\Models\AuditLog;
use App\Repositories\AlumniRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Alumni/WorkHistoryController
 *
 * Mengelola riwayat pekerjaan alumni.
 *
 * Routes (05_API.md §2.3):
 *   GET    /api/v1/alumni/work-histories              → index
 *   POST   /api/v1/alumni/work-histories              → store
 *   PUT    /api/v1/alumni/work-histories/{id}         → update
 *   DELETE /api/v1/alumni/work-histories/{id}         → destroy
 *
 * Admin tambahan:
 *   GET    /api/v1/admin/alumni/{alumni}/work-histories → indexForAdmin
 */
class WorkHistoryController extends Controller
{
    public function __construct(
        private readonly AlumniRepository $alumniRepo,
    ) {}

    /**
     * GET /alumni/work-histories
     * Semua riwayat kerja alumni yang login.
     */
    public function index(Request $request): JsonResponse
    {
        $alumni = $this->alumniRepo->findByUserId($request->user()->id);

        if (!$alumni) {
            return response()->json([
                'success' => false,
                'message' => 'Profil alumni tidak ditemukan.',
            ], 404);
        }

        $histories = $alumni->workHistories()
            ->with(['industrySector:id,name', 'salaryRange:id,label'])
            ->orderByDesc('start_date')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $histories,
        ]);
    }

    /**
     * POST /alumni/work-histories (atau /admin/alumni/{alumni}/work-histories)
     * Tambah riwayat kerja.
     */
    public function store(StoreWorkHistoryRequest $request, Alumni $alumni): JsonResponse
    {
        // Jika pekerjaan saat ini, set end_date ke null
        $data = $request->validated();
        if (($data['is_current'] ?? false)) {
            $data['end_date'] = null;
        }

        $history = $alumni->workHistories()->create($data);

        AuditLog::record(
            action   : 'create',
            module   : 'alumni_work_history',
            modelId  : $history->id,
            oldValues: null,
            newValues: $history->toArray(),
            modelType: AlumniWorkHistory::class,
        );

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pekerjaan berhasil ditambahkan.',
            'data'    => $history->load(['industrySector:id,name', 'salaryRange:id,label']),
        ], 201);
    }

    /**
     * PUT /alumni/work-histories/{workHistory}
     * Update riwayat kerja.
     */
    public function update(StoreWorkHistoryRequest $request, Alumni $alumni, AlumniWorkHistory $workHistory): JsonResponse
    {
        // Pastikan riwayat ini milik alumni ybs
        if ($workHistory->alumni_id !== $alumni->id) {
            return response()->json([
                'success' => false,
                'message' => 'Riwayat pekerjaan tidak ditemukan.',
            ], 404);
        }

        $old  = $workHistory->toArray();
        $data = $request->validated();

        if (($data['is_current'] ?? false)) {
            $data['end_date'] = null;
        }

        $workHistory->update($data);

        AuditLog::record(
            action   : 'update',
            module   : 'alumni_work_history',
            modelId  : $workHistory->id,
            oldValues: $old,
            newValues: $workHistory->fresh()->toArray(),
            modelType: AlumniWorkHistory::class,
        );

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pekerjaan berhasil diperbarui.',
            'data'    => $workHistory->load(['industrySector:id,name', 'salaryRange:id,label']),
        ]);
    }

    /**
     * DELETE /alumni/work-histories/{workHistory}
     * Hapus riwayat kerja.
     */
    public function destroy(Request $request, Alumni $alumni, AlumniWorkHistory $workHistory): JsonResponse
    {
        if ($workHistory->alumni_id !== $alumni->id) {
            return response()->json([
                'success' => false,
                'message' => 'Riwayat pekerjaan tidak ditemukan.',
            ], 404);
        }

        $user = $request->user();
        if (!$user->isAdmin() && $user->id !== $alumni->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak punya akses untuk menghapus riwayat ini.',
            ], 403);
        }

        AuditLog::record(
            action   : 'delete',
            module   : 'alumni_work_history',
            modelId  : $workHistory->id,
            oldValues: $workHistory->toArray(),
            newValues: null,
            modelType: AlumniWorkHistory::class,
        );

        $workHistory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pekerjaan berhasil dihapus.',
        ]);
    }

    /**
     * GET /admin/alumni/{alumni}/work-histories
     * Admin melihat semua riwayat kerja satu alumni.
     */
    public function indexForAdmin(Request $request, Alumni $alumni): JsonResponse
    {
        $this->authorize('view', $alumni);

        $histories = $alumni->workHistories()
            ->with(['industrySector:id,name', 'salaryRange:id,label'])
            ->orderByDesc('start_date')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $histories,
        ]);
    }
}

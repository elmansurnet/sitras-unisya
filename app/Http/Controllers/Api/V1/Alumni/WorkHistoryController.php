<?php

namespace App\Http\Controllers\Api\V1\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\StoreWorkHistoryRequest;
use App\Models\Alumni;
use App\Models\AlumniWorkHistory;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkHistoryController extends Controller
{
    /**
     * GET /api/v1/alumni/{alumni}/work-histories
     * Daftar riwayat kerja alumni.
     */
    public function index(Alumni $alumni): JsonResponse
    {
        $this->authorizeAlumniAccess($alumni);

        $histories = $alumni->workHistories()
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Riwayat kerja berhasil diambil.',
            'data'    => $histories,
        ]);
    }

    /**
     * POST /api/v1/alumni/{alumni}/work-histories
     * Tambah riwayat kerja.
     */
    public function store(StoreWorkHistoryRequest $request, Alumni $alumni): JsonResponse
    {
        $history = $alumni->workHistories()->create($request->validated());

        // Jika is_current=true, set yang lain jadi false
        if ($history->is_current) {
            $alumni->workHistories()
                ->where('id', '!=', $history->id)
                ->update(['is_current' => false]);
        }

        AuditLog::record(
            action: 'create',
            module: 'AlumniWorkHistory',
            modelId: $history->id,
            newValues: $history->toArray(),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Riwayat kerja berhasil ditambahkan.',
            'data'    => $history,
        ], 201);
    }

    /**
     * PUT /api/v1/alumni/{alumni}/work-histories/{workHistory}
     * Update riwayat kerja.
     */
    public function update(
        StoreWorkHistoryRequest $request,
        Alumni $alumni,
        AlumniWorkHistory $workHistory
    ): JsonResponse {
        $this->authorizeAlumniAccess($alumni);

        // Pastikan work history milik alumni ini
        if ($workHistory->alumni_id !== $alumni->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Riwayat kerja tidak ditemukan untuk alumni ini.',
                'data'    => null,
            ], 404);
        }

        $oldValues = $workHistory->toArray();
        $workHistory->update($request->validated());

        if ($workHistory->is_current) {
            $alumni->workHistories()
                ->where('id', '!=', $workHistory->id)
                ->update(['is_current' => false]);
        }

        AuditLog::record(
            action: 'update',
            module: 'AlumniWorkHistory',
            modelId: $workHistory->id,
            oldValues: $oldValues,
            newValues: $workHistory->fresh()->toArray(),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Riwayat kerja berhasil diperbarui.',
            'data'    => $workHistory->fresh(),
        ]);
    }

    /**
     * DELETE /api/v1/alumni/{alumni}/work-histories/{workHistory}
     * Hapus riwayat kerja.
     */
    public function destroy(Alumni $alumni, AlumniWorkHistory $workHistory): JsonResponse
    {
        $this->authorizeAlumniAccess($alumni);

        if ($workHistory->alumni_id !== $alumni->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Riwayat kerja tidak ditemukan untuk alumni ini.',
                'data'    => null,
            ], 404);
        }

        AuditLog::record(
            action: 'delete',
            module: 'AlumniWorkHistory',
            modelId: $workHistory->id,
            oldValues: $workHistory->toArray(),
        );

        $workHistory->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Riwayat kerja berhasil dihapus.',
            'data'    => null,
        ]);
    }

    /**
     * Otorisasi akses ke alumni:
     * - Admin/Superadmin: selalu boleh
     * - Alumni: hanya datanya sendiri
     */
    private function authorizeAlumniAccess(Alumni $alumni): void
    {
        $user = request()->user();

        if (in_array($user->role, ['superadmin', 'admin'], true)) {
            return;
        }

        if ($user->role !== 'alumni' || $user->id !== $alumni->user_id) {
            abort(403, 'Anda tidak memiliki akses ke data alumni ini.');
        }
    }
}

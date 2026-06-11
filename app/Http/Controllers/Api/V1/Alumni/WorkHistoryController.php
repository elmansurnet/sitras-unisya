<?php

namespace App\Http\Controllers\Api\V1\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\StoreWorkHistoryRequest;
use App\Http\Requests\Alumni\UpdateWorkHistoryRequest;
use App\Models\Alumni;
use App\Models\AlumniWorkHistory;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkHistoryController extends Controller
{
    // ─── GET /alumni/work-histories (self) ────────────────────────────────────
    /**
     * Daftar riwayat pekerjaan milik alumni yang sedang login.
     * 05_API.md §11.4
     */
    public function index(Request $request): JsonResponse
    {
        $alumni = Alumni::where('user_id', $request->user()->id)->firstOrFail();

        $histories = AlumniWorkHistory::where('alumni_id', $alumni->id)
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->get()
            ->map(fn(AlumniWorkHistory $wh) => $this->formatWorkHistory($wh));

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pekerjaan berhasil diambil',
            'data'    => $histories,
        ]);
    }

    // ─── POST /alumni/work-histories/{alumni} ─────────────────────────────────
    /**
     * Tambah riwayat pekerjaan.
     * Otorisasi ditangani sepenuhnya oleh StoreWorkHistoryRequest::authorize().
     * 05_API.md §11.5
     */
    public function store(StoreWorkHistoryRequest $request, Alumni $alumni): JsonResponse
    {
        $validated = $request->validated();

        // Jika is_current = true, reset flag pada pekerjaan lain milik alumni ini
        if (!empty($validated['is_current'])) {
            AlumniWorkHistory::where('alumni_id', $alumni->id)
                ->update(['is_current' => false]);
        }

        $workHistory = AlumniWorkHistory::create(
            array_merge($validated, ['alumni_id' => $alumni->id])
        );

        AuditLog::record(
            action   : 'create_work_history',
            module   : 'alumni',
            modelId  : $alumni->id,
            oldValues: null,
            newValues: [
                'work_history_id' => $workHistory->id,
                'company'         => $workHistory->company_name,
            ],
            modelType: Alumni::class,
        );

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pekerjaan berhasil ditambahkan',
            'data'    => $this->formatWorkHistory($workHistory),
        ], 201);
    }

    // ─── PUT /alumni/work-histories/{alumni}/{workHistory} ────────────────────
    /**
     * Update riwayat pekerjaan.
     * Otorisasi & ownership check ditangani oleh UpdateWorkHistoryRequest::authorize().
     * 05_API.md §11.6
     */
    public function update(
        UpdateWorkHistoryRequest $request,
        Alumni $alumni,
        AlumniWorkHistory $workHistory,
    ): JsonResponse {
        $validated = $request->validated();

        if (!empty($validated['is_current'])) {
            AlumniWorkHistory::where('alumni_id', $alumni->id)
                ->where('id', '!=', $workHistory->id)
                ->update(['is_current' => false]);
        }

        $oldValues = $workHistory->only([
            'company_name', 'position', 'employment_type',
            'start_date', 'end_date', 'is_current',
        ]);

        $workHistory->update($validated);

        AuditLog::record(
            action   : 'update_work_history',
            module   : 'alumni',
            modelId  : $alumni->id,
            oldValues: array_merge(['work_history_id' => $workHistory->id], $oldValues),
            newValues: array_merge(['work_history_id' => $workHistory->id], $validated),
            modelType: Alumni::class,
        );

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pekerjaan berhasil diperbarui',
            'data'    => $this->formatWorkHistory($workHistory->fresh()),
        ]);
    }

    // ─── DELETE /alumni/work-histories/{alumni}/{workHistory} ─────────────────
    /**
     * Hapus riwayat pekerjaan.
     * 05_API.md §11.7
     */
    public function destroy(
        Request $request,
        Alumni $alumni,
        AlumniWorkHistory $workHistory,
    ): JsonResponse {
        // Ownership: alumni hanya bisa hapus miliknya sendiri; admin bebas
        if (!$request->user()->isAdmin() && $alumni->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses data ini.');
        }

        if ((int) $workHistory->alumni_id !== (int) $alumni->id) {
            abort(403, 'Riwayat pekerjaan ini tidak milik alumni yang dimaksud.');
        }

        AuditLog::record(
            action   : 'delete_work_history',
            module   : 'alumni',
            modelId  : $alumni->id,
            oldValues: [
                'work_history_id' => $workHistory->id,
                'company'         => $workHistory->company_name,
            ],
            newValues: null,
            modelType: Alumni::class,
        );

        $workHistory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pekerjaan berhasil dihapus',
        ]);
    }

    // ─── GET /admin/alumni/{alumni}/work-histories ────────────────────────────
    /**
     * Daftar riwayat pekerjaan untuk view admin.
     * Dipanggil dari route admin di routes/api.php.
     * 05_API.md §3.x (work-histories sub-resource)
     */
    public function indexForAdmin(Request $request, Alumni $alumni): JsonResponse
    {
        $histories = AlumniWorkHistory::where('alumni_id', $alumni->id)
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->get()
            ->map(fn(AlumniWorkHistory $wh) => $this->formatWorkHistory($wh));

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pekerjaan berhasil diambil',
            'data'    => $histories,
        ]);
    }

    // ─── PRIVATE HELPERS ──────────────────────────────────────────────────────

    /**
     * Format work history sesuai response spec.
     *
     * @return array<string,mixed>
     */
    private function formatWorkHistory(AlumniWorkHistory $wh): array
    {
        return [
            'id'                  => $wh->id,
            'company_name'        => $wh->company_name,
            'position'            => $wh->position,
            'employment_type'     => $wh->employment_type,
            'industry_sector'     => $wh->industry_sector,
            'city'                => $wh->city,
            'province'            => $wh->province,
            'start_date'          => $wh->start_date?->toDateString(),
            'end_date'            => $wh->end_date?->toDateString(),
            'is_current'          => (bool) $wh->is_current,
            'salary_range_id'     => $wh->salary_range_id,
            'job_relevance'       => $wh->job_relevance,
            'waiting_time_months' => $wh->waiting_time_months,
            'description'         => $wh->description,
        ];
    }
}

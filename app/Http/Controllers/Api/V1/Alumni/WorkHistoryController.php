<?php

namespace App\Http\Controllers\Api\V1\Alumni;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Models\AlumniWorkHistory;
use App\Models\AuditLog;
use App\Repositories\AlumniRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Alumni\WorkHistoryController
 * Endpoint: /api/v1/alumni/work-histories (alumni self-access)
 *           /api/v1/admin/alumni/{alumni}/work-histories (admin read-only)
 */
class WorkHistoryController extends Controller
{
    public function __construct(
        private readonly AlumniRepository $repo,
    ) {}

    // ─── GET /api/v1/alumni/work-histories ───────────────────────────────────

    /**
     * Daftar riwayat pekerjaan milik alumni yang login.
     */
    public function index(Request $request): JsonResponse
    {
        $alumni = $this->repo->findByUserId($request->user()->id);

        if (!$alumni) {
            return $this->alumniNotFound();
        }

        $histories = $alumni->workHistories()
            ->orderByDesc('start_date')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pekerjaan berhasil diambil',
            'data'    => $histories->map(fn ($wh) => $this->formatWorkHistory($wh))->toArray(),
        ]);
    }

    // ─── GET /api/v1/admin/alumni/{alumni}/work-histories ────────────────────

    /**
     * Lihat riwayat pekerjaan alumni tertentu (admin view).
     */
    public function indexForAdmin(Alumni $alumni): JsonResponse
    {
        Gate::authorize('view', $alumni);

        $histories = $alumni->workHistories()
            ->orderByDesc('start_date')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pekerjaan alumni berhasil diambil',
            'data'    => $histories->map(fn ($wh) => $this->formatWorkHistory($wh))->toArray(),
        ]);
    }

    // ─── POST /api/v1/alumni/work-histories/{alumni} ─────────────────────────

    /**
     * Tambah riwayat pekerjaan baru.
     * {alumni} di route diisi dari user yang login (alumni self-access).
     */
    public function store(Request $request, Alumni $alumni): JsonResponse
    {
        // Alumni hanya bisa tambah riwayat miliknya sendiri
        if ($request->user()->id !== $alumni->user_id) {
            return response()->json([
                'success'    => false,
                'message'    => 'Anda tidak memiliki akses.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $validated = $request->validate($this->workHistoryRules());

        // Jika is_current = true, set is_current = false untuk semua record lain
        if (!empty($validated['is_current'])) {
            $alumni->workHistories()
                ->where('is_current', true)
                ->update(['is_current' => false]);
        }

        $workHistory = $alumni->workHistories()->create($validated);

        AuditLog::record(
            action   : 'create',
            module   : 'work_history',
            modelId  : $workHistory->id,
            oldValues: null,
            newValues: $workHistory->toArray(),
            modelType: AlumniWorkHistory::class,
        );

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pekerjaan berhasil ditambahkan',
            'data'    => $this->formatWorkHistory($workHistory),
        ], 201);
    }

    // ─── PUT /api/v1/alumni/work-histories/{alumni}/{workHistory} ────────────

    /**
     * Update riwayat pekerjaan.
     */
    public function update(Request $request, Alumni $alumni, AlumniWorkHistory $workHistory): JsonResponse
    {
        // Pastikan workHistory milik alumni yang benar
        if ($workHistory->alumni_id !== $alumni->id) {
            return response()->json([
                'success'    => false,
                'message'    => 'Data tidak ditemukan.',
                'error_code' => 'NOT_FOUND',
            ], 404);
        }

        // Alumni hanya bisa update miliknya sendiri
        if ($request->user()->id !== $alumni->user_id) {
            return response()->json([
                'success'    => false,
                'message'    => 'Anda tidak memiliki akses.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $rules     = $this->workHistoryRules();
        $rulesOpt  = array_map(fn ($r) => array_merge(['sometimes'], (array) $r), $rules);
        $validated = $request->validate($rulesOpt);

        $old = $workHistory->toArray();

        if (!empty($validated['is_current'])) {
            $alumni->workHistories()
                ->where('id', '!=', $workHistory->id)
                ->where('is_current', true)
                ->update(['is_current' => false]);
        }

        $workHistory->update($validated);

        AuditLog::record(
            action   : 'update',
            module   : 'work_history',
            modelId  : $workHistory->id,
            oldValues: $old,
            newValues: $workHistory->fresh()->toArray(),
            modelType: AlumniWorkHistory::class,
        );

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pekerjaan berhasil diperbarui',
            'data'    => $this->formatWorkHistory($workHistory->fresh()),
        ]);
    }

    // ─── DELETE /api/v1/alumni/work-histories/{alumni}/{workHistory} ─────────

    /**
     * Hapus riwayat pekerjaan.
     */
    public function destroy(Request $request, Alumni $alumni, AlumniWorkHistory $workHistory): JsonResponse
    {
        if ($workHistory->alumni_id !== $alumni->id) {
            return response()->json([
                'success'    => false,
                'message'    => 'Data tidak ditemukan.',
                'error_code' => 'NOT_FOUND',
            ], 404);
        }

        if ($request->user()->id !== $alumni->user_id) {
            return response()->json([
                'success'    => false,
                'message'    => 'Anda tidak memiliki akses.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        AuditLog::record(
            action   : 'delete',
            module   : 'work_history',
            modelId  : $workHistory->id,
            oldValues: $workHistory->toArray(),
            newValues: null,
            modelType: AlumniWorkHistory::class,
        );

        $workHistory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pekerjaan berhasil dihapus',
        ]);
    }

    // ─── PRIVATE HELPERS ─────────────────────────────────────────────────────

    /**
     * Aturan validasi riwayat pekerjaan.
     *
     * @return array<string,mixed>
     */
    private function workHistoryRules(): array
    {
        return [
            'company_name'         => ['required', 'string', 'max:255'],
            'position'             => ['required', 'string', 'max:255'],
            'employment_type'      => ['nullable', Rule::in(['full_time', 'part_time', 'freelance', 'internship', 'contract'])],
            'industry_sector'      => ['nullable', 'string', 'max:100'],
            'job_description'      => ['nullable', 'string', 'max:1000'],
            'city'                 => ['nullable', 'string', 'max:100'],
            'province'             => ['nullable', 'string', 'max:100'],
            'country'              => ['nullable', 'string', 'max:100'],
            'salary_range_id'      => ['nullable', 'integer', 'exists:salary_ranges,id'],
            'start_date'           => ['required', 'date'],
            'end_date'             => ['nullable', 'date', 'after:start_date'],
            'is_current'           => ['boolean'],
            'is_relevant_to_study' => ['boolean'],
            'waiting_months'       => ['nullable', 'integer', 'min:0', 'max:120'],
        ];
    }

    /**
     * Format output riwayat pekerjaan.
     *
     * @return array<string,mixed>
     */
    private function formatWorkHistory(AlumniWorkHistory $wh): array
    {
        return [
            'id'                   => $wh->id,
            'company_name'         => $wh->company_name,
            'position'             => $wh->position,
            'employment_type'      => $wh->employment_type,
            'industry_sector'      => $wh->industry_sector,
            'job_description'      => $wh->job_description,
            'city'                 => $wh->city,
            'province'             => $wh->province,
            'country'              => $wh->country,
            'salary_range_id'      => $wh->salary_range_id,
            'start_date'           => $wh->start_date?->toDateString(),
            'end_date'             => $wh->end_date?->toDateString(),
            'is_current'           => (bool) $wh->is_current,
            'is_relevant_to_study' => (bool) $wh->is_relevant_to_study,
            'waiting_months'       => $wh->waiting_months,
        ];
    }

    private function alumniNotFound(): JsonResponse
    {
        return response()->json([
            'success'    => false,
            'message'    => 'Data alumni tidak ditemukan.',
            'error_code' => 'ALUMNI_NOT_FOUND',
        ], 404);
    }
}

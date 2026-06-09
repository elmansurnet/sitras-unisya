<?php

namespace App\Http\Controllers\Api\V1\Alumni;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Models\AlumniWorkHistory;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkHistoryController extends Controller
{
    // ─── GET /alumni/work-histories (self) ────────────────────────────────────
    /**
     * Daftar riwayat pekerjaan milik alumni yang sedang login.
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
     * Alumni hanya boleh tambah untuk dirinya sendiri.
     */
    public function store(Request $request, Alumni $alumni): JsonResponse
    {
        $this->authorizeSelf($request, $alumni);

        $validated = $request->validate($this->rules());

        // Jika is_current = true, reset semua pekerjaan lain
        if (!empty($validated['is_current'])) {
            AlumniWorkHistory::where('alumni_id', $alumni->id)
                ->update(['is_current' => false]);
        }

        $workHistory = AlumniWorkHistory::create(array_merge(
            $validated,
            ['alumni_id' => $alumni->id]
        ));

        AuditLog::record(
            action   : 'create_work_history',
            module   : 'alumni',
            modelId  : $alumni->id,
            oldValues: null,
            newValues: ['work_history_id' => $workHistory->id, 'company' => $workHistory->company_name],
            modelType: Alumni::class,
        );

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pekerjaan berhasil ditambahkan',
            'data'    => $this->formatWorkHistory($workHistory),
        ], 201);
    }

    // ─── PUT /alumni/work-histories/{alumni}/{workHistory} ────────────────────
    public function update(Request $request, Alumni $alumni, AlumniWorkHistory $workHistory): JsonResponse
    {
        $this->authorizeSelf($request, $alumni);
        $this->authorizeOwnership($alumni, $workHistory);

        $validated = $request->validate($this->rules(update: true));

        if (!empty($validated['is_current'])) {
            AlumniWorkHistory::where('alumni_id', $alumni->id)
                ->where('id', '!=', $workHistory->id)
                ->update(['is_current' => false]);
        }

        $workHistory->update($validated);

        AuditLog::record(
            action   : 'update_work_history',
            module   : 'alumni',
            modelId  : $alumni->id,
            oldValues: ['work_history_id' => $workHistory->id],
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
    public function destroy(Request $request, Alumni $alumni, AlumniWorkHistory $workHistory): JsonResponse
    {
        $this->authorizeSelf($request, $alumni);
        $this->authorizeOwnership($alumni, $workHistory);

        AuditLog::record(
            action   : 'delete_work_history',
            module   : 'alumni',
            modelId  : $alumni->id,
            oldValues: ['work_history_id' => $workHistory->id, 'company' => $workHistory->company_name],
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
     * Pastikan alumni yang diakses adalah milik user yang login.
     */
    private function authorizeSelf(Request $request, Alumni $alumni): void
    {
        if ($alumni->user_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses data ini.');
        }
    }

    /**
     * Pastikan work history milik alumni yang dimaksud.
     */
    private function authorizeOwnership(Alumni $alumni, AlumniWorkHistory $workHistory): void
    {
        if ($workHistory->alumni_id !== $alumni->id) {
            abort(403, 'Riwayat pekerjaan ini tidak milik alumni yang dimaksud.');
        }
    }

    /**
     * Validation rules untuk store/update work history.
     *
     * @return array<string, mixed>
     */
    private function rules(bool $update = false): array
    {
        $required = $update ? 'sometimes' : 'required';

        return [
            'company_name'      => [$required, 'string', 'max:200'],
            'position'          => [$required, 'string', 'max:200'],
            'employment_type'   => [$required, Rule::in(['penuh_waktu', 'paruh_waktu', 'kontrak', 'magang', 'wirausaha'])],
            'industry_sector'   => ['nullable', 'string', 'max:100'],
            'city'              => ['nullable', 'string', 'max:100'],
            'province'          => ['nullable', 'string', 'max:100'],
            'start_date'        => [$required, 'date'],
            'end_date'          => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_current'        => ['boolean'],
            'salary_range_id'   => ['nullable', 'integer', 'exists:salary_ranges,id'],
            'job_relevance'     => ['nullable', Rule::in(['sangat_relevan', 'relevan', 'kurang_relevan', 'tidak_relevan'])],
            'waiting_time_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'description'       => ['nullable', 'string', 'max:1000'],
        ];
    }

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

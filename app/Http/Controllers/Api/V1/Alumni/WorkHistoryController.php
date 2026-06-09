<?php

namespace App\Http\Controllers\Api\V1\Alumni;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use App\Models\AlumniWorkHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class WorkHistoryController extends Controller
{
    // ─── GET /api/v1/alumni/work-histories ────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $alumni = Alumni::where('user_id', $request->user()->id)->firstOrFail();

        $histories = $alumni->workHistories()
            ->orderByDesc('start_date')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $histories,
        ]);
    }

    // ─── POST /api/v1/alumni/work-histories ───────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $alumni = Alumni::where('user_id', $request->user()->id)->firstOrFail();

        // Batasi: maksimal 20 riwayat per alumni
        if ($alumni->workHistories()->count() >= 20) {
            return response()->json([
                'success' => false,
                'message' => 'Maksimal 20 riwayat pekerjaan per alumni.',
            ], 422);
        }

        $history = $alumni->workHistories()->create(array_merge($data, [
            'alumni_id' => $alumni->id,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pekerjaan berhasil ditambahkan.',
            'data'    => $history,
        ], 201);
    }

    // ─── PUT /api/v1/alumni/work-histories/{workHistory} ──────────────────────
    public function update(Request $request, AlumniWorkHistory $workHistory): JsonResponse
    {
        // Pastikan riwayat ini milik alumni yang sedang login
        $alumni = Alumni::where('user_id', $request->user()->id)->firstOrFail();

        if ($workHistory->alumni_id !== $alumni->id) {
            abort(403, 'Akses ditolak.');
        }

        $data = $request->validate($this->rules(update: true));
        $workHistory->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pekerjaan berhasil diperbarui.',
            'data'    => $workHistory->fresh(),
        ]);
    }

    // ─── DELETE /api/v1/alumni/work-histories/{workHistory} ───────────────────
    public function destroy(Request $request, AlumniWorkHistory $workHistory): JsonResponse
    {
        $alumni = Alumni::where('user_id', $request->user()->id)->firstOrFail();

        if ($workHistory->alumni_id !== $alumni->id) {
            abort(403, 'Akses ditolak.');
        }

        $workHistory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pekerjaan berhasil dihapus.',
        ]);
    }

    /**
     * Aturan validasi riwayat pekerjaan.
     *
     * @return array<string,mixed>
     */
    private function rules(bool $update = false): array
    {
        $sometimes = $update ? 'sometimes' : 'required';

        return [
            'company_name'    => [$sometimes, 'string', 'max:150'],
            'position'        => [$sometimes, 'string', 'max:100'],
            'industry_sector_id' => ['nullable', 'integer', 'exists:industry_sectors,id'],
            'employment_type' => ['nullable', Rule::in(['full_time', 'part_time', 'contract', 'internship', 'freelance'])],
            'city'            => ['nullable', 'string', 'max:100'],
            'province'        => ['nullable', 'string', 'max:100'],
            'country'         => ['nullable', 'string', 'max:100'],
            'salary_range_id' => ['nullable', 'integer', 'exists:salary_ranges,id'],
            'start_date'      => [$sometimes, 'date'],
            'end_date'        => ['nullable', 'date', 'after:start_date'],
            'is_current'      => ['boolean'],
            'description'     => ['nullable', 'string', 'max:1000'],
            'match_with_major'=> ['nullable', Rule::in(['very_relevant', 'relevant', 'less_relevant', 'not_relevant'])],
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyPeriod;
use App\Services\SurveyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SurveyPeriodController extends Controller
{
    public function __construct(
        protected SurveyService $service,
    ) {}

    /**
     * GET /api/v1/admin/survey-periods
     * List semua periode survei dengan filter status.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('admin-or-superadmin');

        $request->validate([
            'status'   => ['nullable', 'in:draft,active,closed'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = SurveyPeriod::query()
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->withCount([
                'responses',
                'responses as submitted_count' => fn ($q) => $q->where('status', 'submitted'),
            ])
            ->latest();

        $result = $query->paginate((int) $request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $result->items(),
            'meta'    => [
                'current_page' => $result->currentPage(),
                'last_page'    => $result->lastPage(),
                'per_page'     => $result->perPage(),
                'total'        => $result->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/admin/survey-periods
     * Buat periode survei baru (status: draft).
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('admin-or-superadmin');

        $data = $request->validate([
            'name'                    => ['required', 'string', 'max:255'],
            'description'             => ['nullable', 'string'],
            'start_date'              => ['required', 'date', 'before_or_equal:end_date'],
            'end_date'                => ['required', 'date', 'after_or_equal:start_date'],
            'target_graduation_years' => ['required', 'array', 'min:1'],
            'target_graduation_years.*' => ['integer', 'min:1990', 'max:2100'],
            'send_wa'                 => ['boolean'],
            'send_email'              => ['boolean'],
        ]);

        $period = $this->service->openPeriod($data, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Periode survei berhasil dibuat.',
            'data'    => $period,
        ], 201);
    }

    /**
     * GET /api/v1/admin/survey-periods/{period}
     */
    public function show(int $id): JsonResponse
    {
        $this->authorize('admin-or-superadmin');

        $period = SurveyPeriod::withCount([
            'responses',
            'responses as submitted_count' => fn ($q) => $q->where('status', 'submitted'),
            'responses as draft_count'     => fn ($q) => $q->where('status', 'draft'),
        ])->find($id);

        if (! $period) {
            return response()->json(['success' => false, 'message' => 'Periode survei tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $period,
        ]);
    }

    /**
     * PUT /api/v1/admin/survey-periods/{period}
     * Hanya boleh edit jika masih draft.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorize('admin-or-superadmin');

        $period = SurveyPeriod::find($id);

        if (! $period) {
            return response()->json(['success' => false, 'message' => 'Periode survei tidak ditemukan.'], 404);
        }

        if ($period->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Periode yang sudah aktif atau ditutup tidak dapat diedit.',
            ], 422);
        }

        $data = $request->validate([
            'name'                      => ['sometimes', 'string', 'max:255'],
            'description'               => ['nullable', 'string'],
            'start_date'                => ['sometimes', 'date'],
            'end_date'                  => ['sometimes', 'date', 'after_or_equal:start_date'],
            'target_graduation_years'   => ['sometimes', 'array', 'min:1'],
            'target_graduation_years.*' => ['integer', 'min:1990', 'max:2100'],
            'send_wa'                   => ['boolean'],
            'send_email'                => ['boolean'],
        ]);

        $period->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Periode survei berhasil diperbarui.',
            'data'    => $period->fresh(),
        ]);
    }

    /**
     * DELETE /api/v1/admin/survey-periods/{period}
     * Hanya boleh hapus jika masih draft dan belum ada respons.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->authorize('superadmin-only');

        $period = SurveyPeriod::withCount('responses')->find($id);

        if (! $period) {
            return response()->json(['success' => false, 'message' => 'Periode survei tidak ditemukan.'], 404);
        }

        if ($period->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya periode berstatus draft yang dapat dihapus.',
            ], 422);
        }

        if ($period->responses_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus periode yang sudah memiliki respons survei.',
            ], 422);
        }

        $period->delete();

        return response()->json([
            'success' => true,
            'message' => 'Periode survei berhasil dihapus.',
        ]);
    }

    /**
     * POST /api/v1/admin/survey-periods/{period}/activate
     * Aktifkan periode (draft → active).
     */
    public function activate(Request $request, int $id): JsonResponse
    {
        $this->authorize('admin-or-superadmin');

        $period = SurveyPeriod::find($id);

        if (! $period) {
            return response()->json(['success' => false, 'message' => 'Periode survei tidak ditemukan.'], 404);
        }

        if ($period->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya periode berstatus draft yang dapat diaktifkan.',
            ], 422);
        }

        $updated = $this->service->activatePeriod($period, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Periode survei berhasil diaktifkan.',
            'data'    => $updated,
        ]);
    }

    /**
     * POST /api/v1/admin/survey-periods/{period}/close
     * Tutup periode survei secara manual.
     */
    public function close(Request $request, int $id): JsonResponse
    {
        $this->authorize('admin-or-superadmin');

        $period = SurveyPeriod::find($id);

        if (! $period) {
            return response()->json(['success' => false, 'message' => 'Periode survei tidak ditemukan.'], 404);
        }

        if ($period->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya periode aktif yang dapat ditutup.',
            ], 422);
        }

        $updated = $this->service->closePeriod($period, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Periode survei berhasil ditutup.',
            'data'    => $updated,
        ]);
    }

    /**
     * POST /api/v1/admin/survey-periods/{period}/send-blast
     * Kirim blast undangan survei ke alumni target.
     * questionnaire_id dipilih di sini, TIDAK disimpan di survey_periods.
     */
    public function sendBlast(Request $request, int $id): JsonResponse
    {
        $this->authorize('admin-or-superadmin');

        $period = SurveyPeriod::find($id);

        if (! $period) {
            return response()->json(['success' => false, 'message' => 'Periode survei tidak ditemukan.'], 404);
        }

        if ($period->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Blast hanya dapat dikirim pada periode yang sedang aktif.',
            ], 422);
        }

        $data = $request->validate([
            'questionnaire_id' => ['required', 'integer', 'exists:questionnaires,id'],
            'send_wa'          => ['boolean'],
            'send_email'       => ['boolean'],
        ]);

        $this->service->sendBlast(
            period         : $period,
            questionnaireId: $data['questionnaire_id'],
            actorId        : $request->user()->id,
            options        : [
                'send_wa'    => $data['send_wa']    ?? $period->send_wa,
                'send_email' => $data['send_email'] ?? $period->send_email,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Blast undangan survei sedang diproses di queue.',
        ]);
    }

    /**
     * GET /api/v1/admin/survey-periods/{period}/stats
     * Statistik pengisian survei untuk satu periode.
     */
    public function stats(int $id): JsonResponse
    {
        $this->authorize('admin-or-superadmin');

        $period = SurveyPeriod::find($id);

        if (! $period) {
            return response()->json(['success' => false, 'message' => 'Periode survei tidak ditemukan.'], 404);
        }

        $total     = $period->responses()->count();
        $submitted = $period->responses()->where('status', 'submitted')->count();
        $draft     = $period->responses()->where('status', 'draft')->count();

        $byType = $period->responses()
            ->selectRaw('respondent_type, count(*) as total')
            ->groupBy('respondent_type')
            ->pluck('total', 'respondent_type');

        return response()->json([
            'success' => true,
            'data'    => [
                'period_id'         => $period->id,
                'period_name'       => $period->name,
                'status'            => $period->status,
                'total_responses'   => $total,
                'submitted'         => $submitted,
                'draft'             => $draft,
                'completion_rate'   => $total > 0 ? round(($submitted / $total) * 100, 2) : 0.0,
                'by_respondent'     => $byType,
            ],
        ]);
    }
}

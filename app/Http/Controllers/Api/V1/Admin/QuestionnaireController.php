<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Questionnaire\ReorderQuestionsRequest;
use App\Http\Requests\Questionnaire\StoreQuestionnaireRequest;
use App\Http\Requests\Questionnaire\UpdateQuestionnaireRequest;
use App\Models\Questionnaire;
use App\Services\QuestionnaireService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use LogicException;

class QuestionnaireController extends Controller
{
    public function __construct(
        private readonly QuestionnaireService $service,
    ) {}

    /**
     * GET /api/v1/admin/questionnaires
     * Daftar kuesioner dengan filter type, status, search.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Questionnaire::class);

        $paginator = $this->service->paginate([
            'type'     => $request->query('type'),
            'status'   => $request->query('status'),
            'search'   => $request->query('search'),
            'per_page' => (int) $request->query('per_page', 15),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $paginator->items(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/admin/questionnaires/stats
     * Statistik ringkasan: total per status, total per type.
     */
    public function stats(): JsonResponse
    {
        Gate::authorize('viewAny', Questionnaire::class);

        $stats = [
            'by_status' => Questionnaire::selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'by_type'   => Questionnaire::selectRaw('type, COUNT(*) as total')
                ->groupBy('type')
                ->pluck('total', 'type'),
            'total'     => Questionnaire::count(),
        ];

        return response()->json([
            'success' => true,
            'data'    => $stats,
        ]);
    }

    /**
     * POST /api/v1/admin/questionnaires
     */
    public function store(StoreQuestionnaireRequest $request): JsonResponse
    {
        Gate::authorize('create', Questionnaire::class);

        $questionnaire = $this->service->create(
            $request->validated(),
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Kuesioner berhasil dibuat.',
            'data'    => $questionnaire,
        ], 201);
    }

    /**
     * GET /api/v1/admin/questionnaires/{questionnaire}
     * Mengembalikan struktur lengkap: sections > questions > options.
     */
    public function show(Questionnaire $questionnaire): JsonResponse
    {
        Gate::authorize('view', $questionnaire);

        return response()->json([
            'success' => true,
            'data'    => $this->service->getWithStructure($questionnaire->id),
        ]);
    }

    /**
     * PUT /api/v1/admin/questionnaires/{questionnaire}
     */
    public function update(UpdateQuestionnaireRequest $request, Questionnaire $questionnaire): JsonResponse
    {
        Gate::authorize('update', $questionnaire);

        $questionnaire = $this->service->update(
            $questionnaire,
            $request->validated(),
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Kuesioner berhasil diperbarui.',
            'data'    => $questionnaire,
        ]);
    }

    /**
     * DELETE /api/v1/admin/questionnaires/{questionnaire}
     */
    public function destroy(Request $request, Questionnaire $questionnaire): JsonResponse
    {
        Gate::authorize('delete', $questionnaire);

        try {
            $this->service->delete($questionnaire, $request->user()->id);
        } catch (LogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kuesioner berhasil dihapus.',
        ]);
    }

    /**
     * PATCH /api/v1/admin/questionnaires/{questionnaire}/publish
     * Transisi status: draft -> aktif.
     */
    public function publish(Request $request, Questionnaire $questionnaire): JsonResponse
    {
        Gate::authorize('publish', $questionnaire);

        try {
            $questionnaire = $this->service->publish($questionnaire, $request->user()->id);
        } catch (LogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kuesioner berhasil dipublikasikan.',
            'data'    => $questionnaire,
        ]);
    }

    /**
     * PATCH /api/v1/admin/questionnaires/{questionnaire}/archive
     * Transisi status: aktif -> arsip.
     */
    public function archive(Request $request, Questionnaire $questionnaire): JsonResponse
    {
        Gate::authorize('archive', $questionnaire);

        try {
            $questionnaire = $this->service->archive($questionnaire, $request->user()->id);
        } catch (LogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kuesioner berhasil diarsipkan.',
            'data'    => $questionnaire,
        ]);
    }

    /**
     * POST /api/v1/admin/questionnaires/{questionnaire}/duplicate
     * Duplikasi kuesioner beserta seluruh strukturnya.
     */
    public function duplicate(Request $request, Questionnaire $questionnaire): JsonResponse
    {
        Gate::authorize('create', Questionnaire::class);

        $newQuestionnaire = $this->service->duplicate($questionnaire, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Kuesioner berhasil diduplikasi.',
            'data'    => $newQuestionnaire,
        ], 201);
    }

    /**
     * PATCH /api/v1/admin/questionnaires/{questionnaire}/reorder
     * Urutkan ulang questions atau sections.
     */
    public function reorder(ReorderQuestionsRequest $request, Questionnaire $questionnaire): JsonResponse
    {
        Gate::authorize('reorder', $questionnaire);

        $this->service->reorder($questionnaire, $request->validated(), $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Urutan berhasil diperbarui.',
        ]);
    }
}

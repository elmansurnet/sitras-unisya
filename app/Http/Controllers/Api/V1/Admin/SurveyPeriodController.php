<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyPeriod;
use App\Services\SurveyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SurveyPeriodController extends Controller
{
    public function __construct(
        protected SurveyService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('admin-or-superadmin');

        $request->validate([
            'status'   => ['nullable', 'in:draft,active,closed'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = SurveyPeriod::query()
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->withCount([
                'surveyResponses',
                'surveyResponses as submitted_count' => fn ($q) => $q->where('status', 'submitted'),
                'alumni as target_alumni_count',
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
}

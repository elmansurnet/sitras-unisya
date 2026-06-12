<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employer\StoreEmployerRequest;
use App\Http\Requests\Employer\UpdateEmployerRequest;
use App\Models\Employer;
use App\Services\EmployerService;
use App\Repositories\Contracts\EmployerRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmployerController extends Controller
{
    public function __construct(
        protected EmployerService $service,
        protected EmployerRepositoryInterface $repository
    ) {}

    /**
     * GET /api/v1/admin/employers
     * List employers dengan filter & pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Employer::class);

        $request->validate([
            'search'          => ['nullable', 'string', 'max:100'],
            'company_type'    => ['nullable', 'in:swasta,bumn,pemerintah,ngo,startup,lainnya'],
            'industry_sector' => ['nullable', 'string', 'max:100'],
            'survey_status'   => ['nullable', 'in:belum_disurvei,terkirim,selesai'],
            'address_city'    => ['nullable', 'string', 'max:100'],
            'sort_by'         => ['nullable', 'in:company_name,created_at,survey_status'],
            'sort_dir'        => ['nullable', 'in:asc,desc'],
            'per_page'        => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->repository->findWithFilters(
            $request->only(['search', 'company_type', 'industry_sector', 'survey_status', 'address_city', 'sort_by', 'sort_dir']),
            (int) $request->get('per_page', 15)
        );

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
     * POST /api/v1/admin/employers
     */
    public function store(StoreEmployerRequest $request): JsonResponse
    {
        $this->authorize('create', Employer::class);

        $employer = $this->service->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Employer berhasil ditambahkan.',
            'data'    => $employer,
        ], 201);
    }

    /**
     * GET /api/v1/admin/employers/{employer}
     */
    public function show(int $id): JsonResponse
    {
        $employer = $this->repository->findById($id);

        if (! $employer) {
            return response()->json(['success' => false, 'message' => 'Employer tidak ditemukan.'], 404);
        }

        $this->authorize('view', $employer);

        return response()->json([
            'success' => true,
            'data'    => $this->buildDetailResource($employer),
        ]);
    }

    /**
     * PUT /api/v1/admin/employers/{employer}
     */
    public function update(UpdateEmployerRequest $request, int $id): JsonResponse
    {
        $employer = Employer::find($id);

        if (! $employer) {
            return response()->json(['success' => false, 'message' => 'Employer tidak ditemukan.'], 404);
        }

        $this->authorize('update', $employer);

        $updated = $this->service->update($employer, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Employer berhasil diperbarui.',
            'data'    => $updated,
        ]);
    }

    /**
     * DELETE /api/v1/admin/employers/{employer}
     * Hanya superadmin. Admin mendapat 403.
     */
    public function destroy(int $id): JsonResponse
    {
        $employer = Employer::find($id);

        if (! $employer) {
            return response()->json(['success' => false, 'message' => 'Employer tidak ditemukan.'], 404);
        }

        $this->authorize('delete', $employer);

        $this->service->delete($employer);

        return response()->json([
            'success' => true,
            'message' => 'Employer berhasil dihapus.',
        ]);
    }

    /**
     * POST /api/v1/admin/employers/{employer}/send-survey-token
     */
    public function sendSurveyToken(Request $request, int $id): JsonResponse
    {
        $employer = Employer::find($id);

        if (! $employer) {
            return response()->json(['success' => false, 'message' => 'Employer tidak ditemukan.'], 404);
        }

        $this->authorize('sendSurveyToken', $employer);

        $request->validate([
            'channel' => ['required', 'in:whatsapp,email'],
        ]);

        try {
            $this->service->sendSurveyToken($employer, $request->channel);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->errors()['employer'][0] ?? 'Gagal mengirim token.',
            ], 422);
        }

        $employer->refresh();

        return response()->json([
            'success'    => true,
            'message'    => 'Token survei berhasil dikirim.',
            'data'       => [
                'survey_status'           => $employer->survey_status,
                'survey_token_expires_at' => $employer->survey_token_expires_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * POST /api/v1/admin/employers/{employer}/regenerate-token
     */
    public function regenerateToken(Request $request, int $id): JsonResponse
    {
        $employer = Employer::find($id);

        if (! $employer) {
            return response()->json(['success' => false, 'message' => 'Employer tidak ditemukan.'], 404);
        }

        $this->authorize('regenerateToken', $employer);

        try {
            $this->service->regenerateToken($employer);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->errors()['employer'][0] ?? 'Gagal regenerate token.',
            ], 422);
        }

        $employer->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Token survei berhasil di-regenerate.',
            'data'    => [
                'survey_status'           => $employer->survey_status,
                'survey_token_expires_at' => $employer->survey_token_expires_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Build resource array untuk detail view.
     * survey_token TIDAK diekspos ke response (keamanan).
     */
    private function buildDetailResource(Employer $employer): array
    {
        return [
            'id'                      => $employer->id,
            'user_id'                 => $employer->user_id,
            'company_name'            => $employer->company_name,
            'company_type'            => $employer->company_type,
            'industry_sector'         => $employer->industry_sector,
            'company_scale'           => $employer->company_scale,
            'address_street'          => $employer->address_street,
            'address_city'            => $employer->address_city,
            'address_province'        => $employer->address_province,
            'address_country'         => $employer->address_country,
            'phone'                   => $employer->phone,
            'email'                   => $employer->email,
            'website'                 => $employer->website,
            'contact_person_name'     => $employer->contact_person_name,
            'contact_person_position' => $employer->contact_person_position,
            'contact_person_email'    => $employer->contact_person_email,
            'contact_person_phone'    => $employer->contact_person_phone,
            'survey_status'           => $employer->survey_status,
            'survey_token_expires_at' => $employer->survey_token_expires_at?->toIso8601String(),
            'survey_token_used_at'    => $employer->survey_token_used_at?->toIso8601String(),
            'logo'                    => $employer->logo,
            'notes'                   => $employer->notes,
            'created_at'              => $employer->created_at?->toIso8601String(),
            'updated_at'              => $employer->updated_at?->toIso8601String(),
            'user'                    => $employer->user ? [
                'id'    => $employer->user->id,
                'name'  => $employer->user->name,
                'email' => $employer->user->email,
                'phone' => $employer->user->phone,
            ] : null,
            'alumni'                  => $employer->alumni->map(fn ($a) => [
                'id'       => $a->id,
                'nim'      => $a->nim,
                'fullname' => $a->fullname,
                'pivot'    => ['is_verified' => (bool) $a->pivot->is_verified],
            ]),
        ];
    }
}

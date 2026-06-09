<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\ImportAlumniRequest;
use App\Http\Requests\Alumni\SendInvitationRequest;
use App\Http\Requests\Alumni\StoreAlumniRequest;
use App\Http\Requests\Alumni\UpdateAlumniRequest;
use App\Models\Alumni;
use App\Policies\AlumniPolicy;
use App\Repositories\AlumniRepository;
use App\Services\AlumniService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Admin\AlumniController
 * Endpoint: /api/v1/admin/alumni/*
 * Middleware (dipasang di routes/api.php):
 *   auth:sanctum → EnsureAccountActive → CheckRole:superadmin,admin → LogActivity
 */
class AlumniController extends Controller
{
    public function __construct(
        private readonly AlumniService    $service,
        private readonly AlumniRepository $repo,
    ) {}

    // ─── GET /api/v1/admin/alumni ─────────────────────────────────────────────

    /**
     * Daftar alumni dengan filter & paginasi.
     * Sesuai 05_API.md §3.1
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Alumni::class);

        $paginator = $this->repo->paginate($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Data alumni berhasil diambil',
            'data'    => $paginator->items(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'links'   => [
                'first' => $paginator->url(1),
                'last'  => $paginator->url($paginator->lastPage()),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
            ],
        ]);
    }

    // ─── GET /api/v1/admin/alumni/{alumni} ────────────────────────────────────

    /**
     * Detail 1 alumni.
     * Sesuai 05_API.md §3.2
     */
    public function show(Alumni $alumni): JsonResponse
    {
        Gate::authorize('view', $alumni);

        $alumni = $this->repo->findWithRelations($alumni->id);

        return response()->json([
            'success' => true,
            'message' => 'Data alumni berhasil diambil',
            'data'    => $this->formatDetail($alumni),
        ]);
    }

    // ─── POST /api/v1/admin/alumni ────────────────────────────────────────────

    /**
     * Tambah alumni baru.
     * Sesuai 05_API.md §3.3
     */
    public function store(StoreAlumniRequest $request): JsonResponse
    {
        Gate::authorize('create', Alumni::class);

        $alumni = $this->service->create(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Data alumni berhasil ditambahkan',
            'data'    => [
                'id'        => $alumni->id,
                'nim'       => $alumni->nim,
                'full_name' => $alumni->full_name,
            ],
        ], 201);
    }

    // ─── PUT /api/v1/admin/alumni/{alumni} ────────────────────────────────────

    /**
     * Update alumni.
     * Sesuai 05_API.md §3.4
     */
    public function update(UpdateAlumniRequest $request, Alumni $alumni): JsonResponse
    {
        Gate::authorize('update', $alumni);

        $updated = $this->service->update(
            $alumni,
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Data alumni berhasil diperbarui',
            'data'    => $this->formatDetail($updated),
        ]);
    }

    // ─── DELETE /api/v1/admin/alumni/{alumni} ─────────────────────────────────

    /**
     * Soft-delete alumni. Superadmin only.
     * Sesuai 05_API.md §3.5
     */
    public function destroy(Request $request, Alumni $alumni): JsonResponse
    {
        Gate::authorize('delete', $alumni);

        $this->service->delete($alumni, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Data alumni berhasil dihapus',
        ]);
    }

    // ─── POST /api/v1/admin/alumni/import ────────────────────────────────────

    /**
     * Import alumni dari Excel.
     * Sesuai 05_API.md §3.6
     */
    public function import(ImportAlumniRequest $request): JsonResponse
    {
        Gate::authorize('import', Alumni::class);

        // Override study_program_id & graduation_year_id dari form jika ada
        $result = $this->service->import(
            $request->file('file'),
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Import selesai',
            'data'    => [
                'total_rows' => $result['success'] + $result['failed'],
                'imported'   => $result['success'],
                'skipped'    => 0,
                'failed'     => $result['failed'],
                'errors'     => $result['errors'],
            ],
        ]);
    }

    // ─── GET /api/v1/admin/alumni/export ─────────────────────────────────────

    /**
     * Export alumni ke Excel (via queue).
     * Sesuai 05_API.md §3.7
     * Rate limit: 5/5menit (dipasang di RouteServiceProvider)
     */
    public function export(Request $request): JsonResponse
    {
        Gate::authorize('export', Alumni::class);

        $filename = $this->service->export(
            $request->only([
                'search', 'study_program_id', 'graduation_year_id',
                'survey_status', 'gender',
            ]),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Export sedang diproses. File akan tersedia dalam beberapa saat.',
            'data'    => ['filename' => $filename],
        ]);
    }

    // ─── GET /api/v1/admin/alumni/template ───────────────────────────────────

    /**
     * Download template Excel untuk import.
     * Sesuai 05_API.md §3.8
     */
    public function importTemplate(Request $request): JsonResponse
    {
        Gate::authorize('import', Alumni::class);

        $path = $this->service->generateImportTemplate();

        return response()->json([
            'success' => true,
            'message' => 'Template berhasil di-generate',
            'data'    => ['download_path' => $path],
        ]);
    }

    // ─── GET /api/v1/admin/alumni/stats ──────────────────────────────────────

    /**
     * Statistik ringkasan alumni.
     */
    public function stats(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Alumni::class);

        return response()->json([
            'success' => true,
            'message' => 'Statistik alumni berhasil diambil',
            'data'    => $this->repo->getStats(),
        ]);
    }

    // ─── POST /api/v1/admin/alumni/{alumni}/invite ───────────────────────────

    /**
     * Kirim undangan survei ke alumni.
     * Sesuai 05_API.md §3.9
     */
    public function sendInvitation(SendInvitationRequest $request, Alumni $alumni): JsonResponse
    {
        Gate::authorize('sendInvitation', Alumni::class);

        $this->service->sendInvitation(
            $alumni,
            $request->validated('questionnaire_id'),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Undangan survei berhasil dikirim ke alumni',
        ]);
    }

    // ─── PRIVATE HELPERS ─────────────────────────────────────────────────────

    /**
     * Format detail alumni sesuai 05_API.md §3.2
     *
     * @param  Alumni|null $alumni
     * @return array<string,mixed>|null
     */
    private function formatDetail(?Alumni $alumni): ?array
    {
        if (!$alumni) {
            return null;
        }

        return [
            'id'                   => $alumni->id,
            'nim'                  => $alumni->nim,
            'nik'                  => $alumni->nik,
            'full_name'            => $alumni->full_name,
            'gender'               => $alumni->gender,
            'birth_place'          => $alumni->birth_place,
            'birth_date'           => $alumni->birth_date?->toDateString(),
            'study_program'        => $alumni->studyProgram ? [
                'id'           => $alumni->studyProgram->id,
                'name'         => $alumni->studyProgram->name,
                'code'         => $alumni->studyProgram->code,
                'degree_level' => $alumni->studyProgram->degree_level,
            ] : null,
            'graduation_year'      => $alumni->graduationYear ? [
                'id'            => $alumni->graduationYear->id,
                'year'          => $alumni->graduationYear->year,
                'academic_year' => $alumni->graduationYear->academic_year,
                'semester'      => $alumni->graduationYear->semester,
            ] : null,
            'thesis_title'         => $alumni->thesis_title,
            'gpa'                  => $alumni->gpa,        // number, bukan string
            'graduation_predicate' => $alumni->graduation_predicate,
            'address'              => [
                'street'       => $alumni->address_street,
                'village'      => $alumni->address_village,
                'district'     => $alumni->address_district,
                'city'         => $alumni->address_city,
                'province'     => $alumni->address_province,
                'postal_code'  => $alumni->address_postal_code,
                'latitude'     => $alumni->address_latitude,   // number
                'longitude'    => $alumni->address_longitude,  // number
            ],
            'phone'                => $alumni->user?->phone,
            'email'                => $alumni->user?->email,
            'linkedin_url'         => $alumni->linkedin_url,
            'photo_url'            => $alumni->photo_path
                ? \Illuminate\Support\Facades\Storage::temporaryUrl($alumni->photo_path, now()->addHours(1))
                : null,
            'survey_status'        => $alumni->survey_status,
            'work_histories'       => $alumni->workHistories?->map(fn ($wh) => [
                'id'           => $wh->id,
                'company_name' => $wh->company_name,
                'position'     => $wh->position,
                'is_current'   => $wh->is_current,
            ])->toArray(),
            'survey_responses'     => $alumni->surveyResponses?->map(fn ($sr) => [
                'id'           => $sr->id,
                'status'       => $sr->status,
                'submitted_at' => $sr->submitted_at?->toIso8601String(),
            ])->toArray(),
            'created_at'           => $alumni->created_at?->toIso8601String(),
            'updated_at'           => $alumni->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\ImportAlumniRequest;
use App\Http\Requests\Alumni\SendInvitationRequest;
use App\Http\Requests\Alumni\StoreAlumniRequest;
use App\Http\Requests\Alumni\UpdateAlumniRequest;
use App\Models\Alumni;
use App\Services\AlumniService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AlumniController extends Controller
{
    public function __construct(
        private readonly AlumniService $alumniService,
    ) {}

    // ─── GET /admin/alumni ────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Alumni::class);

        $paginator = $this->alumniService->paginate($request->only([
            'search', 'study_program_id', 'graduation_year_id',
            'survey_status', 'gender', 'sort_by', 'sort_dir', 'per_page',
        ]));

        $items = $paginator->getCollection()->map(fn(Alumni $a) => [
            'id'              => $a->id,
            'nim'             => $a->nim,
            'full_name'       => $a->full_name,
            'gender'          => $a->gender,
            'study_program'   => $a->studyProgram ? [
                'id'           => $a->studyProgram->id,
                'name'         => $a->studyProgram->name,
                'degree_level' => $a->studyProgram->degree_level,
            ] : null,
            'graduation_year' => $a->graduationYear ? [
                'id'            => $a->graduationYear->id,
                'year'          => $a->graduationYear->year,
                'academic_year' => $a->graduationYear->academic_year,
            ] : null,
            'gpa'             => $a->gpa,
            'phone'           => $a->phone,
            'email'           => $a->user?->email,
            'survey_status'   => $a->survey_status,
            'created_at'      => $a->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data alumni berhasil diambil',
            'data'    => $items,
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

    // ─── GET /admin/alumni/{alumni} ───────────────────────────────────────────
    public function show(Alumni $alumni): JsonResponse
    {
        Gate::authorize('view', $alumni);

        $alumni->load(['user', 'studyProgram.faculty', 'graduationYear', 'workHistories', 'surveyResponses']);

        return response()->json([
            'success' => true,
            'message' => 'Detail alumni berhasil diambil',
            'data'    => $this->formatDetail($alumni),
        ]);
    }

    // ─── POST /admin/alumni ───────────────────────────────────────────────────
    public function store(StoreAlumniRequest $request): JsonResponse
    {
        $alumni = $this->alumniService->create(
            $request->validated(),
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Data alumni berhasil ditambahkan',
            'data'    => ['id' => $alumni->id, 'nim' => $alumni->nim, 'full_name' => $alumni->full_name],
        ], 201);
    }

    // ─── PUT /admin/alumni/{alumni} ───────────────────────────────────────────
    public function update(UpdateAlumniRequest $request, Alumni $alumni): JsonResponse
    {
        $updated = $this->alumniService->update(
            $alumni,
            $request->validated(),
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Data alumni berhasil diperbarui',
            'data'    => $this->formatDetail($updated),
        ]);
    }

    // ─── DELETE /admin/alumni/{alumni} ────────────────────────────────────────
    public function destroy(Request $request, Alumni $alumni): JsonResponse
    {
        Gate::authorize('delete', $alumni);

        $this->alumniService->delete($alumni, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Data alumni berhasil dihapus',
        ]);
    }

    // ─── POST /admin/alumni/import ────────────────────────────────────────────
    public function import(ImportAlumniRequest $request): JsonResponse
    {
        $result = $this->alumniService->import(
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

    /**
     * GET /admin/alumni/export
     *
     * FIX: Sebelumnya dispatch queue job dan return JSON {filename}.
     * Frontend memanggil dengan responseType:'blob' dan mengharapkan file binary langsung.
     * Sekarang menggunakan exportStream() yang return BinaryFileResponse (Excel::download).
     */
    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('export', Alumni::class);

        return $this->alumniService->exportStream(
            $request->only(['study_program_id', 'graduation_year_id', 'survey_status', 'gender']),
            $request->user()->id,
        );
    }

    // ─── GET /admin/alumni/template ───────────────────────────────────────────
    public function importTemplate(Request $request): JsonResponse
    {
        Gate::authorize('import', Alumni::class);

        $filename = $this->alumniService->generateImportTemplate();

        return response()->json([
            'success' => true,
            'message' => 'Template berhasil digenerate',
            'data'    => ['filename' => $filename],
        ]);
    }

    // ─── GET /admin/alumni/stats ──────────────────────────────────────────────
    public function stats(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Alumni::class);

        $stats = $this->alumniService->stats();

        return response()->json([
            'success' => true,
            'message' => 'Statistik alumni berhasil diambil',
            'data'    => $stats,
        ]);
    }

    // ─── POST /admin/alumni/{alumni}/invite ───────────────────────────────────
    public function sendInvitation(SendInvitationRequest $request, Alumni $alumni): JsonResponse
    {
        $this->alumniService->sendInvitation(
            $alumni,
            $request->validated('questionnaire_id'),
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Undangan survei berhasil dikirim ke alumni',
        ]);
    }

    // ─── PRIVATE HELPER ───────────────────────────────────────────────────────

    /**
     * Format alumni detail sesuai response spec 05_API.md §3.2
     *
     * @return array<string,mixed>
     */
    private function formatDetail(Alumni $alumni): array
    {
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
            'gpa'                  => $alumni->gpa,
            'graduation_predicate' => $alumni->graduation_predicate,
            'address'              => [
                'street'      => $alumni->address_street,
                'village'     => $alumni->address_village,
                'district'    => $alumni->address_district,
                'city'        => $alumni->address_city,
                'province'    => $alumni->address_province,
                'postal_code' => $alumni->address_postal_code,
                'latitude'    => $alumni->latitude,
                'longitude'   => $alumni->longitude,
            ],
            'phone'                => $alumni->phone,
            'email'                => $alumni->user?->email,
            'linkedin_url'         => $alumni->linkedin_url,
            'photo_url'            => $alumni->photo_path
                ? \Illuminate\Support\Facades\Storage::temporaryUrl($alumni->photo_path, now()->addHour())
                : null,
            'survey_status'        => $alumni->survey_status,
            'work_histories'       => $alumni->workHistories?->map(fn($wh) => [
                'id'           => $wh->id,
                'company_name' => $wh->company_name,
                'position'     => $wh->position,
                'is_current'   => (bool) $wh->is_current,
            ])->values()->toArray(),
            'survey_responses'     => $alumni->surveyResponses?->map(fn($sr) => [
                'id'           => $sr->id,
                'status'       => $sr->status,
                'submitted_at' => $sr->submitted_at?->toIso8601String(),
            ])->values()->toArray(),
            'created_at'           => $alumni->created_at?->toIso8601String(),
            'updated_at'           => $alumni->updated_at?->toIso8601String(),
        ];
    }
}

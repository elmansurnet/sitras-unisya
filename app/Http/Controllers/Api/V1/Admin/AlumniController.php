<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\StoreAlumniRequest;
use App\Http\Requests\Alumni\UpdateAlumniRequest;
use App\Models\Alumni;
use App\Repositories\AlumniRepository;
use App\Services\AlumniService;
use App\Services\ImportExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AlumniController extends Controller
{
    public function __construct(
        private readonly AlumniRepository  $repo,
        private readonly AlumniService     $service,
        private readonly ImportExportService $importExport,
    ) {}

    /**
     * GET /api/v1/admin/alumni
     * Daftar alumni dengan filter & pagination.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Alumni::class);

        $alumni = $this->repo->findWithFilters(
            filters: $request->only([
                'search', 'study_program_id', 'graduation_year_id',
                'survey_status', 'gender', 'sort_by', 'sort_dir',
            ]),
            perPage: (int) $request->get('per_page', 15),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Daftar alumni berhasil diambil.',
            'data'    => $alumni,
        ]);
    }

    /**
     * POST /api/v1/admin/alumni
     * Tambah alumni baru.
     */
    public function store(StoreAlumniRequest $request): JsonResponse
    {
        $alumni = $this->service->create($request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Data alumni berhasil ditambahkan.',
            'data'    => $alumni,
        ], 201);
    }

    /**
     * GET /api/v1/admin/alumni/{alumni}
     * Detail alumni.
     */
    public function show(Alumni $alumni): JsonResponse
    {
        Gate::authorize('view', $alumni);

        $alumni->load([
            'user:id,email,phone,is_active,last_login_at',
            'studyProgram:id,name,code,degree_level',
            'studyProgram.faculty:id,name,code',
            'graduationYear:id,year,semester,academic_year',
            'workHistories',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail alumni berhasil diambil.',
            'data'    => $alumni,
        ]);
    }

    /**
     * PUT /api/v1/admin/alumni/{alumni}
     * Update data alumni.
     */
    public function update(UpdateAlumniRequest $request, Alumni $alumni): JsonResponse
    {
        $alumni = $this->service->update($alumni, $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Data alumni berhasil diperbarui.',
            'data'    => $alumni,
        ]);
    }

    /**
     * DELETE /api/v1/admin/alumni/{alumni}
     * Soft-delete alumni.
     */
    public function destroy(Alumni $alumni): JsonResponse
    {
        Gate::authorize('delete', $alumni);

        $this->service->delete($alumni);

        return response()->json([
            'status'  => 'success',
            'message' => 'Data alumni berhasil dihapus.',
            'data'    => null,
        ]);
    }

    /**
     * POST /api/v1/admin/alumni/import
     * Import alumni dari Excel.
     */
    public function import(Request $request): JsonResponse
    {
        Gate::authorize('create', Alumni::class);

        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:5120', // 5MB
            ],
        ]);

        $result = $this->service->import($request->file('file'));

        return response()->json([
            'status'  => 'success',
            'message' => "Import selesai. Berhasil: {$result['success']}, Gagal: {$result['failed']}.",
            'data'    => $result,
        ]);
    }

    /**
     * GET /api/v1/admin/alumni/export
     * Export alumni ke Excel, return signed URL.
     */
    public function export(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Alumni::class);

        $filePath = $this->service->export($request->only([
            'search', 'study_program_id', 'graduation_year_id',
            'survey_status', 'gender',
        ]));

        $signedUrl = Storage::disk('private')->temporaryUrl($filePath, now()->addMinutes(10));

        return response()->json([
            'status'  => 'success',
            'message' => 'File export siap diunduh.',
            'data'    => [
                'url'        => $signedUrl,
                'expires_in' => 600,
            ],
        ]);
    }

    /**
     * GET /api/v1/admin/alumni/import-template
     * Download template Excel untuk import.
     */
    public function importTemplate(): JsonResponse
    {
        Gate::authorize('create', Alumni::class);

        $filePath  = $this->importExport->generateTemplate();
        $signedUrl = Storage::disk('private')->temporaryUrl($filePath, now()->addMinutes(30));

        return response()->json([
            'status'  => 'success',
            'message' => 'Template import siap diunduh.',
            'data'    => [
                'url'        => $signedUrl,
                'expires_in' => 1800,
            ],
        ]);
    }

    /**
     * POST /api/v1/admin/alumni/{alumni}/send-invitation
     * Kirim undangan survei ke alumni tertentu.
     */
    public function sendInvitation(Request $request, Alumni $alumni): JsonResponse
    {
        Gate::authorize('update', $alumni);

        $request->validate([
            'survey_period_id'   => ['required', 'integer', 'exists:survey_periods,id'],
            'questionnaire_id'   => ['required', 'integer', 'exists:questionnaires,id'],
        ]);

        $this->service->sendInvitation(
            $alumni,
            $request->integer('survey_period_id'),
            $request->integer('questionnaire_id'),
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Undangan survei berhasil dikirim ke antrian.',
            'data'    => null,
        ]);
    }
}

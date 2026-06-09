<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\StoreAlumniRequest;
use App\Http\Requests\Alumni\UpdateAlumniRequest;
use App\Models\Alumni;
use App\Services\AlumniService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin/AlumniController
 *
 * Mengelola data alumni dari sisi admin.
 * Semua endpoint dilindungi: auth:sanctum + EnsureAccountActive + CheckRole:superadmin,admin
 *
 * Routes (05_API.md §2.1):
 *   GET    /api/v1/admin/alumni             → index
 *   POST   /api/v1/admin/alumni             → store
 *   GET    /api/v1/admin/alumni/{id}        → show
 *   PUT    /api/v1/admin/alumni/{id}        → update
 *   DELETE /api/v1/admin/alumni/{id}        → destroy
 *   POST   /api/v1/admin/alumni/import      → import
 *   GET    /api/v1/admin/alumni/export      → export
 *   GET    /api/v1/admin/alumni/template    → importTemplate
 *   POST   /api/v1/admin/alumni/{id}/invite → sendInvitation
 *   GET    /api/v1/admin/alumni/stats       → stats
 */
class AlumniController extends Controller
{
    public function __construct(
        private readonly AlumniService $alumniService,
    ) {}

    /**
     * GET /admin/alumni
     * Daftar alumni dengan filter & paginasi.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Alumni::class);

        $alumni = $this->alumniService->alumniRepo->findWithFilters($request->all());

        return response()->json([
            'success' => true,
            'data'    => $alumni->items(),
            'meta'    => [
                'current_page' => $alumni->currentPage(),
                'per_page'     => $alumni->perPage(),
                'total'        => $alumni->total(),
                'last_page'    => $alumni->lastPage(),
            ],
        ]);
    }

    /**
     * POST /admin/alumni
     * Buat alumni baru.
     */
    public function store(StoreAlumniRequest $request): JsonResponse
    {
        $alumni = $this->alumniService->create(
            $request->validated(),
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Alumni berhasil ditambahkan.',
            'data'    => $alumni,
        ], 201);
    }

    /**
     * GET /admin/alumni/{alumni}
     * Detail satu alumni.
     */
    public function show(Alumni $alumni): JsonResponse
    {
        $this->authorize('view', $alumni);

        $alumni->load([
            'user:id,email,is_active,last_login_at,created_at',
            'studyProgram.faculty',
            'graduationYear',
        ]);

        return response()->json([
            'success' => true,
            'data'    => $alumni,
        ]);
    }

    /**
     * PUT /admin/alumni/{alumni}
     * Update data alumni.
     */
    public function update(UpdateAlumniRequest $request, Alumni $alumni): JsonResponse
    {
        $updated = $this->alumniService->update(
            $alumni,
            $request->validated(),
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Data alumni berhasil diperbarui.',
            'data'    => $updated,
        ]);
    }

    /**
     * DELETE /admin/alumni/{alumni}
     * Soft-delete alumni.
     */
    public function destroy(Request $request, Alumni $alumni): JsonResponse
    {
        $this->authorize('delete', $alumni);

        $this->alumniService->delete($alumni, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Alumni berhasil dihapus.',
        ]);
    }

    /**
     * POST /admin/alumni/import
     * Import alumni dari file Excel.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('import', Alumni::class);

        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:5120', // 5MB
            ],
        ]);

        $result = $this->alumniService->import(
            $request->file('file'),
            $request->user()->id,
        );

        $success = $result['failed'] === 0;

        return response()->json([
            'success' => $success,
            'message' => "Import selesai: {$result['success']} berhasil, {$result['failed']} gagal.",
            'data'    => [
                'success_count' => $result['success'],
                'failed_count'  => $result['failed'],
                'errors'        => $result['errors'],
            ],
        ], $success ? 200 : 422);
    }

    /**
     * GET /admin/alumni/export
     * Export alumni ke Excel (async via queue).
     */
    public function export(Request $request): JsonResponse
    {
        $this->authorize('export', Alumni::class);

        $filename = $this->alumniService->export(
            $request->all(),
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Export sedang diproses. File akan tersedia dalam beberapa saat.',
            'data'    => ['filename' => $filename],
        ]);
    }

    /**
     * GET /admin/alumni/template
     * Download template Excel untuk import.
     */
    public function importTemplate(): JsonResponse
    {
        $path = $this->alumniService->generateImportTemplate();

        // Return signed URL untuk download file dari private storage
        $url = route('api.v1.admin.alumni.template.download', [
            'path' => base64_encode($path),
        ]);

        return response()->json([
            'success' => true,
            'data'    => ['download_url' => $url],
        ]);
    }

    /**
     * POST /admin/alumni/{alumni}/invite
     * Kirim undangan survei ke alumni.
     */
    public function sendInvitation(Request $request, Alumni $alumni): JsonResponse
    {
        $this->authorize('update', $alumni);

        $request->validate([
            'survey_period_id' => ['required', 'integer'],
        ]);

        $this->alumniService->sendInvitation(
            $alumni,
            $request->integer('survey_period_id'),
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Undangan survei berhasil dikirim.',
        ]);
    }

    /**
     * GET /admin/alumni/stats
     * Statistik ringkas alumni untuk dashboard.
     */
    public function stats(): JsonResponse
    {
        $this->authorize('viewAny', Alumni::class);

        $stats = $this->alumniService->alumniRepo->getStats();

        return response()->json([
            'success' => true,
            'data'    => [
                'total'      => $stats['total'],
                'active'     => $stats['active'],
                'by_faculty' => $stats['by_faculty'],
                'by_year'    => $stats['by_year'],
            ],
        ]);
    }
}

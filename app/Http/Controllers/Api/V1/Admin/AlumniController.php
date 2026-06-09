<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\ImportAlumniRequest;
use App\Http\Requests\Alumni\StoreAlumniRequest;
use App\Http\Requests\Alumni\UpdateAlumniRequest;
use App\Models\Alumni;
use App\Repositories\AlumniRepository;
use App\Services\AlumniService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AlumniController extends Controller
{
    public function __construct(
        private readonly AlumniService    $service,
        private readonly AlumniRepository $repository,
    ) {}

    // ─── GET /api/v1/admin/alumni ─────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'faculty_id', 'study_program_id', 'graduation_year_id',
            'employment_status', 'is_active', 'per_page', 'sort_by', 'sort_dir',
        ]);

        $paginator = $this->repository->findWithFilters($filters);

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

    // ─── GET /api/v1/admin/alumni/{alumni} ────────────────────────────────────
    public function show(Alumni $alumni): JsonResponse
    {
        $alumni->load([
            'user:id,name,email,is_active,last_login_at',
            'studyProgram:id,name,faculty_id',
            'studyProgram.faculty:id,name',
            'graduationYear:id,year',
            'workHistories',
        ]);

        return response()->json([
            'success' => true,
            'data'    => $alumni,
        ]);
    }

    // ─── POST /api/v1/admin/alumni ────────────────────────────────────────────
    public function store(StoreAlumniRequest $request): JsonResponse
    {
        $alumni = $this->service->create(
            $request->validated(),
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Alumni berhasil ditambahkan.',
            'data'    => $alumni,
        ], 201);
    }

    // ─── PUT /api/v1/admin/alumni/{alumni} ────────────────────────────────────
    public function update(UpdateAlumniRequest $request, Alumni $alumni): JsonResponse
    {
        $alumni = $this->service->update(
            $alumni,
            $request->validated(),
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Data alumni berhasil diperbarui.',
            'data'    => $alumni,
        ]);
    }

    // ─── DELETE /api/v1/admin/alumni/{alumni} ─────────────────────────────────
    public function destroy(Request $request, Alumni $alumni): JsonResponse
    {
        $this->service->delete($alumni, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Alumni berhasil dihapus.',
        ]);
    }

    // ─── POST /api/v1/admin/alumni/import ─────────────────────────────────────
    public function import(ImportAlumniRequest $request): JsonResponse
    {
        $result = $this->service->import(
            $request->file('file'),
            $request->user()->id,
        );

        $statusCode = $result['failed'] > 0 && $result['success'] === 0 ? 422 : 200;

        return response()->json([
            'success' => $result['success'] > 0 || $result['failed'] === 0,
            'message' => "Berhasil: {$result['success']}, Gagal: {$result['failed']}",
            'data'    => $result,
        ], $statusCode);
    }

    // ─── GET /api/v1/admin/alumni/export ──────────────────────────────────────
    public function export(Request $request): JsonResponse
    {
        $filters  = $request->only(['search', 'faculty_id', 'study_program_id', 'graduation_year_id', 'employment_status']);
        $filename = $this->service->export($filters, $request->user()->id);

        return response()->json([
            'success'  => true,
            'message'  => 'Export sedang diproses. File akan tersedia setelah selesai.',
            'data'     => ['filename' => $filename],
        ]);
    }

    // ─── GET /api/v1/admin/alumni/import-template ─────────────────────────────
    public function importTemplate(): JsonResponse
    {
        $path = $this->service->generateImportTemplate();

        // Return signed URL agar client bisa download dari storage/private
        $url = Storage::temporaryUrl($path, now()->addMinutes(15));

        return response()->json([
            'success' => true,
            'data'    => ['url' => $url, 'expires_in' => 900],
        ]);
    }

    // ─── POST /api/v1/admin/alumni/{alumni}/send-invitation ───────────────────
    public function sendInvitation(Request $request, Alumni $alumni): JsonResponse
    {
        $request->validate([
            'survey_period_id' => ['required', 'integer', 'min:1'],
        ]);

        $this->service->sendInvitation(
            $alumni,
            (int) $request->input('survey_period_id'),
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Undangan survei berhasil dikirim.',
        ]);
    }

    // ─── GET /api/v1/admin/alumni/stats ───────────────────────────────────────
    public function stats(): JsonResponse
    {
        $stats = $this->repository->getStats();

        return response()->json([
            'success' => true,
            'data'    => $stats,
        ]);
    }

    // ─── GET /api/v1/admin/alumni/map ─────────────────────────────────────────
    public function map(Request $request): JsonResponse
    {
        $coords = $this->repository->getMapCoordinates(
            $request->integer('study_program_id') ?: null,
            $request->integer('graduation_year_id') ?: null,
        );

        return response()->json([
            'success' => true,
            'data'    => $coords,
        ]);
    }
}

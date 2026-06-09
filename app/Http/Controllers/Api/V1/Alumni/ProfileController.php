<?php

namespace App\Http\Controllers\Api\V1\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\UpdateAlumniRequest;
use App\Models\Alumni;
use App\Repositories\AlumniRepository;
use App\Services\AlumniService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Alumni/ProfileController
 *
 * Alumni mengelola profil mereka sendiri.
 * Endpoint dilindungi: auth:sanctum + EnsureAccountActive + CheckRole:alumni
 *
 * Routes (05_API.md §2.2):
 *   GET   /api/v1/alumni/profile          → show
 *   PUT   /api/v1/alumni/profile          → update
 *   POST  /api/v1/alumni/profile/photo    → uploadPhoto
 */
class ProfileController extends Controller
{
    public function __construct(
        private readonly AlumniRepository $alumniRepo,
        private readonly AlumniService    $alumniService,
    ) {}

    /**
     * GET /alumni/profile
     * Profil alumni yang sedang login.
     */
    public function show(Request $request): JsonResponse
    {
        $alumni = $this->alumniRepo->findByUserId($request->user()->id);

        if (!$alumni) {
            return response()->json([
                'success' => false,
                'message' => 'Profil alumni tidak ditemukan.',
            ], 404);
        }

        $alumni->load([
            'studyProgram.faculty',
            'graduationYear',
        ]);

        return response()->json([
            'success' => true,
            'data'    => $alumni,
        ]);
    }

    /**
     * PUT /alumni/profile
     * Update profil alumni sendiri.
     */
    public function update(UpdateAlumniRequest $request): JsonResponse
    {
        $alumni = $this->alumniRepo->findByUserId($request->user()->id);

        if (!$alumni) {
            return response()->json([
                'success' => false,
                'message' => 'Profil alumni tidak ditemukan.',
            ], 404);
        }

        $updated = $this->alumniService->update(
            $alumni,
            $request->validated(),
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data'    => $updated,
        ]);
    }

    /**
     * POST /alumni/profile/photo
     * Upload foto profil.
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:2048', // 2MB
            ],
        ]);

        $alumni = $this->alumniRepo->findByUserId($request->user()->id);

        if (!$alumni) {
            return response()->json([
                'success' => false,
                'message' => 'Profil alumni tidak ditemukan.',
            ], 404);
        }

        $this->authorize('uploadPhoto', $alumni);

        $path = $this->alumniService->uploadPhoto($alumni, $request->file('photo'));

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diupload.',
            'data'    => ['photo_path' => $path],
        ]);
    }
}

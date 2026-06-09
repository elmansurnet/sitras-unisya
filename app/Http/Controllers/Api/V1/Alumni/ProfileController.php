<?php

namespace App\Http\Controllers\Api\V1\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\UpdateAlumniRequest;
use App\Services\AlumniService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private readonly AlumniService $service,
    ) {}

    /**
     * GET /api/v1/alumni/profile
     * Lihat profil alumni yang sedang login.
     */
    public function show(Request $request): JsonResponse
    {
        $alumni = $request->user()
            ->alumni
            ->load([
                'studyProgram:id,name,code,degree_level',
                'studyProgram.faculty:id,name',
                'graduationYear:id,year,semester,academic_year',
                'workHistories',
            ]);

        if (!$alumni) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data profil alumni tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Profil alumni berhasil diambil.',
            'data'    => $alumni,
        ]);
    }

    /**
     * PUT /api/v1/alumni/profile
     * Update profil alumni yang sedang login.
     */
    public function update(UpdateAlumniRequest $request): JsonResponse
    {
        $alumni  = $request->user()->alumni;

        if (!$alumni) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data profil alumni tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $alumni = $this->service->update($alumni, $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Profil berhasil diperbarui.',
            'data'    => $alumni,
        ]);
    }

    /**
     * POST /api/v1/alumni/profile/photo
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

        $alumni = $request->user()->alumni;

        if (!$alumni) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data profil alumni tidak ditemukan.',
                'data'    => null,
            ], 404);
        }

        $path = $this->service->uploadPhoto($alumni, $request->file('photo'));

        return response()->json([
            'status'  => 'success',
            'message' => 'Foto profil berhasil diunggah.',
            'data'    => ['photo_path' => $path],
        ]);
    }
}

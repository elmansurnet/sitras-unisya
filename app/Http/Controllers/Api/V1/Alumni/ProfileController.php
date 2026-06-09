<?php

namespace App\Http\Controllers\Api\V1\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\UpdateAlumniRequest;
use App\Models\Alumni;
use App\Services\AlumniService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProfileController extends Controller
{
    public function __construct(
        private readonly AlumniService $service,
    ) {}

    // ─── GET /api/v1/alumni/profile ───────────────────────────────────────────
    public function show(Request $request): JsonResponse
    {
        $alumni = Alumni::where('user_id', $request->user()->id)
            ->with([
                'user:id,name,email,is_active,last_login_at',
                'studyProgram:id,name,faculty_id',
                'studyProgram.faculty:id,name',
                'graduationYear:id,year',
                'workHistories',
            ])
            ->firstOrFail();

        Gate::authorize('view', $alumni);

        return response()->json([
            'success' => true,
            'data'    => $alumni,
        ]);
    }

    // ─── PUT /api/v1/alumni/profile ───────────────────────────────────────────
    public function update(UpdateAlumniRequest $request): JsonResponse
    {
        $alumni = Alumni::where('user_id', $request->user()->id)->firstOrFail();

        Gate::authorize('update', $alumni);

        // Alumni tidak boleh mengubah kolom akademik sensitif
        $data = collect($request->validated())
            ->except(['nim', 'study_program_id', 'graduation_year_id', 'gpa', 'graduation_predicate', 'is_active'])
            ->toArray();

        $alumni = $this->service->update($alumni, $data, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data'    => $alumni,
        ]);
    }

    // ─── POST /api/v1/alumni/profile/photo ───────────────────────────────────
    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => [
                'required',
                'image',
                'max:2048', // 2MB
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
            ],
        ]);

        $alumni = Alumni::where('user_id', $request->user()->id)->firstOrFail();

        Gate::authorize('uploadPhoto', $alumni);

        $path = $this->service->uploadPhoto($alumni, $request->file('photo'));

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diunggah.',
            'data'    => ['photo_path' => $path],
        ]);
    }
}

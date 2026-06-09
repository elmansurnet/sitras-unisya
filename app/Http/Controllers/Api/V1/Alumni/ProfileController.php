<?php

namespace App\Http\Controllers\Api\V1\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\UpdateAlumniRequest;
use App\Models\Alumni;
use App\Services\AlumniService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function __construct(
        private readonly AlumniService $alumniService,
    ) {}

    // ─── GET /alumni/profile ──────────────────────────────────────────────────
    /**
     * Ambil profil alumni yang sedang login.
     * Sesuai 05_API.md §11.1
     */
    public function show(Request $request): JsonResponse
    {
        $alumni = $this->alumniService->alumniRepo->findByUserId($request->user()->id);

        if (!$alumni) {
            return response()->json([
                'success'    => false,
                'message'    => 'Profil alumni tidak ditemukan.',
                'error_code' => 'ALUMNI_NOT_FOUND',
            ], 404);
        }

        Gate::authorize('view', $alumni);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diambil',
            'data'    => $this->formatProfile($alumni),
        ]);
    }

    // ─── PUT /alumni/profile ──────────────────────────────────────────────────
    /**
     * Update profil alumni yang sedang login.
     * Sesuai 05_API.md §11.2
     */
    public function update(Request $request): JsonResponse
    {
        $alumni = $this->alumniService->alumniRepo->findByUserId($request->user()->id);

        if (!$alumni) {
            return response()->json([
                'success'    => false,
                'message'    => 'Profil alumni tidak ditemukan.',
                'error_code' => 'ALUMNI_NOT_FOUND',
            ], 404);
        }

        Gate::authorize('update', $alumni);

        // Alumni hanya boleh update field tertentu — bukan nim/nik/gpa/study_program
        $validated = $request->validate([
            'birth_place'         => ['nullable', 'string', 'max:100'],
            'birth_date'          => ['nullable', 'date', 'before:today'],
            'address_street'      => ['nullable', 'string', 'max:255'],
            'address_village'     => ['nullable', 'string', 'max:100'],
            'address_district'    => ['nullable', 'string', 'max:100'],
            'address_city'        => ['nullable', 'string', 'max:100'],
            'address_province'    => ['nullable', 'string', 'max:100'],
            'address_postal_code' => ['nullable', 'string', 'max:10'],
            'latitude'            => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'           => ['nullable', 'numeric', 'between:-180,180'],
            'phone'               => ['nullable', 'string', 'max:20'],
            'linkedin_url'        => ['nullable', 'url', 'max:255'],
        ]);

        $updated = $this->alumniService->update($alumni, $validated, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data'    => $this->formatProfile($updated),
        ]);
    }

    // ─── POST /alumni/profile/photo ───────────────────────────────────────────
    /**
     * Upload foto profil alumni.
     * Max 2 MB, mime: jpg/jpeg/png/webp. Sesuai 05_API.md §11.3
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $alumni = $this->alumniService->alumniRepo->findByUserId($request->user()->id);

        if (!$alumni) {
            return response()->json([
                'success'    => false,
                'message'    => 'Profil alumni tidak ditemukan.',
                'error_code' => 'ALUMNI_NOT_FOUND',
            ], 404);
        }

        Gate::authorize('update', $alumni);

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $path = $this->alumniService->uploadPhoto($alumni, $request->file('photo'));

        $url = Storage::temporaryUrl($path, now()->addHour());

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diupload',
            'data'    => ['photo_url' => $url],
        ]);
    }

    // ─── PRIVATE HELPER ───────────────────────────────────────────────────────

    /**
     * Format data profil sesuai 05_API.md §11.1
     *
     * @return array<string,mixed>
     */
    private function formatProfile(Alumni $alumni): array
    {
        return [
            'id'                   => $alumni->id,
            'nim'                  => $alumni->nim,
            'full_name'            => $alumni->full_name,
            'gender'               => $alumni->gender,
            'birth_place'          => $alumni->birth_place,
            'birth_date'           => $alumni->birth_date?->toDateString(),
            'study_program'        => $alumni->studyProgram ? [
                'id'           => $alumni->studyProgram->id,
                'name'         => $alumni->studyProgram->name,
                'degree_level' => $alumni->studyProgram->degree_level,
            ] : null,
            'graduation_year'      => $alumni->graduationYear ? [
                'id'            => $alumni->graduationYear->id,
                'year'          => $alumni->graduationYear->year,
                'academic_year' => $alumni->graduationYear->academic_year,
            ] : null,
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
                ? Storage::temporaryUrl($alumni->photo_path, now()->addHour())
                : null,
            'survey_status'        => $alumni->survey_status,
            'is_profile_complete'  => $alumni->isProfileComplete(),
            'work_histories'       => $alumni->workHistories?->map(fn($wh) => [
                'id'              => $wh->id,
                'company_name'    => $wh->company_name,
                'position'        => $wh->position,
                'is_current'      => (bool) $wh->is_current,
                'employment_type' => $wh->employment_type,
                'start_date'      => $wh->start_date?->toDateString(),
                'end_date'        => $wh->end_date?->toDateString(),
            ])->values()->toArray(),
        ];
    }
}

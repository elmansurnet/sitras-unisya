<?php

namespace App\Http\Controllers\Api\V1\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\UpdateAlumniRequest;
use App\Repositories\AlumniRepository;
use App\Services\AlumniService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Alumni\ProfileController
 * Endpoint: /api/v1/alumni/profile
 * Middleware (dipasang di routes/api.php):
 *   auth:sanctum → EnsureAccountActive → CheckRole:alumni
 */
class ProfileController extends Controller
{
    public function __construct(
        private readonly AlumniService    $service,
        private readonly AlumniRepository $repo,
    ) {}

    // ─── GET /api/v1/alumni/profile ───────────────────────────────────────────

    /**
     * Tampilkan profil alumni yang sedang login.
     * Sesuai 05_API.md §11.1
     */
    public function show(Request $request): JsonResponse
    {
        $alumni = $this->repo->findByUserId($request->user()->id);

        if (!$alumni) {
            return response()->json([
                'success'    => false,
                'message'    => 'Data profil alumni tidak ditemukan.',
                'error_code' => 'ALUMNI_PROFILE_NOT_FOUND',
            ], 404);
        }

        Gate::authorize('view', $alumni);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diambil',
            'data'    => $this->formatProfile($alumni),
        ]);
    }

    // ─── PUT /api/v1/alumni/profile ───────────────────────────────────────────

    /**
     * Update profil alumni yang sedang login.
     * Sesuai 05_API.md §11.2
     */
    public function update(UpdateAlumniRequest $request): JsonResponse
    {
        $alumni = $this->repo->findByUserId($request->user()->id);

        if (!$alumni) {
            return response()->json([
                'success'    => false,
                'message'    => 'Data profil alumni tidak ditemukan.',
                'error_code' => 'ALUMNI_PROFILE_NOT_FOUND',
            ], 404);
        }

        Gate::authorize('update', $alumni);

        $updated = $this->service->update(
            $alumni,
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data'    => $this->formatProfile($updated),
        ]);
    }

    // ─── POST /api/v1/alumni/profile/photo ───────────────────────────────────

    /**
     * Upload foto profil.
     * Sesuai 05_API.md §11.3
     * File: jpg/jpeg/png/webp max 2MB → storage/app/private/alumni/photos/
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048', // 2 MB
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
            ],
        ]);

        $alumni = $this->repo->findByUserId($request->user()->id);

        if (!$alumni) {
            return response()->json([
                'success'    => false,
                'message'    => 'Data profil alumni tidak ditemukan.',
                'error_code' => 'ALUMNI_PROFILE_NOT_FOUND',
            ], 404);
        }

        Gate::authorize('uploadPhoto', $alumni);

        $path = $this->service->uploadPhoto($alumni, $request->file('photo'));

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diperbarui',
            'data'    => [
                'photo_url' => \Illuminate\Support\Facades\Storage::temporaryUrl(
                    $path,
                    now()->addHours(1)
                ),
            ],
        ]);
    }

    // ─── PRIVATE HELPERS ─────────────────────────────────────────────────────

    /**
     * Format data profil alumni.
     *
     * @param  \App\Models\Alumni $alumni
     * @return array<string,mixed>
     */
    private function formatProfile(\App\Models\Alumni $alumni): array
    {
        return [
            'id'                   => $alumni->id,
            'nim'                  => $alumni->nim,
            'full_name'            => $alumni->full_name,
            'gender'               => $alumni->gender,
            'birth_place'          => $alumni->birth_place,
            'birth_date'           => $alumni->birth_date?->toDateString(),
            'religion'             => $alumni->religion,
            'marital_status'       => $alumni->marital_status,
            'study_program'        => $alumni->studyProgram ? [
                'id'           => $alumni->studyProgram->id,
                'name'         => $alumni->studyProgram->name,
                'code'         => $alumni->studyProgram->code,
                'degree_level' => $alumni->studyProgram->degree_level,
                'faculty'      => $alumni->studyProgram->faculty ? [
                    'id'   => $alumni->studyProgram->faculty->id,
                    'name' => $alumni->studyProgram->faculty->name,
                ] : null,
            ] : null,
            'graduation_year'      => $alumni->graduationYear ? [
                'id'            => $alumni->graduationYear->id,
                'year'          => $alumni->graduationYear->year,
                'academic_year' => $alumni->graduationYear->academic_year,
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
                'latitude'    => $alumni->address_latitude,
                'longitude'   => $alumni->address_longitude,
            ],
            'phone'                => $alumni->user?->phone,
            'email'                => $alumni->user?->email,
            'linkedin_url'         => $alumni->linkedin_url,
            'instagram_url'        => $alumni->instagram_url,
            'photo_url'            => $alumni->photo_path
                ? \Illuminate\Support\Facades\Storage::temporaryUrl($alumni->photo_path, now()->addHours(1))
                : null,
            'survey_status'        => $alumni->survey_status,
            'is_profile_complete'  => $this->isProfileComplete($alumni),
            'work_histories'       => $alumni->workHistories?->map(fn ($wh) => [
                'id'             => $wh->id,
                'company_name'   => $wh->company_name,
                'position'       => $wh->position,
                'employment_type'=> $wh->employment_type,
                'start_date'     => $wh->start_date?->toDateString(),
                'end_date'       => $wh->end_date?->toDateString(),
                'is_current'     => $wh->is_current,
            ])->toArray() ?? [],
        ];
    }

    /**
     * Cek kelengkapan profil (field-field wajib terisi).
     */
    private function isProfileComplete(\App\Models\Alumni $alumni): bool
    {
        return !empty($alumni->full_name)
            && !empty($alumni->nim)
            && !empty($alumni->study_program_id)
            && !empty($alumni->graduation_year_id)
            && !empty($alumni->user?->phone)
            && !empty($alumni->user?->email);
    }
}

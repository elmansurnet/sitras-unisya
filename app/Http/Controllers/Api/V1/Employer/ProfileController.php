<?php

namespace App\Http\Controllers\Api\V1\Employer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employer\UpdateEmployerRequest;
use App\Models\Employer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * GET /api/v1/employer/profile
     * Employer hanya bisa melihat profil perusahaannya sendiri.
     */
    public function show(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $employer = Employer::where('user_id', $user->id)->first();

        if (! $employer) {
            return response()->json([
                'success' => false,
                'message' => 'Profil employer tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                      => $employer->id,
                'company_name'            => $employer->company_name,
                'company_type'            => $employer->company_type,
                'industry_sector'         => $employer->industry_sector,
                'company_scale'           => $employer->company_scale,
                'address_street'          => $employer->address_street,
                'address_city'            => $employer->address_city,
                'address_province'        => $employer->address_province,
                'address_country'         => $employer->address_country,
                'phone'                   => $employer->phone,
                'email'                   => $employer->email,
                'website'                 => $employer->website,
                'contact_person_name'     => $employer->contact_person_name,
                'contact_person_position' => $employer->contact_person_position,
                'contact_person_email'    => $employer->contact_person_email,
                'contact_person_phone'    => $employer->contact_person_phone,
                'logo'                    => $employer->logo,
                'survey_status'           => $employer->survey_status,
                'created_at'              => $employer->created_at?->toIso8601String(),
                'updated_at'              => $employer->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * PUT /api/v1/employer/profile
     * Employer hanya bisa update kontak & info profil perusahaannya sendiri.
     * Tidak bisa mengubah survey_status, survey_token, atau user_id.
     */
    public function update(UpdateEmployerRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $employer = Employer::where('user_id', $user->id)->first();

        if (! $employer) {
            return response()->json([
                'success' => false,
                'message' => 'Profil employer tidak ditemukan.',
            ], 404);
        }

        // Employer tidak boleh mengubah field sensitif
        $allowed = $request->only([
            'company_name',
            'company_type',
            'industry_sector',
            'company_scale',
            'address_street',
            'address_city',
            'address_province',
            'address_country',
            'phone',
            'email',
            'website',
            'contact_person_name',
            'contact_person_position',
            'contact_person_email',
            'contact_person_phone',
        ]);

        $employer->update($allowed);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data'    => $employer->fresh(),
        ]);
    }
}

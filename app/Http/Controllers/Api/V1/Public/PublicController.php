<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\Faculty;
use App\Models\GraduationYear;
use App\Models\IndustrySector;
use App\Models\SalaryRange;
use App\Models\StudyProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PublicController
 * Endpoint publik TANPA auth middleware.
 * Routes: /api/v1/public/*
 */
class PublicController extends Controller
{
    /**
     * GET /api/v1/public/employer-token/{token}/validate
     * Validasi token employer untuk keperluan frontend sebelum redirect.
     */
    public function validateEmployerToken(Request $request, string $token): JsonResponse
    {
        $employer = Employer::where('survey_token', $token)
            ->where('survey_token_expires_at', '>', now())
            ->where('survey_status', '!=', 'selesai')
            ->first();

        if (! $employer) {
            return response()->json([
                'success'    => false,
                'message'    => 'Link survei tidak valid atau sudah kedaluwarsa.',
                'error_code' => 'INVALID_EMPLOYER_TOKEN',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Token valid',
            'data'    => [
                'company_name'        => $employer->company_name,
                'contact_person_name' => $employer->contact_person_name,
                'is_valid'            => true,
            ],
        ], 200);
    }

    /**
     * GET /api/v1/public/study-programs
     */
    public function masterStudyPrograms(): JsonResponse
    {
        $programs = StudyProgram::with('faculty')
            ->where('is_active', true)
            ->orderBy('faculty_id')
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => [
                'id'           => $p->id,
                'name'         => $p->name,
                'code'         => $p->code,
                'faculty_id'   => $p->faculty_id,
                'faculty_name' => $p->faculty?->name,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Data program studi berhasil diambil',
            'data'    => $programs,
        ], 200);
    }

    /**
     * GET /api/v1/public/faculties
     */
    public function masterFaculties(): JsonResponse
    {
        $faculties = Faculty::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($f) => [
                'id'   => $f->id,
                'name' => $f->name,
                'code' => $f->code,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Data fakultas berhasil diambil',
            'data'    => $faculties,
        ], 200);
    }

    /**
     * GET /api/v1/public/industry-sectors
     */
    public function masterIndustrySectors(): JsonResponse
    {
        $sectors = IndustrySector::orderBy('name')
            ->get()
            ->map(fn ($s) => [
                'id'   => $s->id,
                'name' => $s->name,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Data sektor industri berhasil diambil',
            'data'    => $sectors,
        ], 200);
    }

    /**
     * GET /api/v1/public/graduation-years
     */
    public function masterGraduationYears(): JsonResponse
    {
        $years = GraduationYear::orderByDesc('year')
            ->get()
            ->map(fn ($y) => [
                'id'   => $y->id,
                'year' => $y->year,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Data angkatan berhasil diambil',
            'data'    => $years,
        ], 200);
    }

    /**
     * GET /api/v1/public/salary-ranges
     */
    public function masterSalaryRanges(): JsonResponse
    {
        $ranges = SalaryRange::orderBy('sort_order')
            ->get()
            ->map(fn ($r) => [
                'id'    => $r->id,
                'label' => $r->label,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Data range gaji berhasil diambil',
            'data'    => $ranges,
        ], 200);
    }
}

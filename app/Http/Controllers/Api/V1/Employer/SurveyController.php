<?php

namespace App\Http\Controllers\Api\V1\Employer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Survey\SubmitSurveyRequest;
use App\Models\Employer;
use App\Models\Questionnaire;
use App\Models\SurveyPeriod;
use App\Models\SurveyResponse;
use App\Services\SurveyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SurveyController extends Controller
{
    public function __construct(
        protected SurveyService $service,
    ) {}

    /**
     * GET /api/v1/employer/survey
     * Ambil kuesioner via survey_token (employer tidak perlu login).
     *
     * Token dikirim di query string: ?token=xxx
     * Middleware ValidateEmployerToken harus dipasang di route ini.
     */
    public function show(Request $request): JsonResponse
    {
        /** @var Employer $employer */
        $employer = $request->attributes->get('employer');

        if (! $employer) {
            return response()->json(['success' => false, 'message' => 'Token tidak valid atau sudah kadaluarsa.'], 401);
        }

        // Cek apakah survei sudah diisi
        if ($employer->survey_status === 'selesai') {
            return response()->json([
                'success' => false,
                'message' => 'Survei sudah pernah diisi sebelumnya.',
                'code'    => 'SURVEY_ALREADY_SUBMITTED',
            ], 422);
        }

        // Ambil periode survei aktif yang paling relevan
        $period = SurveyPeriod::where('status', 'active')
            ->latest()
            ->first();

        if (! $period) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada periode survei yang sedang aktif.',
            ], 404);
        }

        // Ambil response yang sudah ada (draft)
        $response = SurveyResponse::where('employer_id', $employer->id)
            ->where('survey_period_id', $period->id)
            ->with('answers')
            ->first();

        return response()->json([
            'success' => true,
            'data'    => [
                'employer' => [
                    'id'           => $employer->id,
                    'company_name' => $employer->company_name,
                ],
                'period' => [
                    'id'       => $period->id,
                    'name'     => $period->name,
                    'end_date' => $period->end_date,
                ],
                'token_expires_at' => $employer->survey_token_expires_at?->toIso8601String(),
                'response'         => $response ? [
                    'id'      => $response->id,
                    'status'  => $response->status,
                    'answers' => $response->answers->map(fn ($a) => [
                        'question_id'    => $a->question_id,
                        'answer_text'    => $a->answer_text,
                        'answer_options' => $a->answer_options,
                        'scale_value'    => $a->scale_value,
                    ]),
                ] : null,
            ],
        ]);
    }

    /**
     * POST /api/v1/employer/survey/submit
     * Submit survei oleh employer via token (satu kali pakai).
     */
    public function submit(SubmitSurveyRequest $request): JsonResponse
    {
        /** @var Employer $employer */
        $employer = $request->attributes->get('employer');

        if (! $employer) {
            return response()->json(['success' => false, 'message' => 'Token tidak valid atau sudah kadaluarsa.'], 401);
        }

        if ($employer->survey_status === 'selesai') {
            return response()->json([
                'success' => false,
                'message' => 'Survei sudah pernah diisi.',
                'code'    => 'SURVEY_ALREADY_SUBMITTED',
            ], 422);
        }

        // Ambil periode aktif
        $period = SurveyPeriod::where('status', 'active')->latest()->first();

        if (! $period) {
            return response()->json(['success' => false, 'message' => 'Tidak ada periode survei yang aktif.'], 404);
        }

        $response = $this->service->submitEmployer($employer, array_merge(
            $request->validated(),
            ['survey_period_id' => $period->id]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Survei berhasil disubmit. Terima kasih!',
            'data'    => [
                'response_id'  => $response->id,
                'status'       => $response->status,
                'submitted_at' => $response->submitted_at?->toIso8601String(),
            ],
        ]);
    }
}

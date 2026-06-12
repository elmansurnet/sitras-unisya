<?php

namespace App\Http\Controllers\Api\V1\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Requests\Survey\SaveDraftRequest;
use App\Http\Requests\Survey\SubmitSurveyRequest;
use App\Models\SurveyPeriod;
use App\Models\SurveyResponse;
use App\Services\SurveyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    public function __construct(
        protected SurveyService $service,
    ) {}

    /**
     * GET /api/v1/alumni/surveys
     * Daftar periode survei yang aktif dan dapat diikuti alumni.
     */
    public function index(Request $request): JsonResponse
    {
        $alumni = $request->user()->alumni;

        if (! $alumni) {
            return response()->json(['success' => false, 'message' => 'Profil alumni tidak ditemukan.'], 404);
        }

        $periods = SurveyPeriod::where('status', 'active')
            ->where(function ($q) use ($alumni) {
                // Periode terbuka untuk tahun lulus alumni
                $q->whereJsonContains('target_graduation_years', $alumni->graduation_year_id)
                  ->orWhereNull('target_graduation_years');
            })
            ->get()
            ->map(function (SurveyPeriod $period) use ($alumni) {
                $progress = $this->service->getAlumniProgress($alumni->id, $period->id);

                return [
                    'id'          => $period->id,
                    'name'        => $period->name,
                    'description' => $period->description,
                    'start_date'  => $period->start_date,
                    'end_date'    => $period->end_date,
                    'status'      => $period->status,
                    'progress'    => $progress,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $periods,
        ]);
    }

    /**
     * GET /api/v1/alumni/surveys/{period}
     * Ambil kuesioner untuk satu periode + jawaban draft sebelumnya (jika ada).
     */
    public function show(Request $request, int $periodId): JsonResponse
    {
        $alumni = $request->user()->alumni;

        if (! $alumni) {
            return response()->json(['success' => false, 'message' => 'Profil alumni tidak ditemukan.'], 404);
        }

        $period = SurveyPeriod::find($periodId);

        if (! $period || $period->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Periode survei tidak tersedia.'], 404);
        }

        // Cari draft atau response sebelumnya
        $response = SurveyResponse::where('alumni_id', $alumni->id)
            ->where('survey_period_id', $periodId)
            ->with('answers')
            ->first();

        return response()->json([
            'success' => true,
            'data'    => [
                'period'   => [
                    'id'        => $period->id,
                    'name'      => $period->name,
                    'end_date'  => $period->end_date,
                ],
                'response' => $response ? [
                    'id'           => $response->id,
                    'status'       => $response->status,
                    'submitted_at' => $response->submitted_at?->toIso8601String(),
                    'answers'      => $response->answers->map(fn ($a) => [
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
     * POST /api/v1/alumni/surveys/{period}/save-draft
     * Simpan draft jawaban alumni (bisa dipanggil berkali-kali).
     */
    public function saveDraft(SaveDraftRequest $request, int $periodId): JsonResponse
    {
        $alumni = $request->user()->alumni;

        if (! $alumni) {
            return response()->json(['success' => false, 'message' => 'Profil alumni tidak ditemukan.'], 404);
        }

        $period = SurveyPeriod::find($periodId);

        if (! $period || $period->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Periode survei tidak tersedia.'], 422);
        }

        $response = $this->service->saveDraftAlumni($alumni, array_merge(
            $request->validated(),
            ['survey_period_id' => $periodId]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Draft berhasil disimpan.',
            'data'    => [
                'response_id' => $response->id,
                'status'      => $response->status,
                'saved_at'    => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * POST /api/v1/alumni/surveys/{period}/submit
     * Submit survei alumni (tidak dapat diubah setelah submit).
     */
    public function submit(SubmitSurveyRequest $request, int $periodId): JsonResponse
    {
        $alumni = $request->user()->alumni;

        if (! $alumni) {
            return response()->json(['success' => false, 'message' => 'Profil alumni tidak ditemukan.'], 404);
        }

        $period = SurveyPeriod::find($periodId);

        if (! $period || $period->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Periode survei tidak tersedia.'], 422);
        }

        $response = $this->service->submitAlumni($alumni, array_merge(
            $request->validated(),
            ['survey_period_id' => $periodId]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Survei berhasil disubmit. Terima kasih atas partisipasi Anda!',
            'data'    => [
                'response_id'  => $response->id,
                'status'       => $response->status,
                'submitted_at' => $response->submitted_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/v1/alumni/surveys/{period}/progress
     * Progress pengisian survei alumni untuk periode tertentu.
     */
    public function progress(Request $request, int $periodId): JsonResponse
    {
        $alumni = $request->user()->alumni;

        if (! $alumni) {
            return response()->json(['success' => false, 'message' => 'Profil alumni tidak ditemukan.'], 404);
        }

        $progress = $this->service->getAlumniProgress($alumni->id, $periodId);

        return response()->json([
            'success' => true,
            'data'    => $progress,
        ]);
    }
}

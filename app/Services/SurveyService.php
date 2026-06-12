<?php

namespace App\Services;

use App\Jobs\ProcessSurveyBlast;
use App\Models\AuditLog;
use App\Models\SurveyPeriod;
use App\Models\SurveyResponse;
use App\Models\Alumni;
use App\Models\Employer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SurveyService
{
    // ─── PERIODE ─────────────────────────────────────────────────────────────

    /**
     * Buka periode survei baru.
     * Tidak menyimpan questionnaire_id di survey_periods (by design — flexible).
     *
     * @param  array{name:string, description:string|null, start_date:string,
     *               end_date:string, target_graduation_years:int[],
     *               send_wa:bool, send_email:bool} $data
     */
    public function openPeriod(array $data, int $actorId): SurveyPeriod
    {
        return DB::transaction(function () use ($data, $actorId) {
            $period = SurveyPeriod::create([
                'name'                     => $data['name'],
                'description'              => $data['description'] ?? null,
                'start_date'               => $data['start_date'],
                'end_date'                 => $data['end_date'],
                'target_graduation_years'  => $data['target_graduation_years'],
                'send_wa'                  => $data['send_wa'] ?? true,
                'send_email'               => $data['send_email'] ?? false,
                'status'                   => 'draft',
                'created_by'               => $actorId,
            ]);

            AuditLog::record(
                action   : 'open_period',
                module   : 'survey_period',
                modelId  : $period->id,
                oldValues: null,
                newValues: [
                    'name'       => $period->name,
                    'start_date' => $period->start_date,
                    'end_date'   => $period->end_date,
                ],
                modelType: SurveyPeriod::class,
            );

            return $period;
        });
    }

    /**
     * Aktifkan periode survei (status: draft → active).
     */
    public function activatePeriod(SurveyPeriod $period, int $actorId): SurveyPeriod
    {
        return DB::transaction(function () use ($period, $actorId) {
            $oldStatus = $period->status;
            $period->update(['status' => 'active']);

            AuditLog::record(
                action   : 'activate_period',
                module   : 'survey_period',
                modelId  : $period->id,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'active'],
                modelType: SurveyPeriod::class,
            );

            return $period->fresh();
        });
    }

    /**
     * Tutup periode survei secara manual (status → closed).
     */
    public function closePeriod(SurveyPeriod $period, int $actorId): SurveyPeriod
    {
        return DB::transaction(function () use ($period, $actorId) {
            $oldStatus = $period->status;
            $period->update([
                'status'       => 'closed',
                'closed_at'    => now(),
                'closed_by'    => $actorId,
            ]);

            AuditLog::record(
                action   : 'close_period',
                module   : 'survey_period',
                modelId  : $period->id,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'closed', 'closed_at' => now()->toIso8601String()],
                modelType: SurveyPeriod::class,
            );

            return $period->fresh();
        });
    }

    // ─── BLAST UNDANGAN ──────────────────────────────────────────────────────

    /**
     * Kirim blast undangan survei ke alumni target.
     * Dispatch ProcessSurveyBlast ke queue 'high'.
     *
     * @param  int   $questionnaireId  questionnaire_id dipilih saat kirim undangan (tidak disimpan di period)
     * @param  array{channel?:string} $options
     */
    public function sendBlast(
        SurveyPeriod $period,
        int $questionnaireId,
        int $actorId,
        array $options = []
    ): void {
        ProcessSurveyBlast::dispatch(
            periodId       : $period->id,
            questionnaireId: $questionnaireId,
            actorId        : $actorId,
            sendWa         : $options['send_wa']    ?? $period->send_wa,
            sendEmail      : $options['send_email'] ?? $period->send_email,
        )->onQueue('high');

        AuditLog::record(
            action   : 'send_blast',
            module   : 'survey_period',
            modelId  : $period->id,
            oldValues: null,
            newValues: [
                'questionnaire_id' => $questionnaireId,
                'actor_id'         => $actorId,
                'send_wa'          => $options['send_wa']    ?? $period->send_wa,
                'send_email'       => $options['send_email'] ?? $period->send_email,
            ],
            modelType: SurveyPeriod::class,
        );
    }

    // ─── PENGISIAN SURVEI ─────────────────────────────────────────────────────

    /**
     * Simpan draft jawaban survei alumni.
     * Upsert per question: satu baris per (survey_response_id, question_id).
     *
     * @param  array{survey_period_id:int, questionnaire_id:int, answers:array} $data
     */
    public function saveDraftAlumni(Alumni $alumni, array $data): SurveyResponse
    {
        return DB::transaction(function () use ($alumni, $data) {
            $response = SurveyResponse::firstOrCreate(
                [
                    'survey_period_id' => $data['survey_period_id'],
                    'questionnaire_id' => $data['questionnaire_id'],
                    'alumni_id'        => $alumni->id,
                    'respondent_type'  => 'alumni',
                ],
                ['status' => 'draft', 'started_at' => now()]
            );

            $this->upsertAnswers($response, $data['answers']);

            if ($response->status !== 'draft') {
                $response->update(['status' => 'draft']);
            }

            return $response->load('answers');
        });
    }

    /**
     * Submit survei alumni (draft → submitted).
     */
    public function submitAlumni(Alumni $alumni, array $data): SurveyResponse
    {
        return DB::transaction(function () use ($alumni, $data) {
            $response = SurveyResponse::firstOrCreate(
                [
                    'survey_period_id' => $data['survey_period_id'],
                    'questionnaire_id' => $data['questionnaire_id'],
                    'alumni_id'        => $alumni->id,
                    'respondent_type'  => 'alumni',
                ],
                ['status' => 'draft', 'started_at' => now()]
            );

            // Hanya izinkan submit jika belum submitted
            if ($response->status === 'submitted') {
                return $response->load('answers');
            }

            $this->upsertAnswers($response, $data['answers']);

            $response->update([
                'status'       => 'submitted',
                'submitted_at' => now(),
            ]);

            return $response->fresh('answers');
        });
    }

    /**
     * Submit survei employer (via survey_token).
     */
    public function submitEmployer(Employer $employer, array $data): SurveyResponse
    {
        return DB::transaction(function () use ($employer, $data) {
            $response = SurveyResponse::firstOrCreate(
                [
                    'survey_period_id' => $data['survey_period_id'],
                    'questionnaire_id' => $data['questionnaire_id'],
                    'employer_id'      => $employer->id,
                    'respondent_type'  => 'employer',
                ],
                ['status' => 'draft', 'started_at' => now()]
            );

            if ($response->status === 'submitted') {
                return $response->load('answers');
            }

            $this->upsertAnswers($response, $data['answers']);

            $response->update([
                'status'       => 'submitted',
                'submitted_at' => now(),
            ]);

            // Tandai token sudah dipakai di employer
            $employer->update(['survey_status' => 'selesai']);

            return $response->fresh('answers');
        });
    }

    // ─── PROGRESS ─────────────────────────────────────────────────────────────

    /**
     * Hitung progress pengisian survei untuk satu alumni di suatu periode.
     *
     * @return array{answered:int, total:int, percentage:float, status:string}
     */
    public function getAlumniProgress(int $alumniId, int $surveyPeriodId): array
    {
        $response = SurveyResponse::where('alumni_id', $alumniId)
            ->where('survey_period_id', $surveyPeriodId)
            ->with('answers')
            ->first();

        if (! $response) {
            return ['answered' => 0, 'total' => 0, 'percentage' => 0.0, 'status' => 'not_started'];
        }

        $answered = $response->answers->count();

        // Total pertanyaan wajib dari questionnaire
        $total = $response->questionnaire
            ? $response->questionnaire->questions()->where('is_required', true)->count()
            : 0;

        $percentage = $total > 0 ? round(($answered / $total) * 100, 2) : 0.0;

        return [
            'answered'   => $answered,
            'total'      => $total,
            'percentage' => $percentage,
            'status'     => $response->status,
        ];
    }

    // ─── HELPERS ──────────────────────────────────────────────────────────────

    /**
     * Upsert jawaban (array of {question_id, answer_text, answer_options, scale_value}).
     *
     * @param  array<int, array{question_id:int, answer_text?:string|null,
     *                          answer_options?:array|null, scale_value?:int|null}> $answers
     */
    private function upsertAnswers(SurveyResponse $response, array $answers): void
    {
        foreach ($answers as $answerData) {
            $response->answers()->updateOrCreate(
                ['question_id' => $answerData['question_id']],
                [
                    'answer_text'    => $answerData['answer_text']    ?? null,
                    'answer_options' => $answerData['answer_options'] ?? null,
                    'scale_value'    => $answerData['scale_value']    ?? null,
                ]
            );
        }
    }
}

<?php

namespace App\Http\Requests\Survey;

use Illuminate\Foundation\Http\FormRequest;

class SubmitSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Alumni terautentikasi ATAU akses employer via token (employer tidak punya user)
        $user = $this->user();

        if ($user) {
            return in_array($user->role, ['alumni']);
        }

        // Employer mengakses tanpa Sanctum — validasi token dilakukan di middleware
        return $this->attributes->has('employer');
    }

    public function rules(): array
    {
        return [
            'questionnaire_id'       => ['required', 'integer', 'exists:questionnaires,id'],
            'answers'                => ['required', 'array', 'min:1'],
            'answers.*.question_id'  => ['required', 'integer', 'exists:questions,id'],
            'answers.*.answer_text'    => ['nullable', 'string', 'max:5000'],
            'answers.*.answer_options' => ['nullable', 'array'],
            'answers.*.answer_options.*' => ['string', 'max:255'],
            'answers.*.scale_value'    => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }

    /**
     * Validasi tambahan: setiap jawaban harus memiliki setidaknya satu field jawaban terisi.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $answers = $this->input('answers', []);

            foreach ($answers as $index => $answer) {
                $hasValue = ! empty($answer['answer_text'])
                    || ! empty($answer['answer_options'])
                    || isset($answer['scale_value']);

                if (! $hasValue) {
                    $v->errors()->add(
                        "answers.{$index}",
                        'Setiap jawaban harus memiliki setidaknya satu nilai terisi (teks, pilihan, atau skala).'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'questionnaire_id.required' => 'ID kuesioner wajib disertakan.',
            'questionnaire_id.exists'   => 'Kuesioner tidak ditemukan.',
            'answers.required'          => 'Jawaban survei wajib disertakan.',
            'answers.min'               => 'Minimal 1 jawaban harus disertakan.',
            'answers.*.question_id.required' => 'Setiap jawaban harus menyertakan ID pertanyaan.',
            'answers.*.question_id.exists'   => 'Pertanyaan tidak ditemukan.',
        ];
    }
}

<?php

namespace App\Http\Requests\Survey;

use Illuminate\Foundation\Http\FormRequest;

class SaveDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya alumni yang terautentikasi
        return $this->user()?->role === 'alumni';
    }

    public function rules(): array
    {
        return [
            'questionnaire_id'       => ['required', 'integer', 'exists:questionnaires,id'],
            'answers'                => ['required', 'array', 'min:1'],
            'answers.*.question_id'  => ['required', 'integer', 'exists:questions,id'],
            // Minimal satu field jawaban harus diisi per answer
            'answers.*.answer_text'    => ['nullable', 'string', 'max:5000'],
            'answers.*.answer_options' => ['nullable', 'array'],
            'answers.*.answer_options.*' => ['string', 'max:255'],
            'answers.*.scale_value'    => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'questionnaire_id.required' => 'ID kuesioner wajib disertakan.',
            'questionnaire_id.exists'   => 'Kuesioner tidak ditemukan.',
            'answers.required'          => 'Jawaban survei wajib disertakan.',
            'answers.array'             => 'Format jawaban tidak valid.',
            'answers.*.question_id.required' => 'Setiap jawaban harus menyertakan ID pertanyaan.',
            'answers.*.question_id.exists'   => 'Pertanyaan tidak ditemukan.',
            'answers.*.scale_value.min'      => 'Nilai skala minimum adalah 1.',
            'answers.*.scale_value.max'      => 'Nilai skala maksimum adalah 10.',
        ];
    }
}

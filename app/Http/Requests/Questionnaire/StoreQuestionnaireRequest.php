<?php

namespace App\Http\Requests\Questionnaire;

use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionnaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->role, ['superadmin', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            // Header kuesioner
            'title'                                    => ['required', 'string', 'max:255'],
            'description'                              => ['nullable', 'string', 'max:2000'],
            'type'                                     => ['required', Rule::in(['alumni', 'employer'])],
            'is_paginated'                             => ['boolean'],
            'estimated_minutes'                        => ['nullable', 'integer', 'min:1', 'max:180'],

            // Seksi (opsional — kuesioner boleh tanpa seksi)
            'sections'                                 => ['nullable', 'array'],
            'sections.*.title'                         => ['required_with:sections', 'string', 'max:255'],
            'sections.*.description'                   => ['nullable', 'string', 'max:1000'],
            'sections.*.order_number'                  => ['required_with:sections', 'integer', 'min:1'],

            // Pertanyaan (minimal 1 pertanyaan wajib ada di kuesioner)
            'questions'                                => ['required', 'array', 'min:1'],
            'questions.*.question_text'                => ['required', 'string', 'max:1000'],
            'questions.*.question_type'                => ['required', Rule::in([
                'text', 'textarea', 'radio', 'checkbox',
                'select', 'likert', 'rating', 'date', 'file', 'number',
            ])],
            'questions.*.is_required'                  => ['boolean'],
            'questions.*.order_number'                 => ['required', 'integer', 'min:1'],
            'questions.*.help_text'                    => ['nullable', 'string', 'max:500'],
            'questions.*.placeholder'                  => ['nullable', 'string', 'max:255'],
            'questions.*.section_index'                => ['nullable', 'integer', 'min:0'],
            'questions.*.validation_rules'             => ['nullable', 'array'],
            'questions.*.conditional_logic'            => ['nullable', 'array'],
            'questions.*.conditional_logic.depends_on' => ['nullable', 'integer'],
            'questions.*.conditional_logic.operator'   => ['nullable', 'string', Rule::in(['equals', 'not_equals', 'contains'])],
            'questions.*.conditional_logic.value'      => ['nullable', 'string'],

            // Opsi — wajib ada jika question_type adalah option type
            'questions.*.options'                      => [
                'array',
                function ($attribute, $value, $fail) {
                    // Ambil index pertanyaan dari attribute path, e.g. "questions.2.options"
                    $parts = explode('.', $attribute);
                    $idx   = $parts[1] ?? null;
                    $type  = $this->input("questions.{$idx}.question_type");

                    if (in_array($type, Question::OPTION_TYPES, true) && (empty($value) || count($value) < 2)) {
                        $fail("Pertanyaan dengan tipe '{$type}' harus memiliki minimal 2 opsi.");
                    }
                },
            ],
            'questions.*.options.*.option_text'        => ['required_with:questions.*.options', 'string', 'max:500'],
            'questions.*.options.*.option_value'       => ['required_with:questions.*.options', 'string', 'max:100'],
            'questions.*.options.*.order_number'       => ['required_with:questions.*.options', 'integer', 'min:1'],
            'questions.*.options.*.is_other'           => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'                    => 'Judul kuesioner wajib diisi.',
            'type.required'                     => 'Tipe kuesioner wajib dipilih (alumni/employer).',
            'type.in'                           => 'Tipe kuesioner harus salah satu dari: alumni, employer.',
            'questions.required'                => 'Kuesioner harus memiliki minimal 1 pertanyaan.',
            'questions.min'                     => 'Kuesioner harus memiliki minimal 1 pertanyaan.',
            'questions.*.question_text.required'=> 'Teks pertanyaan tidak boleh kosong.',
            'questions.*.question_type.required'=> 'Tipe pertanyaan wajib dipilih.',
            'questions.*.question_type.in'      => 'Tipe pertanyaan tidak valid.',
            'questions.*.order_number.required' => 'Urutan pertanyaan wajib diisi.',
        ];
    }
}

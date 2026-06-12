<?php

namespace App\Http\Requests\Questionnaire;

use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuestionnaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        $questionnaire = $this->route('questionnaire');

        // Kuesioner aktif tidak dapat diubah strukturnya
        if ($questionnaire && $questionnaire->isActive()) {
            return false;
        }

        return in_array($this->user()->role, ['superadmin', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            // Header — semua optional pada update
            'title'                                    => ['sometimes', 'string', 'max:255'],
            'description'                              => ['nullable', 'string', 'max:2000'],
            'type'                                     => ['sometimes', Rule::in(['alumni', 'employer'])],
            'is_paginated'                             => ['sometimes', 'boolean'],
            'estimated_minutes'                        => ['nullable', 'integer', 'min:1', 'max:180'],

            // Seksi
            'sections'                                 => ['sometimes', 'nullable', 'array'],
            'sections.*.id'                            => ['nullable', 'integer', 'exists:questionnaire_sections,id'],
            'sections.*.title'                         => ['required_with:sections', 'string', 'max:255'],
            'sections.*.description'                   => ['nullable', 'string', 'max:1000'],
            'sections.*.order_number'                  => ['required_with:sections', 'integer', 'min:1'],
            'sections.*._delete'                       => ['sometimes', 'boolean'],

            // Pertanyaan
            'questions'                                => ['sometimes', 'array', 'min:1'],
            'questions.*.id'                           => ['nullable', 'integer', 'exists:questions,id'],
            'questions.*.question_text'                => ['required_with:questions', 'string', 'max:1000'],
            'questions.*.question_type'                => ['required_with:questions', Rule::in([
                'text', 'textarea', 'radio', 'checkbox',
                'select', 'likert', 'rating', 'date', 'file', 'number',
            ])],
            'questions.*.is_required'                  => ['sometimes', 'boolean'],
            'questions.*.order_number'                 => ['required_with:questions', 'integer', 'min:1'],
            'questions.*.help_text'                    => ['nullable', 'string', 'max:500'],
            'questions.*.placeholder'                  => ['nullable', 'string', 'max:255'],
            'questions.*.section_index'                => ['nullable', 'integer', 'min:0'],
            'questions.*.validation_rules'             => ['nullable', 'array'],
            'questions.*.conditional_logic'            => ['nullable', 'array'],
            'questions.*.conditional_logic.depends_on' => ['nullable', 'integer'],
            'questions.*.conditional_logic.operator'   => ['nullable', 'string', Rule::in(['equals', 'not_equals', 'contains'])],
            'questions.*.conditional_logic.value'      => ['nullable', 'string'],
            'questions.*._delete'                      => ['sometimes', 'boolean'],

            // Opsi
            'questions.*.options'                      => [
                'sometimes',
                'array',
                function ($attribute, $value, $fail) {
                    $parts = explode('.', $attribute);
                    $idx   = $parts[1] ?? null;
                    $type  = $this->input("questions.{$idx}.question_type");

                    if (
                        $type !== null
                        && in_array($type, Question::OPTION_TYPES, true)
                        && ! empty($value)
                        && count(array_filter($value, fn($o) => empty($o['_delete']))) < 2
                    ) {
                        $fail("Pertanyaan dengan tipe '{$type}' harus memiliki minimal 2 opsi aktif.");
                    }
                },
            ],
            'questions.*.options.*.id'                 => ['nullable', 'integer', 'exists:question_options,id'],
            'questions.*.options.*.option_text'        => ['required_with:questions.*.options', 'string', 'max:500'],
            'questions.*.options.*.option_value'       => ['required_with:questions.*.options', 'string', 'max:100'],
            'questions.*.options.*.order_number'       => ['required_with:questions.*.options', 'integer', 'min:1'],
            'questions.*.options.*.is_other'           => ['sometimes', 'boolean'],
            'questions.*.options.*._delete'            => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.string'                      => 'Judul kuesioner harus berupa teks.',
            'type.in'                           => 'Tipe kuesioner harus salah satu dari: alumni, employer.',
            'questions.min'                     => 'Kuesioner harus memiliki minimal 1 pertanyaan.',
            'questions.*.question_text.required_with' => 'Teks pertanyaan tidak boleh kosong.',
            'questions.*.question_type.required_with' => 'Tipe pertanyaan wajib dipilih.',
            'questions.*.question_type.in'      => 'Tipe pertanyaan tidak valid.',
            'questions.*.order_number.required_with'  => 'Urutan pertanyaan wajib diisi.',
        ];
    }
}

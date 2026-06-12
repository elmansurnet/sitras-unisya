<?php

namespace App\Http\Requests\Questionnaire;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->role, ['superadmin', 'admin'], true);
    }

    public function rules(): array
    {
        $questionnaireId = $this->route('questionnaire')?->id;

        return [
            'target'                   => ['required', Rule::in(['questions', 'sections'])],
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.id'               => [
                'required',
                'integer',
                // Pastikan setiap ID milik kuesioner yang sedang di-reorder
                Rule::when(
                    $this->input('target') === 'questions',
                    Rule::exists('questions', 'id')->where('questionnaire_id', $questionnaireId)
                ),
                Rule::when(
                    $this->input('target') === 'sections',
                    Rule::exists('questionnaire_sections', 'id')->where('questionnaire_id', $questionnaireId)
                ),
            ],
            'items.*.order_number'     => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'target.required'              => 'Target reorder wajib diisi (questions / sections).',
            'target.in'                    => 'Target reorder harus salah satu dari: questions, sections.',
            'items.required'               => 'Daftar item reorder wajib diisi.',
            'items.min'                    => 'Daftar item reorder tidak boleh kosong.',
            'items.*.id.required'          => 'ID item wajib ada di setiap entri.',
            'items.*.id.exists'            => 'Salah satu ID tidak ditemukan atau bukan milik kuesioner ini.',
            'items.*.order_number.required'=> 'Urutan baru wajib diisi untuk setiap item.',
        ];
    }
}

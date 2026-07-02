<?php

namespace App\Http\Requests\GraduationYear;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateGraduationYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('admin-or-superadmin');
    }

    public function rules(): array
    {
        $id       = $this->route('graduation_year')?->id ?? $this->route('graduation_year');
        $semester = $this->input('semester', $this->route('graduation_year')?->semester);

        return [
            'year'          => [
                'sometimes', 'required', 'integer', 'min:2000', 'max:2100',
                Rule::unique('graduation_years')
                    ->where(fn ($q) => $q->where('semester', $semester))
                    ->ignore($id),
            ],
            'academic_year' => ['sometimes', 'required', 'string', 'max:20'],
            'semester'      => ['sometimes', 'required', 'string', Rule::in(['Ganjil', 'Genap'])],
            'is_active'     => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'year.unique'          => 'Kombinasi tahun dan semester ini sudah digunakan oleh entri lain.',
            'year.min'             => 'Tahun kelulusan tidak valid (minimum 2000).',
            'year.max'             => 'Tahun kelulusan tidak valid (maksimum 2100).',
            'academic_year.required' => 'Tahun akademik wajib diisi.',
            'semester.required'    => 'Semester wajib dipilih.',
            'semester.in'          => 'Semester harus Ganjil atau Genap.',
        ];
    }
}

<?php

namespace App\Http\Requests\GraduationYear;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreGraduationYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('admin-or-superadmin');
    }

    public function rules(): array
    {
        return [
            'year'          => ['required', 'integer', 'min:2000', 'max:2100', Rule::unique('graduation_years')->where(fn ($q) => $q->where('semester', $this->input('semester')))],
            'academic_year' => ['required', 'string', 'max:20'],
            'semester'      => ['required', 'string', Rule::in(['Ganjil', 'Genap'])],
            'is_active'     => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'year.unique'          => 'Kombinasi tahun dan semester ini sudah terdaftar.',
            'year.min'             => 'Tahun kelulusan tidak valid (minimum 2000).',
            'year.max'             => 'Tahun kelulusan tidak valid (maksimum 2100).',
            'academic_year.required' => 'Tahun akademik wajib diisi.',
            'semester.required'    => 'Semester wajib dipilih.',
            'semester.in'          => 'Semester harus Ganjil atau Genap.',
        ];
    }
}

<?php

namespace App\Http\Requests\GraduationYear;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreGraduationYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('admin-or-superadmin');
    }

    public function rules(): array
    {
        return [
            'year'      => ['required', 'integer', 'min:2000', 'max:2100', 'unique:graduation_years,year'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'year.unique' => 'Tahun kelulusan sudah terdaftar.',
            'year.min'    => 'Tahun kelulusan tidak valid (minimum 2000).',
            'year.max'    => 'Tahun kelulusan tidak valid (maksimum 2100).',
        ];
    }
}

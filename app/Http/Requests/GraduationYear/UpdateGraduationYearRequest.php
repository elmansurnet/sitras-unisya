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
        $graduationYearId = $this->route('graduation_year')?->id ?? $this->route('graduation_year');

        return [
            'year'      => ['sometimes', 'required', 'integer', 'min:2000', 'max:2100', Rule::unique('graduation_years', 'year')->ignore($graduationYearId)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'year.unique' => 'Tahun kelulusan sudah digunakan oleh entri lain.',
            'year.min'    => 'Tahun kelulusan tidak valid (minimum 2000).',
            'year.max'    => 'Tahun kelulusan tidak valid (maksimum 2100).',
        ];
    }
}

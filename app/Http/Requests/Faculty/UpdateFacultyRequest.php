<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateFacultyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('admin-or-superadmin');
    }

    public function rules(): array
    {
        $facultyId = $this->route('faculty')?->id ?? $this->route('faculty');

        return [
            'name'      => ['sometimes', 'required', 'string', 'max:255', Rule::unique('faculties', 'name')->ignore($facultyId)],
            'code'      => ['sometimes', 'required', 'string', 'max:20', Rule::unique('faculties', 'code')->ignore($facultyId)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Nama fakultas sudah digunakan oleh fakultas lain.',
            'code.unique' => 'Kode fakultas sudah digunakan oleh fakultas lain.',
        ];
    }
}

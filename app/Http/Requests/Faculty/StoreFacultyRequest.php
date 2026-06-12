<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreFacultyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('admin-or-superadmin');
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255', 'unique:faculties,name'],
            'code'       => ['required', 'string', 'max:20', 'unique:faculties,code'],
            'is_active'  => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Nama fakultas sudah digunakan.',
            'code.unique' => 'Kode fakultas sudah digunakan.',
        ];
    }
}

<?php

namespace App\Http\Requests\StudyProgram;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreStudyProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('admin-or-superadmin');
    }

    public function rules(): array
    {
        return [
            'faculty_id' => ['required', 'integer', 'exists:faculties,id'],
            'name'       => ['required', 'string', 'max:255', 'unique:study_programs,name'],
            'code'       => ['required', 'string', 'max:20', 'unique:study_programs,code'],
            'level'      => ['required', 'string', 'in:D3,D4,S1,S2,S3'],
            'is_active'  => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'faculty_id.exists' => 'Fakultas tidak ditemukan.',
            'name.unique'       => 'Nama program studi sudah digunakan.',
            'code.unique'       => 'Kode program studi sudah digunakan.',
            'level.in'          => 'Jenjang harus salah satu dari: D3, D4, S1, S2, S3.',
        ];
    }
}

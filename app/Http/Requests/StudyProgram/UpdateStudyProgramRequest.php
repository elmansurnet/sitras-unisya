<?php

namespace App\Http\Requests\StudyProgram;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateStudyProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('admin-or-superadmin');
    }

    public function rules(): array
    {
        $studyProgramId = $this->route('study_program')?->id ?? $this->route('study_program');

        return [
            'faculty_id' => ['sometimes', 'required', 'integer', 'exists:faculties,id'],
            'name'       => ['sometimes', 'required', 'string', 'max:255', Rule::unique('study_programs', 'name')->ignore($studyProgramId)],
            'code'       => ['sometimes', 'required', 'string', 'max:20', Rule::unique('study_programs', 'code')->ignore($studyProgramId)],
            'level'      => ['sometimes', 'required', 'string', 'in:D3,D4,S1,S2,S3'],
            'is_active'  => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'faculty_id.exists' => 'Fakultas tidak ditemukan.',
            'name.unique'       => 'Nama program studi sudah digunakan oleh prodi lain.',
            'code.unique'       => 'Kode program studi sudah digunakan oleh prodi lain.',
            'level.in'          => 'Jenjang harus salah satu dari: D3, D4, S1, S2, S3.',
        ];
    }
}

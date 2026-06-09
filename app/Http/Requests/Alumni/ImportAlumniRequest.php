<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ImportAlumniRequest
 * Validasi untuk POST /api/v1/admin/alumni/import
 * File: .xlsx/.csv max 10MB sesuai 05_API.md §3.6
 */
class ImportAlumniRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->isSuperadmin();
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:xlsx,csv,xls',
                'max:10240', // 10 MB
            ],
            'study_program_id'   => ['nullable', 'integer', 'exists:study_programs,id'],
            'graduation_year_id' => ['nullable', 'integer', 'exists:graduation_years,id'],
        ];
    }

    /**
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'File import wajib diupload.',
            'file.mimes'    => 'File harus berformat .xlsx, .xls, atau .csv.',
            'file.max'      => 'Ukuran file maksimal 10 MB.',
        ];
    }
}

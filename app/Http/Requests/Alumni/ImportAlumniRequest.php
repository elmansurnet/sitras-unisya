<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;

class ImportAlumniRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('import', \App\Models\Alumni::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file'               => [
                'required',
                'file',
                // Hanya xlsx dan csv; max 10 MB sesuai 05_API.md §3.6
                'mimes:xlsx,csv',
                'max:10240',
            ],
            // Override prodi & angkatan opsional — jika diisi, wajib valid
            'study_program_id'   => ['nullable', 'integer', 'exists:study_programs,id'],
            'graduation_year_id' => ['nullable', 'integer', 'exists:graduation_years,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimes' => 'File harus berformat .xlsx atau .csv.',
            'file.max'   => 'Ukuran file maksimal 10 MB.',
        ];
    }
}

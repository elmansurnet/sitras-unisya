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
                // Extension check (layer 1)
                'mimes:xlsx,csv',
                // Byte-level MIME check (layer 2 — cegah bypass via rename, 07_SECURITY.md §5)
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv,text/plain,application/csv',
                // Max 10 MB sesuai 05_API.md §3.6
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
            'file.required'  => 'File import wajib diunggah.',
            'file.mimes'     => 'File harus berformat .xlsx atau .csv.',
            'file.mimetypes' => 'Tipe file tidak valid. Hanya .xlsx dan .csv yang diizinkan.',
            'file.max'       => 'Ukuran file maksimal 10 MB.',
        ];
    }
}

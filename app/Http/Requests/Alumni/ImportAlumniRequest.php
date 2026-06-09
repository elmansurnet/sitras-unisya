<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;

class ImportAlumniRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
                // Max 5MB
                'max:5120',
                // Validasi MIME type eksplisit (OWASP A01: broken access control)
                'mimes:xlsx,xls,csv',
                // Validasi MIME type di level konten (bukan hanya ekstensi)
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv,text/plain',
            ],
        ];
    }

    /**
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'file.required'  => 'File import wajib diunggah.',
            'file.mimes'     => 'Format file harus xlsx, xls, atau csv.',
            'file.max'       => 'Ukuran file maksimal 5MB.',
            'file.mimetypes' => 'Tipe konten file tidak valid.',
        ];
    }
}

<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlumniRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['superadmin', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            // Identitas wajib
            'nim'                => ['required', 'string', 'max:20', 'unique:alumni,nim'],
            'full_name'          => ['required', 'string', 'max:255'],
            'gender'             => ['required', Rule::in(['L', 'P'])],
            'study_program_id'   => ['required', 'integer', 'exists:study_programs,id'],
            'graduation_year_id' => ['required', 'integer', 'exists:graduation_years,id'],

            // Identitas opsional
            'nik'                        => ['nullable', 'string', 'max:20'],
            'birth_place'                => ['nullable', 'string', 'max:100'],
            'birth_date'                 => ['nullable', 'date', 'before:today'],
            'thesis_title'               => ['nullable', 'string'],
            'gpa'                        => ['nullable', 'numeric', 'min:0', 'max:4'],
            'graduation_predicate'       => ['nullable', 'string', 'max:50'],

            // Kontak
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],

            // Alamat
            'address_street'      => ['nullable', 'string'],
            'address_village'     => ['nullable', 'string', 'max:100'],
            'address_district'    => ['nullable', 'string', 'max:100'],
            'address_city'        => ['nullable', 'string', 'max:100'],
            'address_province'    => ['nullable', 'string', 'max:100'],
            'address_postal_code' => ['nullable', 'string', 'max:10'],
            'address_latitude'    => ['nullable', 'numeric', 'between:-90,90'],
            'address_longitude'   => ['nullable', 'numeric', 'between:-180,180'],

            // LinkedIn
            'linkedin_url' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nim.unique'                => 'NIM sudah terdaftar di sistem.',
            'email.unique'              => 'Email sudah digunakan oleh pengguna lain.',
            'study_program_id.exists'   => 'Program studi tidak ditemukan.',
            'graduation_year_id.exists' => 'Tahun lulus tidak ditemukan.',
            'gpa.max'                   => 'IPK maksimal 4.00.',
            'birth_date.before'         => 'Tanggal lahir harus sebelum hari ini.',
        ];
    }
}

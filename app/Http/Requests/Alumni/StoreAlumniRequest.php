<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreAlumniRequest
 * Validasi untuk POST /api/v1/admin/alumni
 * Sesuai skema tabel alumni di 02_DATABASE.md §2.3
 */
class StoreAlumniRequest extends FormRequest
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
            // Identitas
            'nim'                    => ['required', 'string', 'max:20', 'unique:alumni,nim'],
            'nik'                    => ['nullable', 'string', 'size:16', 'unique:alumni,nik'],
            'full_name'              => ['required', 'string', 'max:255'],
            'gender'                 => ['required', Rule::in(['L', 'P'])],
            'birth_place'            => ['nullable', 'string', 'max:100'],
            'birth_date'             => ['nullable', 'date', 'before:today'],
            'religion'               => ['nullable', 'string', 'max:50'],
            'marital_status'         => ['nullable', Rule::in(['belum_menikah', 'menikah', 'cerai'])],

            // Akademik
            'study_program_id'       => ['required', 'integer', 'exists:study_programs,id'],
            'graduation_year_id'     => ['required', 'integer', 'exists:graduation_years,id'],
            'thesis_title'           => ['nullable', 'string', 'max:500'],
            'gpa'                    => ['nullable', 'numeric', 'min:0', 'max:4.00'],
            'graduation_predicate'   => ['nullable', Rule::in(['Memuaskan', 'Sangat Memuaskan', 'Cumlaude'])],

            // Alamat
            'address_street'         => ['nullable', 'string', 'max:255'],
            'address_village'        => ['nullable', 'string', 'max:100'],
            'address_district'       => ['nullable', 'string', 'max:100'],
            'address_city'           => ['nullable', 'string', 'max:100'],
            'address_province'       => ['nullable', 'string', 'max:100'],
            'address_postal_code'    => ['nullable', 'string', 'max:10'],
            'address_latitude'       => ['nullable', 'numeric', 'min:-90', 'max:90'],
            'address_longitude'      => ['nullable', 'numeric', 'min:-180', 'max:180'],

            // Kontak (email & phone di tabel users)
            'email'                  => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'                  => ['nullable', 'string', 'max:20'],

            // Sosial
            'linkedin_url'           => ['nullable', 'url', 'max:255'],
            'instagram_url'          => ['nullable', 'url', 'max:255'],
        ];
    }

    /**
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'nim.unique'           => 'NIM sudah terdaftar.',
            'nik.size'             => 'NIK harus 16 digit.',
            'nik.unique'           => 'NIK sudah terdaftar.',
            'email.unique'         => 'Email sudah digunakan.',
            'gpa.max'              => 'IPK maksimal 4.00.',
            'study_program_id.exists' => 'Program studi tidak ditemukan.',
            'graduation_year_id.exists' => 'Angkatan tidak ditemukan.',
        ];
    }
}

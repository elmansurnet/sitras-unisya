<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlumniRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Alumni::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Identitas
            'nim'                    => ['required', 'string', 'max:20', 'unique:alumni,nim'],
            'nik'                    => ['nullable', 'string', 'size:16', 'unique:alumni,nik'],
            'full_name'              => ['required', 'string', 'max:200'],
            'gender'                 => ['required', Rule::in(['L', 'P'])],
            'birth_place'            => ['nullable', 'string', 'max:100'],
            'birth_date'             => ['nullable', 'date', 'before:today'],

            // Akademik
            'study_program_id'       => ['required', 'integer', 'exists:study_programs,id'],
            'graduation_year_id'     => ['required', 'integer', 'exists:graduation_years,id'],
            'thesis_title'           => ['nullable', 'string', 'max:500'],
            'gpa'                    => ['nullable', 'numeric', 'between:0,4'],
            'graduation_predicate'   => ['nullable', 'string', 'max:50'],

            // Alamat
            'address_street'         => ['nullable', 'string', 'max:255'],
            'address_village'        => ['nullable', 'string', 'max:100'],
            'address_district'       => ['nullable', 'string', 'max:100'],
            'address_city'           => ['nullable', 'string', 'max:100'],
            'address_province'       => ['nullable', 'string', 'max:100'],
            'address_postal_code'    => ['nullable', 'string', 'max:10'],
            'latitude'               => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'              => ['nullable', 'numeric', 'between:-180,180'],

            // Kontak
            'phone'                  => ['nullable', 'string', 'max:20'],
            'email'                  => ['required', 'email', 'max:200', 'unique:users,email'],
            'linkedin_url'           => ['nullable', 'url', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nim.unique'         => 'NIM sudah terdaftar.',
            'nik.unique'         => 'NIK sudah terdaftar.',
            'nik.size'           => 'NIK harus 16 digit.',
            'email.unique'       => 'Email sudah digunakan.',
            'gpa.between'        => 'IPK harus antara 0 dan 4.',
            'study_program_id.exists' => 'Program studi tidak ditemukan.',
            'graduation_year_id.exists' => 'Angkatan tidak ditemukan.',
        ];
    }
}

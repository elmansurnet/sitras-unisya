<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlumniRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya superadmin & admin — dihandle middleware 'role:superadmin,admin'
        return true;
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            // Akun user
            'email'             => ['required', 'email:rfc,dns', 'max:150', 'unique:users,email'],
            'password'          => ['nullable', 'string', 'min:8'],

            // Data pribadi
            'nim'               => ['required', 'string', 'max:20', 'unique:alumni,nim'],
            'full_name'         => ['required', 'string', 'max:150'],
            'nik'               => ['nullable', 'string', 'size:16', 'unique:alumni,nik'],
            'birth_place'       => ['nullable', 'string', 'max:100'],
            'birth_date'        => ['nullable', 'date', 'before:today'],
            'gender'            => ['nullable', Rule::in(['M', 'F'])],
            'religion'          => ['nullable', 'string', 'max:50'],
            'phone'             => ['nullable', 'string', 'max:20'],

            // Alamat
            'address_street'    => ['nullable', 'string', 'max:255'],
            'address_village'   => ['nullable', 'string', 'max:100'],
            'address_district'  => ['nullable', 'string', 'max:100'],
            'city'              => ['nullable', 'string', 'max:100'],
            'province'          => ['nullable', 'string', 'max:100'],
            'postal_code'       => ['nullable', 'string', 'max:10'],
            'latitude'          => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'         => ['nullable', 'numeric', 'between:-180,180'],

            // Akademik
            'study_program_id'  => ['required', 'integer', 'exists:study_programs,id'],
            'graduation_year_id'=> ['required', 'integer', 'exists:graduation_years,id'],
            'gpa'               => ['required', 'numeric', 'between:0.00,4.00'],
            'graduation_predicate' => ['nullable', 'string', 'max:50'],
            'thesis_title'      => ['nullable', 'string', 'max:500'],

            // Karir & sosial
            'employment_status' => ['nullable', Rule::in(['employed', 'self_employed', 'entrepreneur', 'unemployed', 'continuing_study', 'not_seeking'])],
            'linkedin_url'      => ['nullable', 'url', 'max:255'],
            'skills'            => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'nim.unique'    => 'NIM sudah terdaftar.',
            'nik.unique'    => 'NIK sudah terdaftar.',
            'email.unique'  => 'Email sudah terdaftar.',
            'gpa.between'   => 'IPK harus antara 0.00 hingga 4.00.',
            'gender.in'     => 'Jenis kelamin harus M atau F.',
        ];
    }
}

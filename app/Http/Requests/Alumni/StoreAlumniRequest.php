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
            // Akun user
            'email'    => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],

            // Identitas
            'nim'                 => ['required', 'string', 'max:20', 'unique:alumni,nim'],
            'full_name'           => ['required', 'string', 'max:255'],
            'nik'                 => ['nullable', 'string', 'size:16', 'unique:alumni,nik'],
            'birth_place'         => ['nullable', 'string', 'max:100'],
            'birth_date'          => ['nullable', 'date', 'before:today'],
            'gender'              => ['nullable', Rule::in(['M', 'F'])],
            'religion'            => ['nullable', 'string', 'max:50'],

            // Akademik
            'study_program_id'    => ['required', 'integer', 'exists:study_programs,id'],
            'graduation_year_id'  => ['required', 'integer', 'exists:graduation_years,id'],
            'gpa'                 => ['nullable', 'numeric', 'min:0', 'max:4.00'],
            'graduation_predicate'=> ['nullable', 'string', 'max:50'],
            'thesis_title'        => ['nullable', 'string', 'max:500'],

            // Alamat
            'address_street'      => ['nullable', 'string', 'max:255'],
            'address_village'     => ['nullable', 'string', 'max:100'],
            'address_district'    => ['nullable', 'string', 'max:100'],
            'address_city'        => ['nullable', 'string', 'max:100'],
            'address_province'    => ['nullable', 'string', 'max:100'],
            'address_postal_code' => ['nullable', 'string', 'max:10'],

            // Kontak & lainnya
            'phone'               => ['nullable', 'string', 'max:20'],
            'linkedin_url'        => ['nullable', 'url', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nim.unique'          => 'NIM sudah terdaftar.',
            'nik.unique'          => 'NIK sudah terdaftar.',
            'email.unique'        => 'Email sudah digunakan.',
            'gpa.max'             => 'IPK tidak boleh lebih dari 4.00.',
            'gender.in'           => 'Jenis kelamin harus M (Laki-laki) atau F (Perempuan).',
            'birth_date.before'   => 'Tanggal lahir harus sebelum hari ini.',
            'study_program_id.exists' => 'Program studi tidak ditemukan.',
            'graduation_year_id.exists' => 'Tahun lulus tidak ditemukan.',
        ];
    }
}

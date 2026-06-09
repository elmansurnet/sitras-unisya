<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlumniRequest extends FormRequest
{
    public function authorize(): bool
    {
        $alumni = $this->route('alumni');

        return $this->user()?->can('update', $alumni) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $alumniId = $this->route('alumni')?->id;
        $userId   = $this->route('alumni')?->user_id;

        return [
            // Email: unique kecuali milik user ini
            'email'    => ['sometimes', 'email:rfc,dns', 'max:255', Rule::unique('users', 'email')->ignore($userId)],

            // Identitas
            'nim'                 => ['sometimes', 'string', 'max:20', Rule::unique('alumni', 'nim')->ignore($alumniId)],
            'full_name'           => ['sometimes', 'string', 'max:255'],
            'nik'                 => ['sometimes', 'nullable', 'string', 'size:16', Rule::unique('alumni', 'nik')->ignore($alumniId)],
            'birth_place'         => ['sometimes', 'nullable', 'string', 'max:100'],
            'birth_date'          => ['sometimes', 'nullable', 'date', 'before:today'],
            'gender'              => ['sometimes', 'nullable', Rule::in(['M', 'F'])],
            'religion'            => ['sometimes', 'nullable', 'string', 'max:50'],

            // Akademik
            'study_program_id'    => ['sometimes', 'integer', 'exists:study_programs,id'],
            'graduation_year_id'  => ['sometimes', 'integer', 'exists:graduation_years,id'],
            'gpa'                 => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:4.00'],
            'graduation_predicate'=> ['sometimes', 'nullable', 'string', 'max:50'],
            'thesis_title'        => ['sometimes', 'nullable', 'string', 'max:500'],

            // Alamat
            'address_street'      => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_village'     => ['sometimes', 'nullable', 'string', 'max:100'],
            'address_district'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'address_city'        => ['sometimes', 'nullable', 'string', 'max:100'],
            'address_province'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'address_postal_code' => ['sometimes', 'nullable', 'string', 'max:10'],

            // Kontak
            'phone'               => ['sometimes', 'nullable', 'string', 'max:20'],
            'linkedin_url'        => ['sometimes', 'nullable', 'url', 'max:255'],

            // Status (admin only)
            'is_active'           => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nim.unique'          => 'NIM sudah digunakan alumni lain.',
            'nik.unique'          => 'NIK sudah digunakan alumni lain.',
            'email.unique'        => 'Email sudah digunakan akun lain.',
            'gpa.max'             => 'IPK tidak boleh lebih dari 4.00.',
            'gender.in'           => 'Jenis kelamin harus M (Laki-laki) atau F (Perempuan).',
            'birth_date.before'   => 'Tanggal lahir harus sebelum hari ini.',
        ];
    }
}

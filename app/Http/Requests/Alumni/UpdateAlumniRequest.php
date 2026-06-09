<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateAlumniRequest
 * Validasi untuk PUT /api/v1/admin/alumni/{id}
 * Semua field opsional; unique check ignore record saat ini.
 */
class UpdateAlumniRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Admin update semua; alumni update miliknya sendiri (dicek di policy)
        $user = $this->user();
        return $user?->isAdmin() || $user?->isSuperadmin() || $user?->role === 'alumni';
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        // Ambil id alumni dari route parameter
        $alumniId = $this->route('alumni')?->id ?? $this->route('alumni');

        // Cari user_id dari record alumni untuk unique check di tabel users
        $userId = null;
        if ($alumniId) {
            $alumni  = \App\Models\Alumni::find($alumniId);
            $userId  = $alumni?->user_id;
        }

        return [
            'nim'                    => ['sometimes', 'string', 'max:20', Rule::unique('alumni', 'nim')->ignore($alumniId)],
            'nik'                    => ['sometimes', 'nullable', 'string', 'size:16', Rule::unique('alumni', 'nik')->ignore($alumniId)],
            'full_name'              => ['sometimes', 'string', 'max:255'],
            'gender'                 => ['sometimes', Rule::in(['L', 'P'])],
            'birth_place'            => ['sometimes', 'nullable', 'string', 'max:100'],
            'birth_date'             => ['sometimes', 'nullable', 'date', 'before:today'],
            'religion'               => ['sometimes', 'nullable', 'string', 'max:50'],
            'marital_status'         => ['sometimes', 'nullable', Rule::in(['belum_menikah', 'menikah', 'cerai'])],

            'study_program_id'       => ['sometimes', 'integer', 'exists:study_programs,id'],
            'graduation_year_id'     => ['sometimes', 'integer', 'exists:graduation_years,id'],
            'thesis_title'           => ['sometimes', 'nullable', 'string', 'max:500'],
            'gpa'                    => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:4.00'],
            'graduation_predicate'   => ['sometimes', 'nullable', Rule::in(['Memuaskan', 'Sangat Memuaskan', 'Cumlaude'])],

            'address_street'         => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_village'        => ['sometimes', 'nullable', 'string', 'max:100'],
            'address_district'       => ['sometimes', 'nullable', 'string', 'max:100'],
            'address_city'           => ['sometimes', 'nullable', 'string', 'max:100'],
            'address_province'       => ['sometimes', 'nullable', 'string', 'max:100'],
            'address_postal_code'    => ['sometimes', 'nullable', 'string', 'max:10'],
            'address_latitude'       => ['sometimes', 'nullable', 'numeric', 'min:-90', 'max:90'],
            'address_longitude'      => ['sometimes', 'nullable', 'numeric', 'min:-180', 'max:180'],

            'email'                  => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone'                  => ['sometimes', 'nullable', 'string', 'max:20'],

            'linkedin_url'           => ['sometimes', 'nullable', 'url', 'max:255'],
            'instagram_url'          => ['sometimes', 'nullable', 'url', 'max:255'],
        ];
    }

    /**
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'nim.unique'   => 'NIM sudah digunakan alumni lain.',
            'nik.size'     => 'NIK harus 16 digit.',
            'nik.unique'   => 'NIK sudah digunakan alumni lain.',
            'email.unique' => 'Email sudah digunakan.',
            'gpa.max'      => 'IPK maksimal 4.00.',
        ];
    }
}

<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlumniRequest extends FormRequest
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
        // Ambil alumni dari route parameter (bisa berupa Alumni model atau integer)
        $alumniId = $this->route('alumni') instanceof \App\Models\Alumni
            ? $this->route('alumni')->id
            : (int) $this->route('alumni');

        // Jika alumni adalah alumni yang sedang login, ambil ID-nya dari alumniId milik user
        // Fallback: gunakan ID dari route
        $userId = $this->user()?->id;

        return [
            // Email opsional saat update — uniqueness dikecualikan record ini
            'email'             => ['sometimes', 'email:rfc', 'max:150', Rule::unique('users', 'email')->ignore($this->user()?->id)],

            // Data pribadi (semua optional di update)
            'full_name'         => ['sometimes', 'string', 'max:150'],
            'nik'               => ['sometimes', 'nullable', 'string', 'size:16', Rule::unique('alumni', 'nik')->ignore($alumniId)],
            'birth_place'       => ['sometimes', 'nullable', 'string', 'max:100'],
            'birth_date'        => ['sometimes', 'nullable', 'date', 'before:today'],
            'gender'            => ['sometimes', 'nullable', Rule::in(['M', 'F'])],
            'religion'          => ['sometimes', 'nullable', 'string', 'max:50'],
            'phone'             => ['sometimes', 'nullable', 'string', 'max:20'],

            // Alamat
            'address_street'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_village'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'address_district'  => ['sometimes', 'nullable', 'string', 'max:100'],
            'city'              => ['sometimes', 'nullable', 'string', 'max:100'],
            'province'          => ['sometimes', 'nullable', 'string', 'max:100'],
            'postal_code'       => ['sometimes', 'nullable', 'string', 'max:10'],
            'latitude'          => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude'         => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],

            // Akademik — admin bisa update, alumni tidak bisa ubah nim/gpa
            'study_program_id'  => ['sometimes', 'integer', 'exists:study_programs,id'],
            'graduation_year_id'=> ['sometimes', 'integer', 'exists:graduation_years,id'],
            'gpa'               => ['sometimes', 'numeric', 'between:0.00,4.00'],
            'graduation_predicate' => ['sometimes', 'nullable', 'string', 'max:50'],
            'thesis_title'      => ['sometimes', 'nullable', 'string', 'max:500'],

            // Karir & sosial
            'employment_status' => ['sometimes', 'nullable', Rule::in(['employed', 'self_employed', 'entrepreneur', 'unemployed', 'continuing_study', 'not_seeking'])],
            'linkedin_url'      => ['sometimes', 'nullable', 'url', 'max:255'],
            'skills'            => ['sometimes', 'nullable', 'string'],

            // Admin-only fields
            'is_active'         => ['sometimes', 'boolean'],
        ];
    }
}

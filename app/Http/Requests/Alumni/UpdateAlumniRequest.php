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
            'nim'                    => ['sometimes', 'string', 'max:20',
                                         Rule::unique('alumni', 'nim')->ignore($alumniId)],
            'nik'                    => ['sometimes', 'nullable', 'string', 'size:16',
                                         Rule::unique('alumni', 'nik')->ignore($alumniId)],
            'full_name'              => ['sometimes', 'string', 'max:200'],
            'gender'                 => ['sometimes', Rule::in(['L', 'P'])],
            'birth_place'            => ['sometimes', 'nullable', 'string', 'max:100'],
            'birth_date'             => ['sometimes', 'nullable', 'date', 'before:today'],

            'study_program_id'       => ['sometimes', 'integer', 'exists:study_programs,id'],
            'graduation_year_id'     => ['sometimes', 'integer', 'exists:graduation_years,id'],
            'thesis_title'           => ['sometimes', 'nullable', 'string', 'max:500'],
            'gpa'                    => ['sometimes', 'nullable', 'numeric', 'between:0,4'],
            'graduation_predicate'   => ['sometimes', 'nullable', 'string', 'max:50'],

            'address_street'         => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_village'        => ['sometimes', 'nullable', 'string', 'max:100'],
            'address_district'       => ['sometimes', 'nullable', 'string', 'max:100'],
            'address_city'           => ['sometimes', 'nullable', 'string', 'max:100'],
            'address_province'       => ['sometimes', 'nullable', 'string', 'max:100'],
            'address_postal_code'    => ['sometimes', 'nullable', 'string', 'max:10'],
            'latitude'               => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude'              => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],

            'phone'                  => ['sometimes', 'nullable', 'string', 'max:20'],
            'email'                  => ['sometimes', 'email', 'max:200',
                                         Rule::unique('users', 'email')->ignore($userId)],
            'linkedin_url'           => ['sometimes', 'nullable', 'url', 'max:255'],

            // Foto profil — double-validate: extension + byte-level MIME (07_SECURITY.md §5)
            'photo'                  => [
                'sometimes',
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:2048',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.mimes'    => 'Foto harus berformat JPG, JPEG, PNG, atau WebP.',
            'photo.mimetypes'=> 'Tipe file foto tidak valid.',
            'photo.max'      => 'Ukuran foto maksimal 2 MB.',
        ];
    }
}

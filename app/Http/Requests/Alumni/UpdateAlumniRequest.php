<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlumniRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user  = $this->user();
        $alumni = $this->route('alumni');

        // Admin/Superadmin bisa update siapa pun
        if (in_array($user?->role, ['superadmin', 'admin'], true)) {
            return true;
        }

        // Alumni hanya bisa update datanya sendiri
        return $user?->role === 'alumni' && $user->id === $alumni?->user_id;
    }

    public function rules(): array
    {
        $alumniId = $this->route('alumni')?->id;

        return [
            'nim'                => ['sometimes', 'string', 'max:20', Rule::unique('alumni', 'nim')->ignore($alumniId)],
            'full_name'          => ['sometimes', 'string', 'max:255'],
            'gender'             => ['sometimes', Rule::in(['L', 'P'])],
            'study_program_id'   => ['sometimes', 'integer', 'exists:study_programs,id'],
            'graduation_year_id' => ['sometimes', 'integer', 'exists:graduation_years,id'],

            'nik'                        => ['nullable', 'string', 'max:20'],
            'birth_place'                => ['nullable', 'string', 'max:100'],
            'birth_date'                 => ['nullable', 'date', 'before:today'],
            'thesis_title'               => ['nullable', 'string'],
            'gpa'                        => ['nullable', 'numeric', 'min:0', 'max:4'],
            'graduation_predicate'       => ['nullable', 'string', 'max:50'],

            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore(
                    $this->route('alumni')?->user_id
                ),
            ],
            'phone' => ['nullable', 'string', 'max:20'],

            'address_street'      => ['nullable', 'string'],
            'address_village'     => ['nullable', 'string', 'max:100'],
            'address_district'    => ['nullable', 'string', 'max:100'],
            'address_city'        => ['nullable', 'string', 'max:100'],
            'address_province'    => ['nullable', 'string', 'max:100'],
            'address_postal_code' => ['nullable', 'string', 'max:10'],
            'address_latitude'    => ['nullable', 'numeric', 'between:-90,90'],
            'address_longitude'   => ['nullable', 'numeric', 'between:-180,180'],

            'linkedin_url' => ['nullable', 'url', 'max:255'],
        ];
    }
}

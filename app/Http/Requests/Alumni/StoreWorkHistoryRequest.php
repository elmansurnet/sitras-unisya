<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Admin/Superadmin selalu bisa
        if (in_array($this->user()?->role, ['superadmin', 'admin'], true)) {
            return true;
        }

        // Alumni hanya bisa menambah ke profilnya sendiri
        $alumni = $this->route('alumni');
        return $this->user()?->role === 'alumni'
            && $this->user()?->id === $alumni?->user_id;
    }

    public function rules(): array
    {
        return [
            'company_name'         => ['required', 'string', 'max:255'],
            'position'             => ['required', 'string', 'max:255'],
            'industry_sector'      => ['nullable', 'string', 'max:100'],
            'employment_type'      => [
                'nullable',
                Rule::in(['penuh_waktu', 'paruh_waktu', 'kontrak', 'freelance', 'wirausaha', 'magang']),
            ],
            'start_date'           => ['required', 'date'],
            'end_date'             => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_current'           => ['required', 'boolean'],
            'city'                 => ['nullable', 'string', 'max:100'],
            'province'             => ['nullable', 'string', 'max:100'],
            'country'              => ['nullable', 'string', 'max:100'],
            'monthly_salary_range' => ['nullable', 'string', 'max:50'],
            'is_relevant_to_study' => ['nullable', 'boolean'],
            'waiting_time_months'  => ['nullable', 'integer', 'min:0', 'max:255'],
            'description'          => ['nullable', 'string'],
            'employer_id'          => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Nama perusahaan wajib diisi.',
            'position.required'     => 'Jabatan/posisi wajib diisi.',
            'start_date.required'   => 'Tanggal mulai bekerja wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal berakhir harus setelah atau sama dengan tanggal mulai.',
        ];
    }
}

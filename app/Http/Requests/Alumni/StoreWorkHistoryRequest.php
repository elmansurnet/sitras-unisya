<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Alumni hanya bisa tambah riwayat miliknya sendiri;
        // Admin bisa tambah untuk alumni manapun.
        $user   = $this->user();
        $alumni = $this->route('alumni');

        if (!$user || !$alumni) {
            return false;
        }

        return $user->isAdmin() || $user->id === $alumni->user_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name'        => ['required', 'string', 'max:255'],
            'position'            => ['required', 'string', 'max:255'],
            'industry_sector_id'  => ['nullable', 'integer', 'exists:industry_sectors,id'],
            'salary_range_id'     => ['nullable', 'integer', 'exists:salary_ranges,id'],
            'employment_type'     => ['nullable', Rule::in(['full_time', 'part_time', 'freelance', 'contract', 'internship'])],
            'start_date'          => ['required', 'date'],
            'end_date'            => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_current'          => ['required', 'boolean'],
            'is_relevant_to_study'=> ['nullable', 'boolean'],
            'location_city'       => ['nullable', 'string', 'max:100'],
            'location_province'   => ['nullable', 'string', 'max:100'],
            'location_country'    => ['nullable', 'string', 'max:100'],
            'description'         => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_name.required' => 'Nama perusahaan wajib diisi.',
            'position.required'     => 'Jabatan/posisi wajib diisi.',
            'start_date.required'   => 'Tanggal mulai bekerja wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
            'employment_type.in'    => 'Tipe pekerjaan tidak valid.',
        ];
    }
}

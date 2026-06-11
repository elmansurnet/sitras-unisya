<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkHistoryRequest extends FormRequest
{
    /**
     * Alumni hanya bisa update riwayat miliknya sendiri;
     * Admin/Superadmin bisa update untuk alumni manapun.
     */
    public function authorize(): bool
    {
        $user        = $this->user();
        $alumni      = $this->route('alumni');
        $workHistory = $this->route('workHistory');

        if (!$user || !$alumni || !$workHistory) {
            return false;
        }

        // Pastikan work history memang milik alumni yang dimaksud
        if ((int) $workHistory->alumni_id !== (int) $alumni->id) {
            return false;
        }

        return $user->isAdmin() || $user->id === $alumni->user_id;
    }

    /**
     * Semua field bersifat `sometimes` (partial update diizinkan).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name'          => ['sometimes', 'required', 'string', 'max:200'],
            'position'              => ['sometimes', 'required', 'string', 'max:200'],
            'employment_type'       => [
                'sometimes',
                'required',
                Rule::in(['penuh_waktu', 'paruh_waktu', 'kontrak', 'magang', 'wirausaha']),
            ],
            'industry_sector'       => ['nullable', 'string', 'max:100'],
            'city'                  => ['nullable', 'string', 'max:100'],
            'province'              => ['nullable', 'string', 'max:100'],
            'start_date'            => ['sometimes', 'required', 'date'],
            'end_date'              => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_current'            => ['sometimes', 'boolean'],
            'salary_range_id'       => ['nullable', 'integer', 'exists:salary_ranges,id'],
            'job_relevance'         => [
                'nullable',
                Rule::in(['sangat_relevan', 'relevan', 'kurang_relevan', 'tidak_relevan']),
            ],
            'waiting_time_months'   => ['nullable', 'integer', 'min:0', 'max:120'],
            'description'           => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_name.required'     => 'Nama perusahaan wajib diisi.',
            'position.required'         => 'Jabatan/posisi wajib diisi.',
            'start_date.required'       => 'Tanggal mulai bekerja wajib diisi.',
            'end_date.after_or_equal'   => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
            'employment_type.required'  => 'Tipe pekerjaan wajib diisi.',
            'employment_type.in'        => 'Tipe pekerjaan tidak valid. Pilihan: penuh_waktu, paruh_waktu, kontrak, magang, wirausaha.',
            'job_relevance.in'          => 'Nilai relevansi tidak valid. Pilihan: sangat_relevan, relevan, kurang_relevan, tidak_relevan.',
            'waiting_time_months.min'   => 'Lama tunggu tidak boleh negatif.',
            'waiting_time_months.max'   => 'Lama tunggu maksimal 120 bulan.',
            'salary_range_id.exists'    => 'Rentang gaji yang dipilih tidak ditemukan.',
        ];
    }
}

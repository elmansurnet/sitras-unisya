<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request untuk menyimpan riwayat pekerjaan baru.
 *
 * Nama field sesuai PERSIS dengan kolom di tabel alumni_work_histories
 * (02_DATABASE.md §2.4) dan migration _000011.
 */
class StoreWorkHistoryRequest extends FormRequest
{
    /**
     * Alumni hanya bisa tambah riwayat miliknya sendiri;
     * Admin/Superadmin bisa tambah untuk alumni manapun.
     */
    public function authorize(): bool
    {
        $user   = $this->user();
        $alumni = $this->route('alumni');

        if (!$user || !$alumni) {
            return false;
        }

        return $user->isAdmin() || (int) $user->id === (int) $alumni->user_id;
    }

    /**
     * Validation rules.
     * ENUM values sesuai PERSIS dengan migration _000011:
     * penuh_waktu, paruh_waktu, kontrak, freelance, wirausaha, magang
     *
     * @return array<string, mixed>
     */
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
            'description'          => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_name.required'  => 'Nama perusahaan wajib diisi.',
            'position.required'      => 'Jabatan/posisi wajib diisi.',
            'start_date.required'    => 'Tanggal mulai bekerja wajib diisi.',
            'end_date.after_or_equal'=> 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
            'employment_type.in'     => 'Tipe pekerjaan tidak valid. Pilihan: penuh_waktu, paruh_waktu, kontrak, freelance, wirausaha, magang.',
            'waiting_time_months.max'=> 'Waktu tunggu maksimal 255 bulan.',
        ];
    }
}

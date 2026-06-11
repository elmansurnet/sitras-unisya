<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request untuk mengupdate riwayat pekerjaan.
 *
 * Nama field sesuai PERSIS dengan kolom di tabel alumni_work_histories
 * (02_DATABASE.md §2.4) dan migration _000011.
 * Semua field nullable (PATCH semantics — hanya field yang dikirim yang diupdate).
 */
class UpdateWorkHistoryRequest extends FormRequest
{
    /**
     * Alumni hanya bisa update riwayat miliknya sendiri;
     * Admin/Superadmin bisa update milik alumni manapun.
     * Ownership workHistory ↔ alumni diverifikasi juga di sini.
     */
    public function authorize(): bool
    {
        $user        = $this->user();
        $alumni      = $this->route('alumni');
        $workHistory = $this->route('workHistory');

        if (!$user || !$alumni || !$workHistory) {
            return false;
        }

        // Pastikan workHistory memang milik alumni yang dimaksud
        if ((int) $workHistory->alumni_id !== (int) $alumni->id) {
            return false;
        }

        return $user->isAdmin() || (int) $user->id === (int) $alumni->user_id;
    }

    /**
     * Validation rules — semua 'sometimes' (PATCH semantics).
     * ENUM values sesuai PERSIS migration _000011.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name'         => ['sometimes', 'required', 'string', 'max:255'],
            'position'             => ['sometimes', 'required', 'string', 'max:255'],
            'industry_sector'      => ['sometimes', 'nullable', 'string', 'max:100'],
            'employment_type'      => [
                'sometimes',
                'nullable',
                Rule::in(['penuh_waktu', 'paruh_waktu', 'kontrak', 'freelance', 'wirausaha', 'magang']),
            ],
            'start_date'           => ['sometimes', 'required', 'date'],
            'end_date'             => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'is_current'           => ['sometimes', 'required', 'boolean'],
            'city'                 => ['sometimes', 'nullable', 'string', 'max:100'],
            'province'             => ['sometimes', 'nullable', 'string', 'max:100'],
            'country'              => ['sometimes', 'nullable', 'string', 'max:100'],
            'monthly_salary_range' => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_relevant_to_study' => ['sometimes', 'nullable', 'boolean'],
            'waiting_time_months'  => ['sometimes', 'nullable', 'integer', 'min:0', 'max:255'],
            'description'          => ['sometimes', 'nullable', 'string', 'max:2000'],
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

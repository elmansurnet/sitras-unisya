<?php

namespace App\Http\Requests\SalaryRange;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // middleware CheckRole sudah handle otorisasi di route level
    }

    public function rules(): array
    {
        return [
            'label'        => ['required', 'string', 'max:100', 'unique:salary_ranges,label'],
            'min_value'    => ['nullable', 'integer', 'min:0'],
            'max_value'    => ['nullable', 'integer', 'min:0', 'gt:min_value'],
            'order_number' => ['nullable', 'integer', 'min:0'],
            'is_active'    => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required'      => 'Label rentang gaji wajib diisi.',
            'label.unique'        => 'Label rentang gaji sudah digunakan.',
            'label.max'           => 'Label tidak boleh lebih dari 100 karakter.',
            'min_value.integer'   => 'Nilai minimum harus berupa angka bulat.',
            'max_value.integer'   => 'Nilai maksimum harus berupa angka bulat.',
            'max_value.gt'        => 'Nilai maksimum harus lebih besar dari nilai minimum.',
        ];
    }
}

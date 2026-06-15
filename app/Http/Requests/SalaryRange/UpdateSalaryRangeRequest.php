<?php

namespace App\Http\Requests\SalaryRange;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalaryRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('salaryRange')?->id;

        return [
            'label'        => ['sometimes', 'string', 'max:100', Rule::unique('salary_ranges', 'label')->ignore($id)],
            'min_value'    => ['nullable', 'integer', 'min:0'],
            'max_value'    => ['nullable', 'integer', 'min:0'],
            'order_number' => ['nullable', 'integer', 'min:0'],
            'is_active'    => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.unique'      => 'Label rentang gaji sudah digunakan.',
            'label.max'         => 'Label tidak boleh lebih dari 100 karakter.',
            'min_value.integer' => 'Nilai minimum harus berupa angka bulat.',
            'max_value.integer' => 'Nilai maksimum harus berupa angka bulat.',
        ];
    }
}

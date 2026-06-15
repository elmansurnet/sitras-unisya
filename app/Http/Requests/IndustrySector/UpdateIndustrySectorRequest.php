<?php

namespace App\Http\Requests\IndustrySector;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIndustrySectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('industrySector')?->id;

        return [
            'name'      => ['sometimes', 'string', 'max:100', Rule::unique('industry_sectors', 'name')->ignore($id)],
            'code'      => ['nullable', 'string', 'max:20', Rule::unique('industry_sectors', 'code')->ignore($id)],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Nama sektor industri sudah digunakan.',
            'name.max'    => 'Nama tidak boleh lebih dari 100 karakter.',
            'code.unique' => 'Kode sektor industri sudah digunakan.',
            'code.max'    => 'Kode tidak boleh lebih dari 20 karakter.',
        ];
    }
}

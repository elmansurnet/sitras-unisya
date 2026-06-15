<?php

namespace App\Http\Requests\IndustrySector;

use Illuminate\Foundation\Http\FormRequest;

class StoreIndustrySectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:100', 'unique:industry_sectors,name'],
            'code'      => ['nullable', 'string', 'max:20', 'unique:industry_sectors,code'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama sektor industri wajib diisi.',
            'name.unique'   => 'Nama sektor industri sudah digunakan.',
            'name.max'      => 'Nama tidak boleh lebih dari 100 karakter.',
            'code.unique'   => 'Kode sektor industri sudah digunakan.',
            'code.max'      => 'Kode tidak boleh lebih dari 20 karakter.',
        ];
    }
}

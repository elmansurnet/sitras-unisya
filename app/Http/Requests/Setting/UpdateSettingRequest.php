<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('superadmin-only');
    }

    public function rules(): array
    {
        return [
            'value'       => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'value.required' => 'Nilai pengaturan tidak boleh kosong.',
        ];
    }
}

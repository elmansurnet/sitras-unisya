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
            // 'present' (bukan 'required') agar value string kosong "" diterima.
            // Beberapa setting sah dikosongkan (mis. wa_sender, footer opsional).
            'value' => ['present'],
        ];
    }

    public function messages(): array
    {
        return [
            'value.present' => 'Field value harus disertakan dalam request.',
        ];
    }
}

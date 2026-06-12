<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class BulkUpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('superadmin-only');
    }

    public function rules(): array
    {
        return [
            'settings'           => ['required', 'array', 'min:1'],
            'settings.*.key'     => ['required', 'string', 'exists:system_settings,key'],
            'settings.*.value'   => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'settings.required'         => 'Data pengaturan tidak boleh kosong.',
            'settings.array'            => 'Format data pengaturan tidak valid.',
            'settings.*.key.required'   => 'Kunci pengaturan wajib diisi.',
            'settings.*.key.exists'     => 'Kunci pengaturan tidak ditemukan.',
            'settings.*.value.required' => 'Nilai pengaturan tidak boleh kosong.',
        ];
    }
}

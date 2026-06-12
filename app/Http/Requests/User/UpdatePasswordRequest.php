<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('superadmin-only');
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}

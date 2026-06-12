<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('superadmin-only');
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['required', 'string', 'in:superadmin,admin,alumni,employer'],
            'is_active'=> ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'      => 'Email sudah terdaftar.',
            'password.min'      => 'Password minimal 8 karakter.',
            'password.confirmed'=> 'Konfirmasi password tidak cocok.',
            'role.in'           => 'Role tidak valid.',
        ];
    }
}

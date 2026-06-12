<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('superadmin-only');
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'email'     => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role'      => ['sometimes', 'required', 'string', 'in:superadmin,admin,alumni,employer'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email sudah digunakan oleh pengguna lain.',
            'role.in'      => 'Role tidak valid.',
        ];
    }
}

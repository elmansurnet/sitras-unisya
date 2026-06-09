<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class OtpVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier'      => ['required', 'string', 'max:100'],
            'identifier_type' => ['required', 'string', 'in:nim,email,phone'],
            'otp_code'        => ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required'      => 'Identifier wajib diisi.',
            'identifier_type.required' => 'Tipe identifier wajib dipilih.',
            'identifier_type.in'       => 'Tipe identifier harus salah satu dari: nim, email, phone.',
            'otp_code.required'        => 'Kode OTP wajib diisi.',
            'otp_code.digits'          => 'Kode OTP harus berupa 6 digit angka.',
        ];
    }
}

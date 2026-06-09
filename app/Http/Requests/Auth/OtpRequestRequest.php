<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class OtpRequestRequest extends FormRequest
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
            'channel'         => ['required', 'string', 'in:whatsapp,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required'           => 'Identifier wajib diisi.',
            'identifier_type.required'      => 'Tipe identifier wajib dipilih.',
            'identifier_type.in'            => 'Tipe identifier harus salah satu dari: nim, email, phone.',
            'channel.required'              => 'Channel pengiriman wajib dipilih.',
            'channel.in'                    => 'Channel harus salah satu dari: whatsapp, email.',
        ];
    }
}

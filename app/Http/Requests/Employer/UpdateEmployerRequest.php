<?php

namespace App\Http\Requests\Employer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name'            => ['sometimes', 'required', 'string', 'max:255'],
            'company_type'            => ['nullable', 'in:swasta,bumn,pemerintah,ngo,startup,lainnya'],
            'industry_sector'         => ['nullable', 'string', 'max:100'],
            'company_scale'           => ['nullable', 'in:mikro,kecil,menengah,besar,multinasional'],
            'address_street'          => ['nullable', 'string'],
            'address_city'            => ['nullable', 'string', 'max:100'],
            'address_province'        => ['nullable', 'string', 'max:100'],
            'address_country'         => ['nullable', 'string', 'max:100'],
            'phone'                   => ['nullable', 'string', 'max:20'],
            'email'                   => ['nullable', 'email', 'max:255'],
            'website'                 => ['nullable', 'url', 'max:255'],
            'contact_person_name'     => ['nullable', 'string', 'max:255'],
            'contact_person_position' => ['nullable', 'string', 'max:100'],
            'contact_person_email'    => ['nullable', 'email', 'max:255'],
            'contact_person_phone'    => ['nullable', 'string', 'max:20'],
            'notes'                   => ['nullable', 'string'],
        ];
    }
}

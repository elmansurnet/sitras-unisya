<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['superadmin', 'admin'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'channel'          => ['required', Rule::in(['whatsapp', 'email', 'both'])],
            'questionnaire_id' => ['required', 'integer', 'exists:questionnaires,id'],
        ];
    }
}

<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * SendInvitationRequest
 * Validasi untuk POST /api/v1/admin/alumni/{id}/invite
 * Sesuai 05_API.md §3.9
 */
class SendInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->isSuperadmin();
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'channel'          => ['required', Rule::in(['whatsapp', 'email', 'both'])],
            'questionnaire_id' => ['required', 'integer', 'exists:questionnaires,id'],
        ];
    }
}

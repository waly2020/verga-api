<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendTargetedMailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipient_type' => ['required', Rule::in(['client', 'agence', 'email'])],
            'client_id' => ['required_if:recipient_type,client', 'nullable', 'uuid', 'exists:clients,id'],
            'agence_id' => ['required_if:recipient_type,agence', 'nullable', 'uuid', 'exists:agences,id'],
            'email' => ['required_if:recipient_type,email', 'nullable', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:10000'],
            'action_label' => ['nullable', 'string', 'max:120'],
            'action_url' => ['nullable', 'url', 'max:2048', 'required_with:action_label'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'recipient_type.required' => 'Choisissez un type de destinataire.',
            'recipient_type.in' => 'Type de destinataire invalide.',
            'client_id.required_if' => 'Sélectionnez un client.',
            'agence_id.required_if' => 'Sélectionnez une agence.',
            'email.required_if' => 'Saisissez une adresse e-mail.',
            'email.email' => 'L\'adresse e-mail n\'est pas valide.',
            'subject.required' => 'L\'objet est obligatoire.',
            'message.required' => 'Le message est obligatoire.',
            'action_url.required_with' => 'L\'URL du bouton est obligatoire si un libellé est renseigné.',
            'action_url.url' => 'L\'URL du bouton n\'est pas valide.',
        ];
    }
}

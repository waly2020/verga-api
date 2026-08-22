<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendMassMailRequest extends FormRequest
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
            'audience' => ['required', Rule::in(['clients', 'agences', 'manual'])],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:10000'],
            'emails' => ['required_if:audience,manual', 'nullable', 'string', 'max:50000'],
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
            'audience.required' => 'Choisissez une audience.',
            'audience.in' => 'Audience de diffusion invalide.',
            'subject.required' => 'L\'objet est obligatoire.',
            'message.required' => 'Le message est obligatoire.',
            'emails.required_if' => 'Saisissez au moins une adresse e-mail.',
            'action_url.required_with' => 'L\'URL du bouton est obligatoire si un libellé est renseigné.',
            'action_url.url' => 'L\'URL du bouton n\'est pas valide.',
        ];
    }
}

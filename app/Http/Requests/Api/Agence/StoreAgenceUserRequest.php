<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Agence;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreAgenceUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:agence_users,email'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'agence_role_id' => [
                'required',
                'uuid',
                Rule::exists('agence_roles', 'id')->where(
                    fn ($query) => $query->where('actif', true)->where('est_systeme', false)
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'email est obligatoire.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'agence_role_id.required' => 'Le rôle est obligatoire.',
            'agence_role_id.exists' => 'Le rôle sélectionné est invalide.',
        ];
    }
}

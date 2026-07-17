<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Agence;

use App\Models\AgenceUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgenceUserRequest extends FormRequest
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
        /** @var AgenceUser|null $agenceUser */
        $agenceUser = $this->route('agenceUser');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('agence_users', 'email')->ignore($agenceUser?->id),
            ],
            'telephone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'agence_role_id' => [
                'sometimes',
                'uuid',
                Rule::exists('agence_roles', 'id')->where(
                    fn ($query) => $query->where('actif', true)->where('est_systeme', false)
                ),
            ],
            'statut' => ['sometimes', 'string', Rule::in([
                AgenceUser::STATUT_ACTIF,
                AgenceUser::STATUT_SUSPENDU,
            ])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Cet email est déjà utilisé.',
            'agence_role_id.exists' => 'Le rôle sélectionné est invalide.',
            'statut.in' => 'Le statut sélectionné est invalide.',
        ];
    }
}

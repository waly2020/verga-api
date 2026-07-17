<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\AgenceUser;
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
            'agence_id' => ['required', 'uuid', 'exists:agences,id'],
            'agence_role_id' => [
                'required',
                'uuid',
                Rule::exists('agence_roles', 'id')->where(
                    fn ($query) => $query->where('actif', true)->where('est_systeme', false)
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:agence_users,email'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'statut' => ['required', Rule::in([
                AgenceUser::STATUT_ACTIF,
                AgenceUser::STATUT_SUSPENDU,
            ])],
        ];
    }
}

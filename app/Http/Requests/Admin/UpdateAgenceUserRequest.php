<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

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
        /** @var AgenceUser $agenceUser */
        $agenceUser = $this->route('agenceUser');

        return [
            'agence_role_id' => [
                'required',
                'uuid',
                Rule::exists('agence_roles', 'id')->where(
                    fn ($query) => $query->where('actif', true)->where('est_systeme', false)
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('agence_users', 'email')->ignore($agenceUser->id),
            ],
            'telephone' => ['nullable', 'string', 'max:20'],
            'statut' => ['required', Rule::in([
                AgenceUser::STATUT_ACTIF,
                AgenceUser::STATUT_SUSPENDU,
            ])],
        ];
    }
}

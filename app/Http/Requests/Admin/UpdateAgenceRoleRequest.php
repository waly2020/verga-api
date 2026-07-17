<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\AgenceRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgenceRoleRequest extends FormRequest
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
        /** @var AgenceRole $role */
        $role = $this->route('agenceRole');

        return [
            'slug' => [
                'sometimes',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('agence_roles', 'slug')->ignore($role->id),
            ],
            'nom' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'actif' => ['sometimes', 'boolean'],
        ];
    }
}

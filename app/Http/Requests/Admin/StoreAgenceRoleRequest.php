<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgenceRoleRequest extends FormRequest
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
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:agence_roles,slug'],
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'actif' => ['sometimes', 'boolean'],
        ];
    }
}

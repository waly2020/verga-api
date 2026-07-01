<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConfigurationCommissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['fixe', 'pourcentage'])],
            'valeur' => [
                'required',
                'numeric',
                'min:0',
                Rule::when($this->input('type') === 'pourcentage', ['max:100']),
            ],
            'actif' => ['sometimes', 'boolean'],
            'libelle' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Le type de commission est obligatoire.',
            'type.in' => 'Le type doit être fixe ou pourcentage.',
            'valeur.required' => 'La valeur est obligatoire.',
            'valeur.min' => 'La valeur ne peut pas être négative.',
            'valeur.max' => 'Le pourcentage ne peut pas dépasser 100 %.',
        ];
    }
}

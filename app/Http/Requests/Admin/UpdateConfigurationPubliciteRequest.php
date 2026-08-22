<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConfigurationPubliciteRequest extends FormRequest
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
            'prix_par_jour' => ['required', 'integer', 'min:1'],
            'type_frais' => ['required', Rule::in(['fixe', 'pourcentage'])],
            'valeur_frais' => [
                'required',
                'integer',
                'min:0',
                Rule::when($this->input('type_frais') === 'pourcentage', ['max:100']),
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
            'prix_par_jour.required' => 'Le prix par jour est obligatoire.',
            'prix_par_jour.min' => 'Le prix par jour doit être un entier supérieur à zéro.',
            'prix_par_jour.integer' => 'Le prix par jour doit être un entier (FCFA).',
            'valeur_frais.integer' => 'La valeur des frais doit être un entier.',
            'type_frais.required' => 'Le type de frais est obligatoire.',
            'valeur_frais.required' => 'La valeur des frais est obligatoire.',
            'valeur_frais.max' => 'Le pourcentage ne peut pas dépasser 100 %.',
        ];
    }
}

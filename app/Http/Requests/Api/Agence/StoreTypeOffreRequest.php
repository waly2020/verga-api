<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Agence;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTypeOffreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $agenceId = $this->user()?->agence?->id;

        return [
            'slug' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('types_offres', 'slug')->where('agence_id', $agenceId),
            ],
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'unite' => ['required', 'string', 'max:50'],
            'unite_label' => ['required', 'string', 'max:100'],
            'quantite_entier' => ['sometimes', 'boolean'],
            'quantite_min' => ['required', 'numeric', 'min:0.001'],
            'actif' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.required' => 'Le code (slug) est obligatoire.',
            'slug.regex' => 'Le code doit commencer par une lettre et ne contenir que des minuscules, chiffres et underscores.',
            'slug.unique' => 'Ce code est déjà utilisé pour votre agence.',
            'nom.required' => 'Le nom est obligatoire.',
            'unite.required' => "L'unité est obligatoire.",
            'unite_label.required' => "Le libellé d'unité est obligatoire.",
            'quantite_min.required' => 'La quantité minimale est obligatoire.',
            'quantite_min.min' => 'La quantité minimale doit être supérieure à zéro.',
        ];
    }
}

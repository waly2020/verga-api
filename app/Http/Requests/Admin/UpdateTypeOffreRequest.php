<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTypeOffreRequest extends FormRequest
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
            'nom.required' => 'Le nom est obligatoire.',
            'unite.required' => "L'unité est obligatoire.",
            'unite_label.required' => "Le libellé d'unité est obligatoire.",
            'quantite_min.required' => 'La quantité minimale est obligatoire.',
            'quantite_min.min' => 'La quantité minimale doit être supérieure à zéro.',
        ];
    }
}

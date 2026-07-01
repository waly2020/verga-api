<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommandeRequest extends FormRequest
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
        $authenticated = (bool) $this->user()?->client;

        return [
            'offre_id' => ['required', 'uuid', 'exists:offres,id'],
            'quantite' => ['required', 'numeric', 'min:0.001'],
            'description' => ['nullable', 'string', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'max:5120'],
            'nom' => [$authenticated ? 'nullable' : 'required', 'string', 'max:255'],
            'prenom' => [$authenticated ? 'nullable' : 'required', 'string', 'max:255'],
            'telephone' => ['required', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'offre_id.required' => "L'offre est obligatoire.",
            'quantite.required' => 'La quantité est obligatoire.',
            'nom.required' => 'Le nom est obligatoire pour une commande sans compte.',
            'prenom.required' => 'Le prénom est obligatoire pour une commande sans compte.',
            'telephone.required' => 'Le téléphone est obligatoire.',
            'photos.*.image' => 'Chaque fichier doit être une image.',
        ];
    }
}

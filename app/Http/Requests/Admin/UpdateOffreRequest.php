<?php

namespace App\Http\Requests\Admin;

use App\Models\Offre;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOffreRequest extends FormRequest
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
            'agence_id' => ['required', 'uuid', 'exists:agences,id'],
            'titre' => ['required', 'string', 'max:255'],
            'type_offre_id' => ['required_without:type', 'uuid', 'exists:types_offres,id'],
            'type' => ['required_without:type_offre_id', Rule::in(['particulier', 'metre_cube', 'conteneur'])],
            'prix' => ['required', 'numeric', 'min:0'],
            'capacite_illimitee' => ['sometimes', 'boolean'],
            'capacite_totale' => ['required_unless:capacite_illimitee,true', 'nullable', 'numeric', 'min:0.001'],
            'origine' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'statut' => ['required', Rule::in(['active', 'inactive', 'archivée'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'agence_id.required' => "L'agence est obligatoire.",
            'agence_id.exists' => "Cette agence n'existe pas.",
            'titre.required' => 'Le titre est obligatoire.',
            'type_offre_id.required_without' => 'Le type d\'offre est obligatoire.',
            'type.required_without' => 'Le type est obligatoire.',
            'prix.required' => 'Le prix est obligatoire.',
            'prix.min' => 'Le prix ne peut pas être négatif.',
            'capacite_totale.required_unless' => 'La capacité totale est obligatoire pour une offre à stock limité.',
            'origine.required' => "L'origine est obligatoire.",
            'destination.required' => 'La destination est obligatoire.',
            'statut.required' => 'Le statut est obligatoire.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $illimitee = $this->has('capacite_illimitee')
            ? $this->boolean('capacite_illimitee')
            : $this->existingIllimitee();

        $this->merge(['capacite_illimitee' => $illimitee]);

        if ($illimitee) {
            $this->merge(['capacite_totale' => null]);
        }
    }

    private function existingIllimitee(): bool
    {
        $offre = $this->route('offre');

        return $offre instanceof Offre ? (bool) $offre->capacite_illimitee : false;
    }
}

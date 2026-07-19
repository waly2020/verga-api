<?php

namespace App\Http\Requests\Api\Agence;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOffreRequest extends FormRequest
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
        return [
            'titre' => ['required', 'string', 'max:255'],
            'type_offre_id' => ['required_without:type', 'uuid', 'exists:types_offres,id'],
            'type' => ['required_without:type_offre_id', 'string', 'max:50'],
            'prix' => ['required', 'numeric', 'min:0'],
            'capacite_illimitee' => ['sometimes', 'boolean'],
            'capacite_totale' => ['required_unless:capacite_illimitee,true', 'nullable', 'numeric', 'min:0.001'],
            'origine' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'date_depart' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'statut' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'titre.required' => 'Le titre est obligatoire.',
            'type_offre_id.required_without' => 'Le type d\'offre est obligatoire.',
            'type.required_without' => 'Le type est obligatoire.',
            'prix.required' => 'Le prix est obligatoire.',
            'prix.min' => 'Le prix ne peut pas être négatif.',
            'capacite_totale.required_unless' => 'La capacité totale est obligatoire pour une offre à stock limité.',
            'origine.required' => "L'origine est obligatoire.",
            'destination.required' => 'La destination est obligatoire.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('capacite_illimitee')) {
            $this->merge([
                'capacite_illimitee' => $this->boolean('capacite_illimitee'),
            ]);
        }

        if ($this->boolean('capacite_illimitee')) {
            $this->merge(['capacite_totale' => null]);
        }
    }
}

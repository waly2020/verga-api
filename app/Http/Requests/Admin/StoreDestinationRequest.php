<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDestinationRequest extends FormRequest
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
            'ville_depart_id' => ['required', 'uuid', 'exists:villes,id'],
            'ville_arrivee_id' => ['required', 'uuid', 'exists:villes,id', 'different:ville_depart_id'],
            'appliquer_configuration' => ['sometimes', 'boolean'],
            'montant' => ['required_if:appliquer_configuration,true', 'nullable', 'numeric', 'min:0.01'],
            'commission_pourcentage' => ['required_if:appliquer_configuration,true', 'nullable', 'numeric', 'min:0', 'max:100'],
            'actif' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ville_depart_id.required' => 'La ville de départ est obligatoire.',
            'ville_arrivee_id.required' => "La ville d'arrivée est obligatoire.",
            'ville_arrivee_id.different' => 'Le départ et l\'arrivée doivent être différents.',
            'montant.required_if' => 'Le montant est obligatoire lorsque la configuration est appliquée.',
            'montant.min' => 'Le montant doit être supérieur à zéro.',
            'commission_pourcentage.required_if' => 'La commission est obligatoire lorsque la configuration est appliquée.',
            'commission_pourcentage.max' => 'La commission ne peut pas dépasser 100 %.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('appliquer_configuration')) {
            $this->merge([
                'appliquer_configuration' => $this->boolean('appliquer_configuration'),
            ]);
        }

        if ($this->has('actif')) {
            $this->merge([
                'actif' => $this->boolean('actif'),
            ]);
        }

        if (! $this->boolean('appliquer_configuration')) {
            $this->merge([
                'montant' => null,
                'commission_pourcentage' => null,
            ]);
        }
    }
}

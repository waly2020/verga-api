<?php

namespace App\Http\Requests\Api\Agence;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDestinationRequest extends FormRequest
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
            'ville_depart_id' => ['required', 'uuid', 'exists:villes,id'],
            'ville_arrivee_id' => ['required', 'uuid', 'exists:villes,id', 'different:ville_depart_id'],
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
        ];
    }
}

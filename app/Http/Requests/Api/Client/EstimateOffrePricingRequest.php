<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EstimateOffrePricingRequest extends FormRequest
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
            'quantite' => ['required', 'numeric', 'min:0.001'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quantite.required' => 'La quantité est obligatoire.',
            'quantite.min' => 'La quantité doit être supérieure à zéro.',
        ];
    }
}

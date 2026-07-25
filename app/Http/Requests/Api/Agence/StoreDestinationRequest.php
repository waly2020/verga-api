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
            'depart' => ['required', 'string', 'max:255'],
            'arrivee' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'depart.required' => 'Le départ est obligatoire.',
            'arrivee.required' => "L'arrivée est obligatoire.",
        ];
    }
}

<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReclamationRequest extends FormRequest
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
            'commande_id' => ['nullable', 'uuid', 'exists:commandes,id'],
            'agence_id' => ['nullable', 'uuid', 'exists:agences,id'],
            'objet' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ];
    }
}

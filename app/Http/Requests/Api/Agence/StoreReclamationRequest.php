<?php

namespace App\Http\Requests\Api\Agence;

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
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'telephone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'objet' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ];
    }
}

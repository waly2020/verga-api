<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterClientRequest extends FormRequest
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
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:clients,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'telephone' => ['required', 'string', 'max:20'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:255'],
            'pays' => ['nullable', 'string', 'max:100'],
            'type' => ['sometimes', Rule::in(['particulier', 'entreprise', 'boutique'])],
            'device_name' => ['nullable', 'string', 'max:255'],
            'documents' => ['nullable', 'array', 'max:10'],
            'documents.*.fichier' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'documents.*.type_document' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'documents.*.fichier.required' => 'Chaque document doit contenir un fichier.',
            'documents.*.fichier.mimes' => 'Chaque document doit être une image ou un PDF.',
            'documents.*.type_document.required' => 'Chaque document doit avoir un type_document.',
        ];
    }
}

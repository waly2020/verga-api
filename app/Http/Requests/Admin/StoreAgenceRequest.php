<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreAgenceRequest extends FormRequest
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
            'gerant_name' => ['required', 'string', 'max:255'],
            'gerant_email' => ['required', 'email', 'max:255', 'unique:agence_users,email'],
            'gerant_password' => ['required', 'confirmed', Password::min(8)],
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:agences,email'],
            'telephone' => ['required', 'string', 'max:20'],
            'type_agence_id' => ['nullable', 'uuid', 'exists:type_agences,id'],
            'ville' => ['nullable', 'string', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'pays' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'max:5120'],
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
            'gerant_name.required' => 'Le nom du gérant est obligatoire.',
            'gerant_email.required' => "L'email du gérant est obligatoire.",
            'gerant_email.unique' => 'Cet email est déjà utilisé.',
            'gerant_password.required' => 'Le mot de passe est obligatoire.',
            'gerant_password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'nom.required' => "Le nom de l'agence est obligatoire.",
            'email.required' => "L'email de l'agence est obligatoire.",
            'email.unique' => 'Cet email est déjà utilisé par une autre agence.',
            'telephone.required' => 'Le téléphone est obligatoire.',
            'logo.image' => 'Le logo doit être une image.',
            'documents.*.fichier.required' => 'Chaque document doit contenir un fichier.',
            'documents.*.fichier.mimes' => 'Chaque document doit être une image ou un PDF.',
            'documents.*.type_document.required' => 'Chaque document doit avoir un type.',
        ];
    }
}

<?php

namespace App\Http\Requests\Concerns;

trait ValidatesPublicitePayload
{
    /**
     * @return array<string, mixed>
     */
    protected function publiciteRules(bool $imageRequired): array
    {
        return [
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'lien' => ['nullable', 'url', 'max:2048'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'image' => [$imageRequired ? 'required' : 'sometimes', 'image', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function publiciteMessages(): array
    {
        return [
            'titre.required' => 'Le titre est obligatoire.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_fin.required' => 'La date de fin est obligatoire.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'lien.url' => 'Le lien doit être une URL valide.',
            'image.required' => 'L\'image est obligatoire.',
            'image.image' => 'Le fichier doit être une image.',
            'image.max' => 'L\'image ne peut pas dépasser 5 Mo.',
        ];
    }
}

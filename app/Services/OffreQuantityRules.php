<?php

namespace App\Services;

use App\Models\Offre;
use Illuminate\Validation\ValidationException;

class OffreQuantityRules
{
    public function validate(Offre $offre, float ...$quantites): void
    {
        $offre->loadMissing('typeOffre');

        $min = (float) ($offre->typeOffre?->quantite_min ?? 0.001);
        $integerRequired = $offre->typeOffre?->quantite_entier ?? $offre->type === 'conteneur';
        $uniteLabel = $offre->typeOffre?->unite_label ?? 'cette offre';

        foreach ($quantites as $quantite) {
            if ($quantite <= 0) {
                throw ValidationException::withMessages([
                    'quantite' => ['La quantité doit être supérieure à zéro.'],
                ]);
            }

            if ($quantite < $min - 0.0001) {
                throw ValidationException::withMessages([
                    'quantite' => ["La quantité minimale est {$min} ({$uniteLabel})."],
                ]);
            }

            if ($integerRequired && floor($quantite) !== $quantite) {
                throw ValidationException::withMessages([
                    'quantite' => ["La quantité doit être un nombre entier ({$uniteLabel})."],
                ]);
            }
        }
    }
}

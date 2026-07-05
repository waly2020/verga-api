<?php

namespace App\Services;

use App\Models\Offre;
use Illuminate\Validation\ValidationException;

class OffreCapaciteService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function applyTotaleUpdate(Offre $offre, array $data): array
    {
        $reserved = (float) $offre->capacite_totale - (float) $offre->capacite_disponible;
        $newTotale = (float) $data['capacite_totale'];

        if ($newTotale < $reserved - 0.0001) {
            throw ValidationException::withMessages([
                'capacite_totale' => ['La capacité totale ne peut pas être inférieure à la quantité déjà réservée ('.$reserved.').'],
            ]);
        }

        $data['capacite_disponible'] = $newTotale - $reserved;

        return $data;
    }
}

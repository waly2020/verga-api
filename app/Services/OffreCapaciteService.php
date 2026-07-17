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
    public function normalizeForCreate(array $data): array
    {
        $illimitee = (bool) ($data['capacite_illimitee'] ?? false);
        $data['capacite_illimitee'] = $illimitee;

        if ($illimitee) {
            $data['capacite_totale'] = null;
            $data['capacite_disponible'] = null;

            return $data;
        }

        $data['capacite_disponible'] = $data['capacite_totale'];

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function applyTotaleUpdate(Offre $offre, array $data): array
    {
        $illimitee = array_key_exists('capacite_illimitee', $data)
            ? (bool) $data['capacite_illimitee']
            : $offre->capacite_illimitee;

        $data['capacite_illimitee'] = $illimitee;

        if ($illimitee) {
            $data['capacite_totale'] = null;
            $data['capacite_disponible'] = null;

            return $data;
        }

        $reserved = $offre->hasStockLimite()
            ? (float) $offre->capacite_totale - (float) $offre->capacite_disponible
            : 0.0;

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

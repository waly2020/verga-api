<?php

namespace App\Services;

use App\Models\Destination;
use Illuminate\Validation\ValidationException;

class OffreDestinationConfigService
{
    /**
     * Applique côté serveur la config destination (ne fait pas confiance au front).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function apply(array $data, Destination $destination): array
    {
        if ($destination->appliquer_configuration) {
            if ($destination->montant === null || $destination->commission_pourcentage === null) {
                throw ValidationException::withMessages([
                    'destination_id' => ['Cette destination a une configuration incomplète (montant ou commission manquant).'],
                ]);
            }

            $data['prix'] = (float) $destination->montant;
            $data['paliers'] = null;
        }

        $data['destination_id'] = $destination->id;

        return $data;
    }
}

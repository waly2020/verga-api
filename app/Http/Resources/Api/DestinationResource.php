<?php

namespace App\Http\Resources\Api;

use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Destination */
class DestinationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['villeDepart', 'villeArrivee']);

        return [
            'id' => $this->id,
            'ville_depart_id' => $this->ville_depart_id,
            'ville_arrivee_id' => $this->ville_arrivee_id,
            'ville_depart' => VilleResource::make($this->villeDepart),
            'ville_arrivee' => VilleResource::make($this->villeArrivee),
            'label' => $this->trajetLabel(),
            'montant' => $this->montant !== null ? (float) $this->montant : null,
            'commission_pourcentage' => $this->commission_pourcentage !== null
                ? (float) $this->commission_pourcentage
                : null,
            'appliquer_configuration' => (bool) $this->appliquer_configuration,
            'actif' => (bool) $this->actif,
            'rattachee' => $this->when(
                array_key_exists('rattachee', $this->resource->getAttributes()),
                (bool) $this->rattachee,
            ),
        ];
    }
}

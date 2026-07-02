<?php

namespace App\Http\Resources\Api\Client;

use App\Models\Offre;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Offre */
class OffreResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'description' => $this->description,
            'type' => $this->type,
            'prix' => $this->prix,
            'capacite_totale' => $this->capacite_totale,
            'capacite_disponible' => $this->capacite_disponible,
            'origine' => $this->origine,
            'destination' => $this->destination,
            'statut' => $this->statut,
            'created_at' => $this->created_at?->toIso8601String(),
            'agence' => $this->whenLoaded('agence', fn () => [
                'id' => $this->agence?->id,
                'nom' => $this->agence?->nom,
                'ville' => $this->agence?->ville,
            ]),
        ];
    }
}

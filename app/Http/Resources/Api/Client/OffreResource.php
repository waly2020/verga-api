<?php

namespace App\Http\Resources\Api\Client;

use App\Http\Resources\Api\TypeOffreResource;
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
            'type_offre_id' => $this->type_offre_id,
            'type_offre' => TypeOffreResource::make($this->whenLoaded('typeOffre')),
            'prix' => (float) $this->prix,
            'capacite_totale' => (float) $this->capacite_totale,
            'capacite_disponible' => (float) $this->capacite_disponible,
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

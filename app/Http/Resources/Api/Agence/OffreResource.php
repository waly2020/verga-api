<?php

namespace App\Http\Resources\Api\Agence;

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
            'capacite_illimitee' => (bool) $this->capacite_illimitee,
            'capacite_totale' => $this->capacite_totale !== null ? (float) $this->capacite_totale : null,
            'capacite_disponible' => $this->capacite_disponible !== null ? (float) $this->capacite_disponible : null,
            'origine' => $this->origine,
            'destination' => $this->destination,
            'date_depart' => $this->date_depart?->toDateString(),
            'statut' => $this->statut,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

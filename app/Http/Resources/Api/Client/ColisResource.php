<?php

namespace App\Http\Resources\Api\Client;

use App\Models\Colis;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Colis */
class ColisResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'poids' => $this->poids,
            'volume' => $this->volume,
            'statut' => $this->statut,
            'created_at' => $this->created_at?->toIso8601String(),
            'commande' => $this->whenLoaded('commande', fn () => [
                'id' => $this->commande?->id,
                'code' => $this->commande?->code,
            ]),
            'agence' => $this->whenLoaded('agence', fn () => [
                'id' => $this->agence?->id,
                'nom' => $this->agence?->nom,
            ]),
        ];
    }
}

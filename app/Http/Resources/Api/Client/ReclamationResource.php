<?php

namespace App\Http\Resources\Api\Client;

use App\Models\Reclamation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Reclamation */
class ReclamationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'objet' => $this->objet,
            'description' => $this->description,
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

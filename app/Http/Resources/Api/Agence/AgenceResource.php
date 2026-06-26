<?php

namespace App\Http\Resources\Api\Agence;

use App\Models\Agence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Agence */
class AgenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'adresse' => $this->adresse,
            'ville' => $this->ville,
            'pays' => $this->pays,
            'statut' => $this->statut,
            'type' => $this->whenLoaded('typeAgence', fn () => [
                'id' => $this->typeAgence?->id,
                'nom' => $this->typeAgence?->nom,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

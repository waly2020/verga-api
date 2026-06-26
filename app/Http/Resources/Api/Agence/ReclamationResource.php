<?php

namespace App\Http\Resources\Api\Agence;

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
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'objet' => $this->objet,
            'description' => $this->description,
            'statut' => $this->statut,
            'created_at' => $this->created_at?->toIso8601String(),
            'commande' => $this->whenLoaded('commande', fn () => [
                'id' => $this->commande?->id,
                'code' => $this->commande?->code,
            ]),
        ];
    }
}

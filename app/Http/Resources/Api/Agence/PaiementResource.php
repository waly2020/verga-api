<?php

namespace App\Http\Resources\Api\Agence;

use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Paiement */
class PaiementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'montant' => $this->montant,
            'methode' => $this->methode,
            'reference' => $this->reference,
            'statut' => $this->statut,
            'created_at' => $this->created_at?->toIso8601String(),
            'commande' => $this->whenLoaded('commande', fn () => [
                'id' => $this->commande?->id,
                'code' => $this->commande?->code,
            ]),
        ];
    }
}

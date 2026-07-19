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
            'code' => $this->code,
            'montant' => $this->statut === 'validé'
                ? (float) ($this->montant_agence ?? $this->montant_sous_total)
                : null,
            'statut' => $this->statut,
            'operateur' => $this->operateur,
            'bamboo_reference' => $this->bamboo_reference,
            'created_at' => $this->created_at?->toIso8601String(),
            'commande_code' => $this->when(
                $this->relationLoaded('commande'),
                fn () => $this->commande?->code,
            ),
        ];
    }
}

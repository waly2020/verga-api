<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

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
            'montant' => (float) $this->montant,
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

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
            'montant' => (float) $this->montant_sous_total,
            'created_at' => $this->created_at?->toIso8601String(),
            'bamboo_reference' => $this->bamboo_reference,
            'commande_code' => $this->when(
                $this->relationLoaded('commande'),
                fn () => $this->commande?->code,
            ),
        ];
    }
}

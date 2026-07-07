<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\Agence;

use App\Models\Reversement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Reversement */
class ReversementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'montant' => (float) $this->montant,
            'periode' => $this->periode,
            'statut' => $this->statut,
            'effectue_le' => $this->effectue_le?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources\Api\Agence;

use App\Models\HistoriqueColis;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HistoriqueColis */
class HistoriqueColisResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'statut' => $this->statut,
            'commentaire' => $this->commentaire,
            'created_at' => $this->created_at?->toIso8601String(),
            'actor' => $this->whenLoaded('actor', fn () => [
                'id' => $this->actor?->getKey(),
                'type' => $this->actor_type,
                'name' => $this->actor?->name,
            ]),
        ];
    }
}

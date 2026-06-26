<?php

namespace App\Http\Resources\Api\Client;

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
        ];
    }
}

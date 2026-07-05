<?php

namespace App\Http\Resources\Api\Agence;

use App\Http\Resources\Api\ColisPhotoResource;
use App\Models\Colis;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Colis */
class ColisDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'description' => $this->description,
            'poids' => $this->poids,
            'volume' => $this->volume,
            'statut' => $this->statut,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'commande' => CommandeResource::make($this->whenLoaded('commande')),
            'photos' => ColisPhotoResource::collection($this->whenLoaded('photos')),
            'historique' => HistoriqueColisResource::collection($this->whenLoaded('historique')),
        ];
    }
}

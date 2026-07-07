<?php

namespace App\Http\Resources\Api\Agence;

use App\Http\Resources\Api\ColisPhotoResource;
use App\Http\Resources\Api\Concerns\ResolvesQuantiteTypeOffre;
use App\Models\Colis;
use App\Support\QuantiteFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Colis */
class ColisResource extends JsonResource
{
    use ResolvesQuantiteTypeOffre;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $typeOffre = $this->resolvedQuantiteTypeOffre();
        $commande = $this->commande;

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'description' => $this->description,
            'poids' => $this->poids,
            'poids_label' => QuantiteFormatter::formatPoids($this->poids),
            'volume' => $this->volume,
            'quantite_label' => QuantiteFormatter::colisDisplay($this->poids, $commande?->quantite, $typeOffre),
            'statut' => $this->statut,
            'created_at' => $this->created_at?->toIso8601String(),
            'commande' => $this->whenLoaded('commande', function () use ($typeOffre, $commande) {
                return [
                    'id' => $commande?->id,
                    'code' => $commande?->code,
                    'quantite' => $commande?->quantite,
                    'quantite_label' => QuantiteFormatter::format($commande?->quantite, $typeOffre),
                ];
            }),
            'photos' => ColisPhotoResource::collection($this->whenLoaded('photos')),
        ];
    }
}

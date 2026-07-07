<?php

namespace App\Http\Resources\Api;

use App\Models\TypeOffre;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TypeOffre */
class TypeOffreResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agence_id' => $this->agence_id,
            'is_platform' => $this->isPlatform(),
            'slug' => $this->slug,
            'nom' => $this->nom,
            'description' => $this->description,
            'unite' => $this->unite,
            'unite_label' => $this->unite_label,
            'quantite_entier' => $this->quantite_entier,
            'quantite_min' => (float) $this->quantite_min,
            'actif' => $this->actif,
        ];
    }
}

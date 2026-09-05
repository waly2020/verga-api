<?php

namespace App\Http\Resources\Api;

use App\Models\Ville;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Ville */
class VilleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pays' => $this->pays,
            'ville' => $this->ville,
            'code' => $this->code,
            'label' => $this->label(),
            'actif' => (bool) $this->actif,
        ];
    }
}

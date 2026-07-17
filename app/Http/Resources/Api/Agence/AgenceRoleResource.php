<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\Agence;

use App\Models\AgenceRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AgenceRole */
class AgenceRoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'nom' => $this->nom,
            'description' => $this->description,
            'actif' => $this->actif,
            'est_systeme' => $this->est_systeme,
        ];
    }
}

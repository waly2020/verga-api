<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\Agence;

use App\Models\AgenceUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AgenceUser */
class AgenceUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'statut' => $this->statut,
            'est_proprietaire' => $this->est_proprietaire,
            'role' => AgenceRoleResource::make($this->whenLoaded('role')),
            'agence' => AgenceResource::make($this->whenLoaded('agence')),
        ];
    }
}

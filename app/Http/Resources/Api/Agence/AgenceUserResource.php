<?php

namespace App\Http\Resources\Api\Agence;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
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
            'role' => $this->role,
            'agence' => AgenceResource::make($this->whenLoaded('agence')),
        ];
    }
}

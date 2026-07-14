<?php

namespace App\Http\Resources\Api;

use App\Models\Logo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Logo */
class LogoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chemin' => $this->chemin,
            'url' => $this->url,
            'nom_original' => $this->nom_original,
        ];
    }
}

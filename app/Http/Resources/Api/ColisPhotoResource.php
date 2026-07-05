<?php

namespace App\Http\Resources\Api;

use App\Models\ColisPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin ColisPhoto */
class ColisPhotoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chemin' => $this->chemin,
            'url' => Storage::disk('public')->url($this->chemin),
            'ordre' => $this->ordre,
        ];
    }
}

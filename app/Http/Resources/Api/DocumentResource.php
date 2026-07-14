<?php

namespace App\Http\Resources\Api;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Document */
class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type_document' => $this->type_document,
            'chemin' => $this->chemin,
            'url' => $this->url,
            'nom_original' => $this->nom_original,
        ];
    }
}

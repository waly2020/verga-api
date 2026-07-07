<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\Concerns;

use App\Models\TypeOffre;

trait ResolvesQuantiteTypeOffre
{
    public ?TypeOffre $quantiteTypeOffre = null;

    public function withQuantiteTypeOffre(?TypeOffre $typeOffre): static
    {
        $this->quantiteTypeOffre = $typeOffre;

        return $this;
    }

    protected function resolvedQuantiteTypeOffre(): ?TypeOffre
    {
        if ($this->quantiteTypeOffre) {
            return $this->quantiteTypeOffre;
        }

        $commande = $this->resource->commande ?? null;

        if ($commande?->relationLoaded('offre') && $commande->offre?->relationLoaded('typeOffre')) {
            return $commande->offre->typeOffre;
        }

        $offre = $this->resource->offre ?? null;

        if ($offre?->relationLoaded('typeOffre')) {
            return $offre->typeOffre;
        }

        return null;
    }
}

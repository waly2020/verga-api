<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Agence;

use App\Http\Resources\Api\PaysNomResource;
use App\Models\Ville;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaysController extends AgenceApiController
{
    public function index(): AnonymousResourceCollection
    {
        $pays = Ville::nomsPays(true)
            ->map(fn (string $nom) => ['pays' => $nom])
            ->values();

        return PaysNomResource::collection($pays);
    }
}

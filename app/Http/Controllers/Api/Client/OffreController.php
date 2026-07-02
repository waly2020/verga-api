<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Requests\Api\Client\EstimateOffrePricingRequest;
use App\Http\Requests\Api\Client\ListClientOffresRequest;
use App\Http\Resources\Api\Client\OffreResource;
use App\Models\Offre;
use App\Services\ClientOffreCatalogService;
use App\Services\OrderPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OffreController extends ClientApiController
{
    public function index(
        ListClientOffresRequest $request,
        ClientOffreCatalogService $catalog,
    ): AnonymousResourceCollection {
        return OffreResource::collection(
            $catalog->paginate($request->validated())
        );
    }

    public function estimate(
        EstimateOffrePricingRequest $request,
        Offre $offre,
        OrderPricingService $pricing,
    ): JsonResponse {
        abort_unless($offre->statut === 'active', 404);

        return response()->json(
            $pricing->estimate($offre, (float) $request->validated('quantite'))
        );
    }
}

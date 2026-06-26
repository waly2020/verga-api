<?php

namespace App\Http\Controllers\Api\Agence;

use App\Http\Requests\Api\Agence\StoreOffreRequest;
use App\Http\Resources\Api\Agence\OffreResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OffreController extends AgenceApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->agence($request)->offres()->latest();

        if ($search = $request->get('search')) {
            $query->where('titre', 'like', "%{$search}%");
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return OffreResource::collection(
            $query->paginate($request->integer('per_page', 15))->withQueryString()
        );
    }

    public function show(Request $request, string $offre): OffreResource
    {
        $model = $this->agence($request)->offres()->findOrFail($offre);

        return OffreResource::make($model);
    }

    public function store(StoreOffreRequest $request): JsonResponse
    {
        $offre = $this->agence($request)->offres()->create([
            ...$request->validated(),
            'statut' => $request->input('statut', 'active'),
        ]);

        return OffreResource::make($offre)
            ->response()
            ->setStatusCode(201);
    }
}

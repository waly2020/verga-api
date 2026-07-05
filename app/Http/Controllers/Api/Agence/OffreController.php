<?php

namespace App\Http\Controllers\Api\Agence;

use App\Http\Requests\Api\Agence\StoreOffreRequest;
use App\Http\Resources\Api\Agence\OffreResource;
use App\Services\OffreTypeResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OffreController extends AgenceApiController
{
    public function __construct(
        private readonly OffreTypeResolver $typeResolver,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->agence($request)
            ->offres()
            ->with('typeOffre:id,slug,nom,unite_label')
            ->latest();

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
        $model = $this->agence($request)
            ->offres()
            ->with('typeOffre')
            ->findOrFail($offre);

        return OffreResource::make($model);
    }

    public function store(StoreOffreRequest $request): JsonResponse
    {
        $data = $this->typeResolver->resolveForCreate($request->validated());
        $data['capacite_disponible'] = $data['capacite_totale'];
        $data['statut'] = $request->input('statut', 'active');

        $offre = $this->agence($request)
            ->offres()
            ->create($data)
            ->load('typeOffre');

        return OffreResource::make($offre)
            ->response()
            ->setStatusCode(201);
    }
}

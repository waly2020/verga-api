<?php

namespace App\Http\Controllers\Api\Agence;

use App\Http\Requests\Api\Agence\StoreOffreRequest;
use App\Http\Requests\Api\Agence\UpdateOffreRequest;
use App\Http\Resources\Api\Agence\OffreResource;
use App\Models\Offre;
use App\Services\OffreCapaciteService;
use App\Services\OffreTypeResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class OffreController extends AgenceApiController
{
    public function __construct(
        private readonly OffreTypeResolver $typeResolver,
        private readonly OffreCapaciteService $capacite,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->agence($request)
            ->offres()
            ->with([
                'typeOffre:id,slug,nom,unite_label',
                'destination:id,ville_depart_id,ville_arrivee_id,montant,commission_pourcentage,appliquer_configuration,actif',
                'destination.villeDepart',
                'destination.villeArrivee',
            ])
            ->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                    ->orWhereHas('destination', fn ($dq) => $dq->matchingLocalite($search));
            });
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
            ->with(['typeOffre', 'destination.villeDepart', 'destination.villeArrivee'])
            ->findOrFail($offre);

        return OffreResource::make($model);
    }

    public function store(StoreOffreRequest $request): JsonResponse
    {
        $data = $this->typeResolver->resolveForCreate($request->validated(), $this->agence($request)->id);
        $data = $this->capacite->normalizeForCreate($data);
        $data['statut'] = $request->input('statut', 'active');

        $offre = $this->agence($request)
            ->offres()
            ->create($data)
            ->load(['typeOffre', 'destination.villeDepart', 'destination.villeArrivee']);

        return OffreResource::make($offre)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateOffreRequest $request, string $offre): OffreResource
    {
        $model = $this->findAgenceOffre($request, $offre);

        $data = $this->typeResolver->resolveForUpdate($request->validated(), $this->agence($request)->id);
        $data = $this->capacite->applyTotaleUpdate($model, $data);

        $model->update($data);

        return OffreResource::make($model->fresh(['typeOffre', 'destination.villeDepart', 'destination.villeArrivee']));
    }

    public function destroy(Request $request, string $offre): JsonResponse
    {
        $model = $this->findAgenceOffre($request, $offre);

        if ($model->commandes()->exists()) {
            throw ValidationException::withMessages([
                'offre' => ['Impossible de supprimer une offre liée à des commandes existantes.'],
            ]);
        }

        $model->delete();

        return response()->json([
            'message' => 'Offre supprimée avec succès.',
        ]);
    }

    private function findAgenceOffre(Request $request, string $offre): Offre
    {
        return $this->agence($request)
            ->offres()
            ->findOrFail($offre);
    }
}

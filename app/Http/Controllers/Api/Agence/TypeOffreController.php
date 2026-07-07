<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Agence;

use App\Http\Requests\Api\Agence\StoreTypeOffreRequest;
use App\Http\Requests\Api\Agence\UpdateTypeOffreRequest;
use App\Http\Resources\Api\TypeOffreResource;
use App\Models\TypeOffre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class TypeOffreController extends AgenceApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = TypeOffre::query()->orderBy('nom');

        if ($agence = $request->user()?->agence) {
            $query->where(function ($q) use ($agence) {
                $q->where(function ($q) {
                    $q->platform()->actif();
                })->orWhere('agence_id', $agence->id);
            });
        } else {
            $query->platform()->actif();
        }

        return TypeOffreResource::collection($query->get());
    }

    public function store(StoreTypeOffreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['agence_id'] = $this->agence($request)->id;
        $validated['quantite_entier'] = $request->boolean('quantite_entier');
        $validated['actif'] = $request->boolean('actif', true);

        $typeOffre = TypeOffre::create($validated);

        return TypeOffreResource::make($typeOffre)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, string $typeOffre): TypeOffreResource
    {
        return TypeOffreResource::make($this->findAccessibleTypeOffre($request, $typeOffre));
    }

    public function update(UpdateTypeOffreRequest $request, string $typeOffre): TypeOffreResource
    {
        $model = $this->findOwnedTypeOffre($request, $typeOffre);

        $validated = $request->validated();

        if ($request->has('quantite_entier')) {
            $validated['quantite_entier'] = $request->boolean('quantite_entier');
        }

        if ($request->has('actif')) {
            $validated['actif'] = $request->boolean('actif');
        }

        $model->update($validated);

        return TypeOffreResource::make($model->fresh());
    }

    public function destroy(Request $request, string $typeOffre): JsonResponse
    {
        $model = $this->findOwnedTypeOffre($request, $typeOffre);

        if ($model->offres()->exists()) {
            throw ValidationException::withMessages([
                'type_offre' => ['Impossible de supprimer un type utilisé par des offres existantes.'],
            ]);
        }

        $model->delete();

        return response()->json([
            'message' => 'Type d\'offre supprimé avec succès.',
        ]);
    }

    private function findAccessibleTypeOffre(Request $request, string $typeOffre): TypeOffre
    {
        $model = TypeOffre::query()->findOrFail($typeOffre);
        $agenceId = $this->agence($request)->id;

        if ($model->isPlatform() || $model->isOwnedByAgence($agenceId)) {
            return $model;
        }

        abort(403, 'Accès non autorisé à ce type d\'offre.');
    }

    private function findOwnedTypeOffre(Request $request, string $typeOffre): TypeOffre
    {
        $model = TypeOffre::query()->findOrFail($typeOffre);

        if (! $model->isOwnedByAgence($this->agence($request)->id)) {
            abort(403, 'Seuls vos types d\'offre personnalisés peuvent être modifiés.');
        }

        return $model;
    }
}

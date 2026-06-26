<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Requests\Api\Client\StoreReclamationRequest;
use App\Http\Resources\Api\Client\ReclamationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ReclamationController extends ClientApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->client($request)
            ->reclamations()
            ->with(['commande:id,code', 'agence:id,nom'])
            ->latest();

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return ReclamationResource::collection(
            $query->paginate($request->integer('per_page', 15))->withQueryString()
        );
    }

    public function show(Request $request, string $reclamation): ReclamationResource
    {
        $model = $this->client($request)
            ->reclamations()
            ->with(['commande:id,code,montant_total,statut', 'agence:id,nom'])
            ->findOrFail($reclamation);

        return ReclamationResource::make($model);
    }

    public function store(StoreReclamationRequest $request): JsonResponse
    {
        $client = $this->client($request);

        if ($request->filled('commande_id')) {
            $ownsCommande = $client->commandes()
                ->where('id', $request->commande_id)
                ->exists();

            if (! $ownsCommande) {
                throw ValidationException::withMessages([
                    'commande_id' => ['Cette commande ne vous appartient pas.'],
                ]);
            }
        }

        $reclamation = $client->reclamations()->create([
            'commande_id' => $request->commande_id,
            'agence_id' => $request->agence_id,
            'nom' => $client->nom,
            'prenom' => $client->prenom,
            'telephone' => $client->telephone,
            'email' => $client->email,
            'objet' => $request->objet,
            'description' => $request->description,
            'statut' => 'ouverte',
        ]);

        $reclamation->load(['commande:id,code', 'agence:id,nom']);

        return ReclamationResource::make($reclamation)
            ->response()
            ->setStatusCode(201);
    }
}

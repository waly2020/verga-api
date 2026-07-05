<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Requests\Api\Client\StoreCommandeRequest;
use App\Http\Requests\Api\Client\StoreSoldePaiementRequest;
use App\Http\Resources\Api\Client\CommandeResource;
use App\Services\CommandeCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;

class CommandeController extends ClientApiController
{
    public function store(
        StoreCommandeRequest $request,
        CommandeCheckoutService $checkout,
    ): JsonResponse {
        $client = $request->user()?->client;

        $data = $request->validated();

        if ($client) {
            $data['nom'] = $data['nom'] ?? $client->nom;
            $data['prenom'] = $data['prenom'] ?? $client->prenom;
            $data['telephone'] = $data['telephone'] ?? $client->telephone;
        }

        /** @var array<int, UploadedFile> $photos */
        $photos = $request->file('photos', []);

        $result = $checkout->checkout(
            data: $data,
            photos: is_array($photos) ? $photos : [],
            clientId: $client?->id,
        );

        return response()->json($result, 201);
    }

    public function storePaiement(
        StoreSoldePaiementRequest $request,
        string $commande,
        CommandeCheckoutService $checkout,
    ): JsonResponse {
        $model = $this->client($request)
            ->commandes()
            ->findOrFail($commande);

        $result = $checkout->payBalance($model, (float) $request->validated('quantite'));

        return response()->json($result, 201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->client($request)
            ->commandes()
            ->with(['agence:id,nom', 'offre.typeOffre'])
            ->latest();

        if ($search = $request->get('search')) {
            $query->where('code', 'like', "%{$search}%");
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return CommandeResource::collection(
            $query->paginate($request->integer('per_page', 15))->withQueryString()
        );
    }

    public function show(Request $request, string $commande): CommandeResource
    {
        $model = $this->client($request)
            ->commandes()
            ->with(['agence:id,nom,email,ville', 'offre.typeOffre', 'paiement', 'colis.photos'])
            ->findOrFail($commande);

        return CommandeResource::make($model);
    }
}

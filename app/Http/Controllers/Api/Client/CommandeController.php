<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Resources\Api\Client\CommandeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommandeController extends ClientApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->client($request)
            ->commandes()
            ->with(['agence:id,nom', 'offre:id,titre,type'])
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
            ->with(['agence:id,nom,email,ville', 'offre', 'paiement', 'colis'])
            ->findOrFail($commande);

        return CommandeResource::make($model);
    }
}

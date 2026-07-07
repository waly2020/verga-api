<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Resources\Api\Client\ColisDetailResource;
use App\Http\Resources\Api\Client\ColisResource;
use App\Models\Colis;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ColisController extends ClientApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $clientId = $this->client($request)->id;

        $query = Colis::query()
            ->with([
                'commande:id,code,client_id,quantite,offre_id',
                'commande.offre:id,type_offre_id',
                'commande.offre.typeOffre:id,unite,quantite_entier',
                'agence:id,nom',
                'photos',
            ])
            ->whereHas('commande', fn ($q) => $q->where('client_id', $clientId))
            ->latest();

        if ($search = $request->get('search')) {
            $query->where('reference', 'like', "%{$search}%");
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return ColisResource::collection(
            $query->paginate($request->integer('per_page', 15))->withQueryString()
        );
    }

    public function show(Request $request, string $colis): ColisDetailResource
    {
        $clientId = $this->client($request)->id;

        $model = Colis::query()
            ->whereHas('commande', fn ($q) => $q->where('client_id', $clientId))
            ->with([
                'agence:id,nom',
                'commande.offre.typeOffre',
                'photos',
                'historique' => fn ($q) => $q->latest(),
            ])
            ->findOrFail($colis);

        return ColisDetailResource::make($model);
    }
}

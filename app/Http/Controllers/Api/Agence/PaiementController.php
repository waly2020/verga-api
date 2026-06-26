<?php

namespace App\Http\Controllers\Api\Agence;

use App\Http\Resources\Api\Agence\PaiementResource;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaiementController extends AgenceApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $agenceId = $this->agence($request)->id;

        $query = Paiement::query()
            ->with('commande:id,code,agence_id')
            ->whereHas('commande', fn ($q) => $q->where('agence_id', $agenceId))
            ->latest();

        if ($search = $request->get('search')) {
            $query->where('reference', 'like', "%{$search}%");
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return PaiementResource::collection(
            $query->paginate($request->integer('per_page', 15))->withQueryString()
        );
    }
}

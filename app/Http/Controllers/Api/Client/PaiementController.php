<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Resources\Api\Client\PaiementResource;
use App\Models\Paiement;
use App\Services\CommandeCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaiementController extends ClientApiController
{
    public function statut(string $code, CommandeCheckoutService $checkout): JsonResponse
    {
        return response()->json($checkout->verifyPaymentStatus($code));
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $clientId = $this->client($request)->id;

        $query = Paiement::query()
            ->with('commande:id,code,client_id')
            ->whereHas('commande', fn ($q) => $q->where('client_id', $clientId))
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

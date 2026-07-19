<?php

namespace App\Http\Controllers\Api\Agence;

use App\Http\Requests\Api\Agence\UpdateCommandeStatutRequest;
use App\Http\Resources\Api\Agence\CommandeResource;
use App\Models\Paiement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class CommandeController extends AgenceApiController
{
    private const TRANSITIONS = [
        'en_attente' => ['confirmée', 'annulée'],
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->agence($request)
            ->commandes()
            ->with(['client:id,nom,prenom,email,telephone', 'offre.typeOffre'])
            ->addSelect(['montant_agence' => $this->montantAgenceSubquery()])
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
        $model = $this->agence($request)
            ->commandes()
            ->with(['client:id,nom,prenom,email,telephone', 'offre.typeOffre', 'paiement', 'colis.photos'])
            ->addSelect(['montant_agence' => $this->montantAgenceSubquery()])
            ->findOrFail($commande);

        return CommandeResource::make($model);
    }

    public function updateStatut(UpdateCommandeStatutRequest $request, string $commande): CommandeResource|JsonResponse
    {
        $model = $this->agence($request)
            ->commandes()
            ->addSelect(['montant_agence' => $this->montantAgenceSubquery()])
            ->findOrFail($commande);

        $allowed = self::TRANSITIONS[$model->statut] ?? [];

        if (! in_array($request->statut, $allowed, true)) {
            throw ValidationException::withMessages([
                'statut' => ['Transition de statut non autorisée pour cette commande.'],
            ]);
        }

        $model->update(['statut' => $request->statut]);

        $model->load(['client:id,nom,prenom,email', 'offre.typeOffre']);

        return CommandeResource::make($model);
    }

    private function montantAgenceSubquery(): Builder
    {
        return Paiement::query()
            ->selectRaw('SUM(COALESCE(montant_agence, montant_sous_total))')
            ->whereColumn('commande_id', 'commandes.id')
            ->where('statut', 'validé');
    }
}

<?php

namespace App\Http\Controllers\Api\Agence;

use App\Http\Requests\Api\Agence\UpdateColisStatutRequest;
use App\Http\Resources\Api\Agence\ColisDetailResource;
use App\Http\Resources\Api\Agence\ColisResource;
use App\Models\Colis;
use App\Services\ColisStatutService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ColisController extends AgenceApiController
{
    public function __construct(
        private readonly ColisStatutService $statutService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->agence($request)
            ->colis()
            ->with([
                'commande:id,code,quantite,offre_id',
                'commande.offre:id,type_offre_id',
                'commande.offre.typeOffre:id,unite,quantite_entier',
                'photos',
            ])
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
        $model = $this->loadColisDetail($request, $colis);

        return ColisDetailResource::make($model)->additional([
            'next_statut' => $this->statutService->nextStatut($model->statut),
        ]);
    }

    public function updateStatut(UpdateColisStatutRequest $request, string $colis): ColisDetailResource
    {
        $model = $this->agence($request)->colis()->findOrFail($colis);

        $updated = $this->statutService->advance(
            colis: $model,
            actor: $request->user(),
            statut: $request->validated('statut'),
            commentaire: $request->validated('commentaire'),
            dateStatut: $request->validated('date_statut'),
        );

        $updated->load([
            'commande.client:id,nom,prenom,email',
            'commande.offre.typeOffre',
            'photos',
            'historique' => fn ($q) => $q->with('actor')->latest(),
        ]);

        return ColisDetailResource::make($updated)->additional([
            'next_statut' => $this->statutService->nextStatut($updated->statut),
        ]);
    }

    private function loadColisDetail(Request $request, string $colis): Colis
    {
        return $this->agence($request)
            ->colis()
            ->with([
                'commande.client:id,nom,prenom,email',
                'commande.offre.typeOffre',
                'photos',
                'historique' => fn ($q) => $q->with('actor')->latest(),
            ])
            ->findOrFail($colis);
    }
}

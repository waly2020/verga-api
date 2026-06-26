<?php

namespace App\Http\Controllers\Api\Agence;

use App\Http\Requests\Api\Agence\UpdateColisStatutRequest;
use App\Http\Resources\Api\Agence\ColisDetailResource;
use App\Http\Resources\Api\Agence\ColisResource;
use App\Models\HistoriqueColis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ColisController extends AgenceApiController
{
    private const FLUX = [
        'déposé' => 'en_transit',
        'en_transit' => 'arrivé',
        'arrivé' => 'récupéré',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->agence($request)
            ->colis()
            ->with('commande:id,code')
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
        $model = $this->agence($request)
            ->colis()
            ->with([
                'commande.client:id,nom,prenom,email',
                'commande.offre:id,titre',
                'historique' => fn ($q) => $q->with('user:id,name')->latest(),
            ])
            ->findOrFail($colis);

        return ColisDetailResource::make($model)->additional([
            'next_statut' => self::FLUX[$model->statut] ?? null,
        ]);
    }

    public function updateStatut(UpdateColisStatutRequest $request, string $colis): ColisDetailResource|JsonResponse
    {
        $model = $this->agence($request)->colis()->findOrFail($colis);

        $next = self::FLUX[$model->statut] ?? null;

        if (! $next) {
            return response()->json([
                'message' => 'Ce colis est dans son statut final.',
            ], 422);
        }

        $model->update(['statut' => $next]);

        HistoriqueColis::create([
            'colis_id' => $model->id,
            'user_id' => $request->user()->id,
            'statut' => $next,
            'commentaire' => $request->commentaire,
        ]);

        $model->load([
            'commande.client:id,nom,prenom,email',
            'commande.offre:id,titre',
            'historique' => fn ($q) => $q->with('user:id,name')->latest(),
        ]);

        return ColisDetailResource::make($model)->additional([
            'next_statut' => self::FLUX[$model->statut] ?? null,
        ]);
    }
}

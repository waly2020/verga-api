<?php

namespace App\Http\Controllers\Api\Agence;

use App\Http\Requests\Api\Agence\StoreReclamationRequest;
use App\Http\Requests\Api\Agence\UpdateReclamationStatutRequest;
use App\Http\Resources\Api\Agence\ReclamationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ReclamationController extends AgenceApiController
{
    private const TRANSITIONS = [
        'ouverte' => ['en_cours', 'fermée'],
        'en_cours' => ['résolue', 'fermée'],
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->agence($request)
            ->reclamations()
            ->with('commande:id,code')
            ->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('objet', 'like', "%{$search}%");
            });
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return ReclamationResource::collection(
            $query->paginate($request->integer('per_page', 15))->withQueryString()
        );
    }

    public function show(Request $request, string $reclamation): ReclamationResource
    {
        $model = $this->agence($request)
            ->reclamations()
            ->with('commande:id,code,montant_total,statut')
            ->findOrFail($reclamation);

        return ReclamationResource::make($model);
    }

    public function store(StoreReclamationRequest $request): JsonResponse
    {
        $agence = $this->agence($request);

        if ($request->filled('commande_id')) {
            $belongsToAgence = $agence->commandes()
                ->where('id', $request->commande_id)
                ->exists();

            if (! $belongsToAgence) {
                throw ValidationException::withMessages([
                    'commande_id' => ['Cette commande n\'appartient pas à votre agence.'],
                ]);
            }
        }

        $reclamation = $agence->reclamations()->create([
            ...$request->validated(),
            'statut' => 'ouverte',
        ]);

        $reclamation->load('commande:id,code');

        return ReclamationResource::make($reclamation)
            ->response()
            ->setStatusCode(201);
    }

    public function updateStatut(UpdateReclamationStatutRequest $request, string $reclamation): ReclamationResource|JsonResponse
    {
        $model = $this->agence($request)->reclamations()->findOrFail($reclamation);

        $allowed = self::TRANSITIONS[$model->statut] ?? [];

        if (empty($allowed)) {
            return response()->json([
                'message' => 'Cette réclamation est déjà dans un état final.',
            ], 422);
        }

        if (! in_array($request->statut, $allowed, true)) {
            throw ValidationException::withMessages([
                'statut' => ['Transition de statut non autorisée pour cette réclamation.'],
            ]);
        }

        $model->update(['statut' => $request->statut]);
        $model->load('commande:id,code');

        return ReclamationResource::make($model);
    }
}

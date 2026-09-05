<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Agence;

use App\Http\Requests\Api\Agence\StoreDestinationRequest;
use App\Http\Resources\Api\DestinationResource;
use App\Models\Destination;
use App\Services\DestinationResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DestinationController extends AgenceApiController
{
    public function __construct(
        private readonly DestinationResolver $destinationResolver,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return DestinationResource::collection(
            $this->destinationsQuery($request)->get()
        );
    }

    public function paginated(Request $request): AnonymousResourceCollection
    {
        $perPage = min(100, max(1, $request->integer('per_page', 15)));

        return DestinationResource::collection(
            $this->destinationsQuery($request)
                ->paginate($perPage)
                ->withQueryString()
        );
    }

    public function store(StoreDestinationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $destination = $this->destinationResolver->findOrCreateAndAttach(
            $this->agence($request)->id,
            $validated['ville_depart_id'],
            $validated['ville_arrivee_id'],
        );

        $destination->load(['villeDepart', 'villeArrivee']);

        $destination->setAttribute('rattachee', true);

        return DestinationResource::make($destination)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Catalogue global des destinations actives + indicateur de rattachement à l'agence.
     *
     * @return Builder<Destination>
     */
    private function destinationsQuery(Request $request): Builder
    {
        $agenceId = $this->agence($request)->id;

        $query = Destination::query()
            ->actif()
            ->with(['villeDepart', 'villeArrivee'])
            ->withExists([
                'agences as rattachee' => fn (Builder $q) => $q->where('agences.id', $agenceId),
            ])
            ->orderByTrajet();

        if ($search = $request->get('search')) {
            $query->matchingLocalite($search);
        }

        return $query;
    }
}

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
use OpenApi\Attributes as OA;

class DestinationController extends AgenceApiController
{
    public function __construct(
        private readonly DestinationResolver $destinationResolver,
    ) {}

    #[OA\Get(
        path: '/agence/destinations',
        operationId: 'agenceListDestinations',
        summary: 'Lister toutes les destinations (sans pagination)',
        description: 'Retourne le catalogue global des destinations actives, y compris celles déjà rattachées à l\'agence. Chaque élément expose le booléen rattachee.',
        tags: ['Agence - Destinations'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste complète des destinations',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/DestinationResource')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        return DestinationResource::collection(
            $this->destinationsQuery($request)->get()
        );
    }

    #[OA\Get(
        path: '/agence/destinations/paginated',
        operationId: 'agenceListDestinationsPaginated',
        summary: 'Lister les destinations (paginé)',
        description: 'Retourne le catalogue global des destinations actives, paginé. Chaque élément expose le booléen rattachee.',
        tags: ['Agence - Destinations'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15, minimum: 1, maximum: 100)
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Filtre sur départ ou arrivée'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste paginée des destinations',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/DestinationResource')
                        ),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function paginated(Request $request): AnonymousResourceCollection
    {
        $perPage = min(100, max(1, $request->integer('per_page', 15)));

        return DestinationResource::collection(
            $this->destinationsQuery($request)
                ->paginate($perPage)
                ->withQueryString()
        );
    }

    #[OA\Post(
        path: '/agence/destinations',
        operationId: 'agenceCreateDestination',
        summary: 'Créer ou rattacher une destination',
        description: 'Find-or-create une destination (sans configuration forcée) puis l\'attache à l\'agence. Body : depart, arrivee uniquement.',
        tags: ['Agence - Destinations'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['depart', 'arrivee'],
                properties: [
                    new OA\Property(property: 'depart', type: 'string', example: 'Chine'),
                    new OA\Property(property: 'arrivee', type: 'string', example: 'Libreville'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Destination créée ou rattachée',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/DestinationResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation échouée'),
        ]
    )]
    public function store(StoreDestinationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $destination = $this->destinationResolver->findOrCreateAndAttach(
            $this->agence($request)->id,
            $validated['depart'],
            $validated['arrivee'],
        );

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
            ->withExists([
                'agences as rattachee' => fn (Builder $q) => $q->where('agences.id', $agenceId),
            ])
            ->orderBy('depart')
            ->orderBy('arrivee');

        if ($search = $request->get('search')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('depart', 'like', "%{$search}%")
                    ->orWhere('arrivee', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}

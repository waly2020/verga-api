<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Requests\Api\Client\StorePubliciteRequest;
use App\Http\Requests\Api\Client\UpdatePubliciteRequest;
use App\Http\Resources\Api\PubliciteResource;
use App\Models\Publicite;
use App\Services\PubliciteLifecycleService;
use App\Services\PublicitePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;

class PubliciteController extends ClientApiController
{
    public function __construct(
        private readonly PubliciteLifecycleService $lifecycle,
        private readonly PublicitePaymentService $payments,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->client($request)
            ->publicites()
            ->latest();

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        return PubliciteResource::collection(
            $query->paginate($request->integer('per_page', 15))->withQueryString()
        );
    }

    public function store(StorePubliciteRequest $request): JsonResponse
    {
        $publicite = $this->lifecycle->createForClient(
            $this->client($request),
            $request->validated(),
            $this->image($request),
        );

        return PubliciteResource::make($publicite)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, string $publicite): PubliciteResource
    {
        return PubliciteResource::make($this->findOwned($request, $publicite));
    }

    public function update(UpdatePubliciteRequest $request, string $publicite): PubliciteResource
    {
        $model = $this->findOwned($request, $publicite);

        return PubliciteResource::make(
            $this->lifecycle->update($model, $request->validated(), $this->image($request))
        );
    }

    public function resoumettre(Request $request, string $publicite): PubliciteResource
    {
        return PubliciteResource::make(
            $this->lifecycle->resoumettre($this->findOwned($request, $publicite))
        );
    }

    public function payer(Request $request, string $publicite): JsonResponse
    {
        $client = $this->client($request);
        $model = $this->findOwned($request, $publicite);

        $payload = $this->payments->initiate(
            $model,
            trim("{$client->prenom} {$client->nom}"),
            $client->telephone,
        );

        return response()->json(['data' => $payload], 201);
    }

    private function findOwned(Request $request, string $publicite): Publicite
    {
        return $this->client($request)
            ->publicites()
            ->findOrFail($publicite);
    }

    private function image(Request $request): ?UploadedFile
    {
        $file = $request->file('image');

        return $file instanceof UploadedFile ? $file : null;
    }
}

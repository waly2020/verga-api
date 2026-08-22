<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Agence;

use App\Http\Requests\Api\Agence\StorePubliciteRequest;
use App\Http\Requests\Api\Agence\UpdatePubliciteRequest;
use App\Http\Resources\Api\PubliciteResource;
use App\Models\Publicite;
use App\Services\PubliciteLifecycleService;
use App\Services\PublicitePaymentService;
use App\Services\PublicitePricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class PubliciteController extends AgenceApiController
{
    public function __construct(
        private readonly PubliciteLifecycleService $lifecycle,
        private readonly PublicitePaymentService $payments,
        private readonly PublicitePricingService $pricing,
    ) {}

    public function configuration(): JsonResponse
    {
        return response()->json([
            'data' => $this->configurationPayload(),
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->agence($request)
            ->publicites()
            ->with('offre:id,titre')
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
        $publicite = $this->lifecycle->createForAgence(
            $this->agence($request),
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
        $model = $this->findOwned($request, $publicite);
        $agence = $this->agence($request);

        $payload = $this->payments->initiate(
            $model,
            $agence->nom,
            $agence->telephone,
        );

        return response()->json(['data' => $payload], 201);
    }

    private function findOwned(Request $request, string $publicite): Publicite
    {
        return $this->agence($request)
            ->publicites()
            ->with('offre:id,titre')
            ->findOrFail($publicite);
    }

    private function image(Request $request): ?UploadedFile
    {
        $file = $request->file('image');

        return $file instanceof UploadedFile ? $file : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function configurationPayload(): ?array
    {
        try {
            $example = $this->pricing->calculate(now(), now()->addDays(6));
        } catch (ValidationException) {
            return null;
        }

        return [
            'prix_par_jour' => $example['prix_par_jour'],
            'type_frais' => $example['type_frais'],
            'valeur_frais' => $example['valeur_frais'],
            'exemple_7_jours' => $example,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Api\PubliciteResource;
use App\Models\Publicite;
use App\Services\PubliciteLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PubliciteCatalogController
{
    public function __construct(
        private readonly PubliciteLifecycleService $lifecycle,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->lifecycle->expireOverdue();

        $query = Publicite::query()
            ->visible()
            ->with(['offre:id,titre', 'agence:id,nom'])
            ->latest();

        return PubliciteResource::collection(
            $query->paginate($request->integer('per_page', 15))->withQueryString()
        );
    }
}

<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Client\ListClientDestinationsRequest;
use App\Http\Resources\Api\DestinationResource;
use App\Models\Destination;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DestinationController extends Controller
{
    public function index(ListClientDestinationsRequest $request): AnonymousResourceCollection
    {
        $query = Destination::query()
            ->actif()
            ->with(['villeDepart', 'villeArrivee'])
            ->orderByTrajet();

        if ($search = $request->validated('search')) {
            $query->matchingLocalite($search);
        }

        return DestinationResource::collection($query->get());
    }
}

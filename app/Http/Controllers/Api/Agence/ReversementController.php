<?php

namespace App\Http\Controllers\Api\Agence;

use App\Http\Resources\Api\Agence\ReversementResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReversementController extends AgenceApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->agence($request)
            ->reversements()
            ->latest();

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        if ($periode = $request->get('periode')) {
            $query->where('periode', $periode);
        }

        return ReversementResource::collection(
            $query->paginate($request->integer('per_page', 15))->withQueryString()
        );
    }
}

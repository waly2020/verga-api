<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Resources\Api\Client\OffreResource;
use App\Models\Offre;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OffreController extends ClientApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Offre::query()
            ->with('agence:id,nom,ville')
            ->active()
            ->where('capacite_disponible', '>', 0);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                    ->orWhere('origine', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        return OffreResource::collection(
            $query->latest()->paginate($request->integer('per_page', 15))->withQueryString()
        );
    }
}

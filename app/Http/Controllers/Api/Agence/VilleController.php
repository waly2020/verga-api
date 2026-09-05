<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Agence;

use App\Http\Resources\Api\VilleResource;
use App\Models\Ville;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VilleController extends AgenceApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Ville::query()->actif()->orderBy('pays')->orderBy('ville');

        if ($pays = $request->get('pays')) {
            $query->duPays((string) $pays);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('pays', 'like', "%{$search}%")
                    ->orWhere('ville', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return VilleResource::collection($query->get());
    }
}

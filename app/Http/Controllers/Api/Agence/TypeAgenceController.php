<?php

namespace App\Http\Controllers\Api\Agence;

use App\Http\Resources\Api\Agence\TypeAgenceResource;
use App\Models\TypeAgence;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TypeAgenceController extends AgenceApiController
{
    public function index(): AnonymousResourceCollection
    {
        return TypeAgenceResource::collection(
            TypeAgence::query()->orderBy('nom')->get()
        );
    }
}

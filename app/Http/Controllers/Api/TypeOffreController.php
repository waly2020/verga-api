<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TypeOffreResource;
use App\Models\TypeOffre;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TypeOffreController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return TypeOffreResource::collection(
            TypeOffre::query()->platform()->actif()->orderBy('nom')->get()
        );
    }
}

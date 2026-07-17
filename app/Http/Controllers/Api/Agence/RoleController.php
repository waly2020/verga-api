<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Agence;

use App\Http\Resources\Api\Agence\AgenceRoleResource;
use App\Models\AgenceRole;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoleController extends AgenceApiController
{
    public function index(): AnonymousResourceCollection
    {
        return AgenceRoleResource::collection(
            AgenceRole::query()
                ->where('actif', true)
                ->where('est_systeme', false)
                ->orderBy('nom')
                ->get()
        );
    }
}

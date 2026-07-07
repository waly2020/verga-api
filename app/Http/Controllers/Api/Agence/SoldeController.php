<?php

namespace App\Http\Controllers\Api\Agence;

use App\Services\Finance\AgenceSoldeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SoldeController extends AgenceApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'data' => AgenceSoldeService::resume($this->agence($request)->id),
        ]);
    }
}

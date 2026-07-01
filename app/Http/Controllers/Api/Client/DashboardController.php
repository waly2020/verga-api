<?php

namespace App\Http\Controllers\Api\Client;

use App\Services\Dashboard\ClientDashboardService;
use App\Support\PeriodeFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends ClientApiController
{
    public function __invoke(Request $request, ClientDashboardService $dashboard): JsonResponse
    {
        $validated = $request->validate([
            'periode' => ['sometimes', 'string', 'in:'.implode(',', PeriodeFilter::allowed())],
        ]);

        $periode = $validated['periode'] ?? 'mois';

        return response()->json([
            'data' => $dashboard->build($this->client($request), $periode),
        ]);
    }
}

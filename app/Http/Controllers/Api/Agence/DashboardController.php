<?php

namespace App\Http\Controllers\Api\Agence;

use App\Services\Dashboard\AgenceDashboardService;
use App\Support\PeriodeFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends AgenceApiController
{
    public function __invoke(Request $request, AgenceDashboardService $dashboard): JsonResponse
    {
        $validated = $request->validate([
            'periode' => ['sometimes', 'string', 'in:'.implode(',', PeriodeFilter::allowed())],
        ]);

        $periode = $validated['periode'] ?? 'mois';

        return response()->json([
            'data' => $dashboard->build($this->agence($request), $periode),
        ]);
    }
}

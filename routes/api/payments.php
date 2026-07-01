<?php

/*
| Webhook Bamboo Pay — notification de changement de statut
*/

use App\Services\PaymentSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('payments/bamboo-pay/callback', function (Request $request, PaymentSettlementService $settlement) {
    $paiement = $settlement->settleFromCallback($request->all());

    return response()->json([
        'received' => true,
        'processed' => $paiement !== null,
        'paiement_code' => $paiement?->code,
        'statut' => $paiement?->statut ?? $request->input('status'),
    ]);
})->name('api.payments.bamboo-pay.callback');

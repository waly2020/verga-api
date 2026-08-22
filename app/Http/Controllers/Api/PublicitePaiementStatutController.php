<?php

namespace App\Http\Controllers\Api;

use App\Models\PaiementPublicite;
use App\Services\BambooPayService;
use App\Services\PaymentSettlementService;
use App\Services\PublicitePaymentSettlementService;
use Illuminate\Http\JsonResponse;

class PublicitePaiementStatutController
{
    public function __construct(
        private readonly BambooPayService $bambooPay,
        private readonly PublicitePaymentSettlementService $settlement,
    ) {}

    public function show(string $code): JsonResponse
    {
        $paiement = PaiementPublicite::query()
            ->with('publicite')
            ->where('code', $code)
            ->firstOrFail();

        if ($paiement->statut === 'en_attente') {
            try {
                $response = $this->bambooPay->checkStatus($paiement->code);
                $status = $response['transaction']['status'] ?? $response['status'] ?? null;
                if (is_string($status)) {
                    $paiement = $this->settlement->settleFromBambooStatus(
                        $paiement,
                        $status,
                        PaymentSettlementService::messageFromCheckStatusResponse($response),
                    );
                }
            } catch (\Throwable) {
                // Le statut local reste consultable.
            }
        }

        $paiement->loadMissing('publicite');

        return response()->json([
            'data' => [
                'paiement_code' => $paiement->code,
                'statut' => $paiement->statut,
                'publicite_statut' => $paiement->publicite?->statut,
                'statut_paiement' => $paiement->publicite?->statut_paiement,
                'montant' => (int) $paiement->montant,
            ],
        ]);
    }
}

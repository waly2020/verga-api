<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PaiementPublicite;
use App\Services\BambooPayService;
use App\Services\PaymentSettlementService;
use App\Services\PublicitePaymentSettlementService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PublicitePaiementRetourController extends Controller
{
    public function show(
        string $paiement,
        BambooPayService $bambooPay,
        PublicitePaymentSettlementService $settlement,
    ): InertiaResponse {
        $model = PaiementPublicite::query()
            ->with('publicite')
            ->where('code', $paiement)
            ->firstOrFail();

        if ($model->statut === 'en_attente') {
            try {
                $response = $bambooPay->checkStatus($model->code);
                $status = $response['transaction']['status'] ?? $response['status'] ?? null;
                if (is_string($status)) {
                    $model = $settlement->settleFromBambooStatus(
                        $model,
                        $status,
                        PaymentSettlementService::messageFromCheckStatusResponse($response),
                    );
                }
            } catch (\Throwable) {
                // Page de retour accessible même si Bamboo est indisponible.
            }
        }

        $model->loadMissing('publicite');

        return Inertia::render('publicite-paiement/retour', [
            'paiement' => [
                'code' => $model->code,
                'statut' => $model->statut,
                'montant' => (int) $model->montant,
                'montant_sous_total' => (int) $model->montant_sous_total,
                'montant_frais' => (int) $model->montant_frais,
                'nombre_jours' => $model->nombre_jours,
                'bamboo_message' => $model->bamboo_message,
            ],
            'publicite' => $model->publicite ? [
                'id' => $model->publicite->id,
                'titre' => $model->publicite->titre,
                'statut' => $model->publicite->statut,
                'statut_paiement' => $model->publicite->statut_paiement,
                'date_debut' => $model->publicite->date_debut?->toDateString(),
                'date_fin' => $model->publicite->date_fin?->toDateString(),
            ] : null,
        ]);
    }
}

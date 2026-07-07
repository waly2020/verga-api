<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Services\CommandeCheckoutService;
use App\Services\PaiementRecapService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PaiementRetourController extends Controller
{
    public function show(
        string $paiement,
        CommandeCheckoutService $checkout,
        PaiementRecapService $recap,
    ): InertiaResponse {
        $this->syncPendingStatus($paiement, $checkout);

        return Inertia::render('paiement/retour', $recap->forPage(
            $recap->findByCode($paiement),
        ));
    }

    public function facture(string $paiement, PaiementRecapService $recap): Response
    {
        $data = $recap->forPdf($recap->findByCode($paiement));

        $filename = sprintf('facture-%s.pdf', $data['paiement']['code']);

        return Pdf::loadView('paiements.facture', $data)
            ->download($filename);
    }

    private function syncPendingStatus(string $code, CommandeCheckoutService $checkout): void
    {
        try {
            $model = Paiement::query()->where('code', $code)->first();

            if ($model && $model->statut === 'en_attente') {
                $checkout->verifyPaymentStatus($code);
            }
        } catch (\Throwable) {
            // La page de retour reste accessible même si Bamboo est indisponible.
        }
    }
}

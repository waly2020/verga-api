<?php

namespace App\Services;

use App\Models\PaiementPublicite;
use App\Models\Publicite;
use App\Support\PublicitePaiementReturnUrl;
use App\Support\ReferenceGenerator;
use Illuminate\Validation\ValidationException;

class PublicitePaymentService
{
    public function __construct(
        private readonly BambooPayService $bambooPay,
        private readonly PublicitePricingService $pricing,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function initiate(Publicite $publicite, string $payerName, string $phone): array
    {
        if (! $publicite->canBePaid()) {
            throw ValidationException::withMessages([
                'publicite' => ['Cette publicité n\'est pas payable. Elle doit être validée par un administrateur.'],
            ]);
        }

        if ($publicite->paiements()->where('statut', 'en_attente')->exists()) {
            throw ValidationException::withMessages([
                'publicite' => ['Un paiement est déjà en cours pour cette publicité.'],
            ]);
        }

        $pricing = $this->pricing->calculate($publicite->date_debut, $publicite->date_fin);

        $paiement = PaiementPublicite::create([
            'publicite_id' => $publicite->id,
            'code' => ReferenceGenerator::paiementPublicite(),
            'nombre_jours' => $pricing['nombre_jours'],
            'prix_par_jour' => $pricing['prix_par_jour'],
            'montant_sous_total' => $pricing['montant_sous_total'],
            'montant_frais' => $pricing['montant_frais'],
            'montant' => $pricing['montant_total'],
            'methode' => 'bamboo_redirect',
            'statut' => 'en_attente',
        ]);

        $publicite->update(['statut_paiement' => Publicite::PAIEMENT_EN_ATTENTE]);

        $bambooResponse = $this->bambooPay->redirectPayment([
            'payerName' => $payerName,
            'matricule' => $paiement->code,
            'raisonSociale' => $payerName,
            'billingId' => $paiement->code,
            'transactionAmount' => (string) $pricing['montant_total'],
            'phone' => $phone,
            'return_url' => PublicitePaiementReturnUrl::for($paiement),
            'update_status_url' => url('/api/v1/payments/bamboo-pay/publicites/callback'),
        ]);

        return [
            'publicite_id' => $publicite->id,
            'paiement_code' => $paiement->code,
            'statut' => $publicite->fresh()->statut,
            'statut_paiement' => $publicite->fresh()->statut_paiement,
            'nombre_jours' => $pricing['nombre_jours'],
            'prix_par_jour' => $pricing['prix_par_jour'],
            'montant_sous_total' => $pricing['montant_sous_total'],
            'montant_frais' => $pricing['montant_frais'],
            'montant_total' => $pricing['montant_total'],
            'retour_url' => PublicitePaiementReturnUrl::for($paiement),
            'redirect_url' => $bambooResponse['redirect_url'] ?? null,
            'verification_url' => url("/api/v1/publicites/paiements/{$paiement->code}/statut"),
        ];
    }
}

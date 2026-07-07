<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Offre;
use App\Models\Paiement;
use App\Support\PaiementReturnUrl;
use App\Support\QuantiteFormatter;
use App\Support\ReferenceGenerator;
use Illuminate\Validation\ValidationException;

class CommandePaymentService
{
    public function __construct(
        private readonly BambooPayService $bambooPay,
        private readonly OrderPricingService $pricing,
        private readonly OffreQuantityRules $quantityRules,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function initiate(Commande $commande, Offre $offre, float $quantiteAPayer): array
    {
        $quantiteRestante = $commande->quantiteRestante();

        if ($quantiteRestante <= 0) {
            throw ValidationException::withMessages([
                'quantite' => ['Cette commande est déjà entièrement payée.'],
            ]);
        }

        if ($quantiteAPayer > $quantiteRestante + 0.0001) {
            throw ValidationException::withMessages([
                'quantite' => ["La quantité à payer ne peut pas dépasser {$quantiteRestante}."],
            ]);
        }

        if ($commande->paiements()->where('statut', 'en_attente')->exists()) {
            throw ValidationException::withMessages([
                'commande' => ['Un paiement est déjà en cours pour cette commande.'],
            ]);
        }

        $this->validateQuantiteChunk($offre, $quantiteAPayer);

        $pricing = $this->pricing->calculate($offre, $quantiteAPayer);

        $paiement = Paiement::create([
            'commande_id' => $commande->id,
            'code' => ReferenceGenerator::paiement(),
            'quantite' => $quantiteAPayer,
            'montant_sous_total' => $pricing['montant_sous_total'],
            'montant_commission_client' => $pricing['montant_commission_client'],
            'montant' => $pricing['montant_total'],
            'methode' => 'bamboo_redirect',
            'statut' => 'en_attente',
        ]);

        $bambooResponse = $this->bambooPay->redirectPayment([
            'payerName' => trim("{$commande->prenom} {$commande->nom}"),
            'matricule' => $commande->code,
            'raisonSociale' => trim("{$commande->nom} {$commande->prenom}"),
            'billingId' => $paiement->code,
            'transactionAmount' => (string) $pricing['montant_total'],
            'phone' => $commande->telephone,
            'return_url' => PaiementReturnUrl::for($paiement),
        ]);

        return $this->paymentPayload(
            $commande,
            $paiement,
            $pricing,
            $bambooResponse['redirect_url'] ?? null,
            $offre,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function paymentPayload(
        Commande $commande,
        Paiement $paiement,
        array $pricing,
        ?string $redirectUrl,
        ?Offre $offre = null,
    ): array {
        $offre ??= $commande->load('offre.typeOffre')->offre;
        $offre?->loadMissing('typeOffre');
        $typeOffre = $offre?->typeOffre;

        return [
            'commande_id' => $commande->id,
            'code' => $commande->code,
            'commande_statut' => $commande->statut,
            ...QuantiteFormatter::withLabels([
                'quantite_reservee' => (float) $commande->quantite,
                'quantite_payee' => (float) $commande->quantite_payee,
                'quantite_a_payer' => (float) $paiement->quantite,
                'quantite_restante' => $commande->quantiteRestante(),
            ], $typeOffre),
            'montant_sous_total' => $pricing['montant_sous_total'],
            'montant_commission_client' => $pricing['montant_commission_client'],
            'montant_total' => $pricing['montant_total'],
            'paiement_code' => $paiement->code,
            'retour_url' => PaiementReturnUrl::for($paiement),
            'redirect_url' => $redirectUrl,
            'verification_url' => url("/api/v1/client/paiements/{$paiement->code}/statut"),
            'mode' => ((float) $commande->quantite_payee + (float) $paiement->quantite) < (float) $commande->quantite - 0.0001
                ? 'reservation'
                : 'complet',
        ];
    }

    private function validateQuantiteChunk(Offre $offre, float $quantite): void
    {
        $this->quantityRules->validate($offre, $quantite);
    }
}

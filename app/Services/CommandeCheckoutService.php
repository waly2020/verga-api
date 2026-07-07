<?php

namespace App\Services;

use App\Models\Colis;
use App\Models\ColisPhoto;
use App\Models\Commande;
use App\Models\Offre;
use App\Models\Paiement;
use App\Support\QuantiteFormatter;
use App\Support\ReferenceGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommandeCheckoutService
{
    public function __construct(
        private readonly BambooPayService $bambooPay,
        private readonly PaymentSettlementService $settlement,
        private readonly CommandePaymentService $payments,
        private readonly OffreQuantityRules $quantityRules,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $photos
     * @return array<string, mixed>
     */
    public function checkout(array $data, array $photos = [], ?string $clientId = null): array
    {
        return DB::transaction(function () use ($data, $photos, $clientId) {
            $offre = Offre::query()
                ->where('statut', 'active')
                ->lockForUpdate()
                ->findOrFail($data['offre_id']);

            $quantiteReservee = (float) ($data['quantite_reservee'] ?? $data['quantite']);
            $quantiteAPayer = (float) $data['quantite'];

            $this->validateReservation($offre, $quantiteReservee, $quantiteAPayer);

            $commande = Commande::create([
                'client_id' => $clientId,
                'offre_id' => $offre->id,
                'agence_id' => $offre->agence_id,
                'code' => ReferenceGenerator::commande(),
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'telephone' => $data['telephone'],
                'quantite' => $quantiteReservee,
                'quantite_payee' => 0,
                'montant_sous_total' => 0,
                'montant_commission_client' => 0,
                'montant_total' => 0,
                'capacite_bloquee' => false,
                'statut' => 'en_attente',
            ]);

            $colis = Colis::create([
                'commande_id' => $commande->id,
                'agence_id' => $offre->agence_id,
                'reference' => ReferenceGenerator::colis(),
                'description' => $data['description'] ?? null,
                'statut' => 'chez_client',
            ]);

            $this->storePhotos($colis, $photos);

            return $this->payments->initiate($commande->fresh(), $offre, $quantiteAPayer);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function payBalance(Commande $commande, float $quantiteAPayer): array
    {
        return DB::transaction(function () use ($commande, $quantiteAPayer) {
            if ($commande->statut !== 'réservée') {
                throw ValidationException::withMessages([
                    'commande' => ['Seules les commandes réservées peuvent recevoir un paiement de solde.'],
                ]);
            }

            $offre = Offre::query()
                ->lockForUpdate()
                ->findOrFail($commande->offre_id);

            return $this->payments->initiate($commande, $offre, $quantiteAPayer);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyPaymentStatus(string $paymentCode): array
    {
        $paiement = Paiement::query()
            ->with(['commande.offre.typeOffre'])
            ->where('code', $paymentCode)
            ->firstOrFail();

        if ($paiement->isFinalized()) {
            return $this->statusPayload($paiement);
        }

        $transactionId = $paiement->bamboo_reference ?: $paiement->code;

        if (! $transactionId) {
            return $this->statusPayload($paiement, pending: true);
        }

        $bambooStatus = $this->bambooPay->checkStatus($transactionId);
        $transactionStatus = (string) ($bambooStatus['transaction']['status'] ?? $bambooStatus['status'] ?? 'pending');
        $bambooMessage = PaymentSettlementService::messageFromCheckStatusResponse($bambooStatus);

        $paiement = $this->settlement->settleFromBambooStatus($paiement, $transactionStatus, $bambooMessage);

        return $this->statusPayload($paiement->fresh(['commande.offre.typeOffre']));
    }

    private function validateReservation(Offre $offre, float $quantiteReservee, float $quantiteAPayer): void
    {
        if ($quantiteAPayer > $quantiteReservee + 0.0001) {
            throw ValidationException::withMessages([
                'quantite' => ['La quantité payée ne peut pas dépasser la quantité réservée.'],
            ]);
        }

        $this->quantityRules->validate($offre, $quantiteReservee, $quantiteAPayer);

        if ((float) $offre->capacite_disponible < $quantiteReservee) {
            throw ValidationException::withMessages([
                'quantite_reservee' => ['Quantité indisponible pour cette offre.'],
            ]);
        }
    }

    /**
     * @param  array<int, UploadedFile>  $photos
     */
    private function storePhotos(Colis $colis, array $photos): void
    {
        foreach (array_values($photos) as $index => $photo) {
            $path = $photo->store("colis/{$colis->id}", 'public');

            ColisPhoto::create([
                'colis_id' => $colis->id,
                'chemin' => $path,
                'ordre' => $index,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function statusPayload(Paiement $paiement, bool $pending = false): array
    {
        $commande = $paiement->commande;
        $commande?->loadMissing('offre.typeOffre');
        $typeOffre = $commande?->offre?->typeOffre;

        return [
            'paiement_code' => $paiement->code,
            'statut' => $paiement->statut,
            'bamboo_reference' => $paiement->bamboo_reference,
            'bamboo_message' => $paiement->bamboo_message,
            ...QuantiteFormatter::withLabels([
                'quantite' => (float) $paiement->quantite,
                'quantite_reservee' => $commande ? (float) $commande->quantite : null,
                'quantite_payee' => $commande ? (float) $commande->quantite_payee : null,
                'quantite_restante' => $commande?->quantiteRestante(),
            ], $typeOffre),
            'montant_sous_total' => (float) $paiement->montant_sous_total,
            'montant_commission_client' => (float) $paiement->montant_commission_client,
            'montant_total' => (float) $paiement->montant,
            'commande_code' => $commande?->code,
            'commande_statut' => $commande?->statut,
            'commande_montant_sous_total' => $commande ? (float) $commande->montant_sous_total : null,
            'commande_montant_commission_client' => $commande ? (float) $commande->montant_commission_client : null,
            'commande_montant_total' => $commande ? (float) $commande->montant_total : null,
            'en_attente_bamboo' => $pending,
        ];
    }
}

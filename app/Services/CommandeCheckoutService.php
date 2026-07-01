<?php

namespace App\Services;

use App\Models\Colis;
use App\Models\ColisPhoto;
use App\Models\Commande;
use App\Models\Offre;
use App\Models\Paiement;
use App\Support\ReferenceGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommandeCheckoutService
{
    public function __construct(
        private readonly BambooPayService $bambooPay,
        private readonly PaymentSettlementService $settlement,
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

            $quantite = (float) $data['quantite'];
            $this->validateQuantite($offre, $quantite);

            $montantTotal = round((float) $offre->prix * $quantite, 2);

            $commande = Commande::create([
                'client_id' => $clientId,
                'offre_id' => $offre->id,
                'agence_id' => $offre->agence_id,
                'code' => ReferenceGenerator::commande(),
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'telephone' => $data['telephone'],
                'quantite' => $quantite,
                'montant_total' => $montantTotal,
                'statut' => 'en_attente',
            ]);

            $colis = Colis::create([
                'commande_id' => $commande->id,
                'agence_id' => $offre->agence_id,
                'reference' => ReferenceGenerator::colis(),
                'description' => $data['description'] ?? null,
                'statut' => 'déposé',
            ]);

            $this->storePhotos($colis, $photos);

            $paiement = Paiement::create([
                'commande_id' => $commande->id,
                'code' => ReferenceGenerator::paiement(),
                'montant' => $montantTotal,
                'methode' => 'bamboo_redirect',
                'statut' => 'en_attente',
            ]);

            $bambooResponse = $this->bambooPay->redirectPayment([
                'payerName' => trim("{$data['prenom']} {$data['nom']}"),
                'matricule' => $commande->code,
                'raisonSociale' => trim("{$data['nom']} {$data['prenom']}"),
                'billingId' => $paiement->code,
                'transactionAmount' => (string) $montantTotal,
                'phone' => $data['telephone'],
            ]);

            return [
                'commande_id' => $commande->id,
                'code' => $commande->code,
                'montant_total' => $montantTotal,
                'paiement_code' => $paiement->code,
                'redirect_url' => $bambooResponse['redirect_url'] ?? null,
                'verification_url' => url("/api/v1/client/paiements/{$paiement->code}/statut"),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyPaymentStatus(string $paymentCode): array
    {
        $paiement = Paiement::query()
            ->with(['commande.offre'])
            ->where('code', $paymentCode)
            ->firstOrFail();

        if ($paiement->isFinalized()) {
            return $this->statusPayload($paiement);
        }

        if (! $paiement->bamboo_reference) {
            return $this->statusPayload($paiement, pending: true);
        }

        $bambooStatus = $this->bambooPay->checkStatus($paiement->bamboo_reference);
        $transactionStatus = (string) ($bambooStatus['transaction']['status'] ?? $bambooStatus['status'] ?? 'pending');

        $paiement = $this->settlement->settleFromBambooStatus($paiement, $transactionStatus);

        return $this->statusPayload($paiement->fresh(['commande']));
    }

    private function validateQuantite(Offre $offre, float $quantite): void
    {
        if ($quantite <= 0) {
            throw ValidationException::withMessages([
                'quantite' => ['La quantité doit être supérieure à zéro.'],
            ]);
        }

        if ($offre->type === 'conteneur' && floor($quantite) !== $quantite) {
            throw ValidationException::withMessages([
                'quantite' => ['La quantité doit être un nombre entier pour une offre conteneur.'],
            ]);
        }

        if ((float) $offre->capacite_disponible < $quantite) {
            throw ValidationException::withMessages([
                'quantite' => ['Quantité indisponible pour cette offre.'],
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
        return [
            'paiement_code' => $paiement->code,
            'statut' => $paiement->statut,
            'bamboo_reference' => $paiement->bamboo_reference,
            'commande_code' => $paiement->commande?->code,
            'commande_statut' => $paiement->commande?->statut,
            'en_attente_bamboo' => $pending,
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Offre;
use App\Models\Paiement;
use Illuminate\Support\Facades\DB;

class PaymentSettlementService
{
    /**
     * Applique un statut Bamboo final de manière idempotente.
     */
    public function settleFromBambooStatus(Paiement $paiement, string $bambooStatus): Paiement
    {
        return DB::transaction(function () use ($paiement, $bambooStatus) {
            /** @var Paiement $locked */
            $locked = Paiement::query()
                ->whereKey($paiement->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->isFinalized()) {
                return $locked;
            }

            $normalized = strtolower($bambooStatus);

            if ($normalized === 'completed') {
                $this->markCompleted($locked);
            } elseif ($normalized === 'failed') {
                $this->markFailed($locked);
            }

            return $locked->fresh(['commande']);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function settleFromCallback(array $payload): ?Paiement
    {
        $billingId = $payload['billingId'] ?? null;
        $reference = $payload['reference'] ?? null;
        $status = $payload['status'] ?? null;

        if (! $status) {
            return null;
        }

        $paiement = Paiement::query()
            ->when($billingId, fn ($q) => $q->where('code', $billingId))
            ->when(! $billingId && $reference, fn ($q) => $q->where('bamboo_reference', $reference))
            ->first();

        if (! $paiement) {
            return null;
        }

        if ($reference && ! $paiement->bamboo_reference) {
            $paiement->update(['bamboo_reference' => $reference]);
        }

        return $this->settleFromBambooStatus($paiement, (string) $status);
    }

    private function markCompleted(Paiement $paiement): void
    {
        $paiement->update(['statut' => 'validé']);

        /** @var Commande $commande */
        $commande = Commande::query()
            ->whereKey($paiement->commande_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($commande->statut === 'confirmée') {
            return;
        }

        $commande->update(['statut' => 'confirmée']);

        /** @var Offre $offre */
        $offre = Offre::query()
            ->whereKey($commande->offre_id)
            ->lockForUpdate()
            ->firstOrFail();

        $nouvelleCapacite = max(0, (float) $offre->capacite_disponible - (float) $commande->quantite);
        $offre->update(['capacite_disponible' => $nouvelleCapacite]);

        if ($nouvelleCapacite <= 0) {
            $offre->update(['statut' => 'inactive']);
        }
    }

    private function markFailed(Paiement $paiement): void
    {
        $paiement->update(['statut' => 'échec']);

        Commande::query()
            ->whereKey($paiement->commande_id)
            ->where('statut', 'en_attente')
            ->update(['statut' => 'annulée']);
    }
}

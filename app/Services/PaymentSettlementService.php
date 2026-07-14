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
    public function settleFromBambooStatus(
        Paiement $paiement,
        string $bambooStatus,
        ?string $bambooMessage = null,
    ): Paiement {
        return DB::transaction(function () use ($paiement, $bambooStatus, $bambooMessage) {
            /** @var Paiement $locked */
            $locked = Paiement::query()
                ->whereKey($paiement->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->isFinalized()) {
                return $locked;
            }

            $normalized = strtolower($bambooStatus);
            $message = self::normalizeMessage($bambooMessage);

            if ($normalized === 'completed') {
                $this->markCompleted($locked, $message);
            } elseif ($normalized === 'failed') {
                $this->markFailed($locked, $message);
            } elseif ($message !== null) {
                $locked->update(['bamboo_message' => $message]);
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

        $this->syncBambooMetadata($paiement, [
            'bamboo_reference' => is_string($reference) ? $reference : null,
            'operateur' => self::operateurFromPayload($payload),
        ]);

        return $this->settleFromBambooStatus(
            $paiement->fresh() ?? $paiement,
            (string) $status,
            self::messageFromCallbackPayload($payload),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function messageFromCallbackPayload(array $payload): ?string
    {
        $observation = $payload['observation'] ?? null;

        return self::normalizeMessage(is_string($observation) ? $observation : null);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function messageFromCheckStatusResponse(array $payload): ?string
    {
        $transaction = $payload['transaction'] ?? null;
        $message = is_array($transaction)
            ? ($transaction['message'] ?? null)
            : null;

        if (! is_string($message) || $message === '') {
            $message = $payload['message'] ?? null;
        }

        return self::normalizeMessage(is_string($message) ? $message : null);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function operateurFromPayload(array $payload): ?string
    {
        $candidates = [
            $payload['operateur'] ?? null,
            $payload['operator'] ?? null,
            $payload['paymentMethod'] ?? null,
            $payload['payment_method'] ?? null,
        ];

        $transaction = $payload['transaction'] ?? null;

        if (is_array($transaction)) {
            $candidates[] = $transaction['operateur'] ?? null;
            $candidates[] = $transaction['operator'] ?? null;
            $candidates[] = $transaction['paymentMethod'] ?? null;
            $candidates[] = $transaction['payment_method'] ?? null;
        }

        foreach ($candidates as $value) {
            $normalized = self::normalizeMessage(is_string($value) ? $value : null);

            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * @param  array{bamboo_reference?: ?string, operateur?: ?string}  $metadata
     */
    public function syncBambooMetadata(Paiement $paiement, array $metadata): void
    {
        $updates = [];

        $reference = $metadata['bamboo_reference'] ?? null;
        if (is_string($reference) && $reference !== '' && ! $paiement->bamboo_reference) {
            $updates['bamboo_reference'] = $reference;
        }

        $operateur = $metadata['operateur'] ?? null;
        if (is_string($operateur) && $operateur !== '' && ! $paiement->operateur) {
            $updates['operateur'] = $operateur;
        }

        if ($updates !== []) {
            $paiement->update($updates);
        }
    }

    private static function normalizeMessage(?string $message): ?string
    {
        if ($message === null) {
            return null;
        }

        $message = trim($message);

        return $message !== '' ? $message : null;
    }

    private function markCompleted(Paiement $paiement, ?string $bambooMessage = null): void
    {
        $this->updatePaiementStatut($paiement, 'validé', $bambooMessage);

        /** @var Commande $commande */
        $commande = Commande::query()
            ->whereKey($paiement->commande_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($commande->statut === 'confirmée') {
            return;
        }

        $commande->update([
            'quantite_payee' => (float) $commande->quantite_payee + (float) $paiement->quantite,
            'montant_sous_total' => (float) $commande->montant_sous_total + (float) $paiement->montant_sous_total,
            'montant_commission_client' => (float) $commande->montant_commission_client + (float) $paiement->montant_commission_client,
            'montant_total' => (float) $commande->montant_total + (float) $paiement->montant,
        ]);

        if (! $commande->capacite_bloquee) {
            $this->blockOfferCapacity($commande);
            $commande->update(['capacite_bloquee' => true]);
        }

        $commande->refresh();

        $commande->update([
            'statut' => $commande->isFullyPaid() ? 'confirmée' : 'réservée',
        ]);
    }

    private function markFailed(Paiement $paiement, ?string $bambooMessage = null): void
    {
        $this->updatePaiementStatut($paiement, 'échec', $bambooMessage);

        $commande = Commande::query()
            ->whereKey($paiement->commande_id)
            ->lockForUpdate()
            ->first();

        if (! $commande || $commande->statut !== 'en_attente') {
            return;
        }

        $hasValidatedPayment = $commande->paiements()
            ->where('statut', 'validé')
            ->exists();

        if (! $hasValidatedPayment) {
            $commande->update(['statut' => 'annulée']);
        }
    }

    private function updatePaiementStatut(Paiement $paiement, string $statut, ?string $bambooMessage = null): void
    {
        $attributes = ['statut' => $statut];

        if ($bambooMessage !== null) {
            $attributes['bamboo_message'] = $bambooMessage;
        }

        $paiement->update($attributes);
    }

    private function blockOfferCapacity(Commande $commande): void
    {
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
}

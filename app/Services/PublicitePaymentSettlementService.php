<?php

namespace App\Services;

use App\Models\PaiementPublicite;
use App\Models\Publicite;
use Illuminate\Support\Facades\DB;

class PublicitePaymentSettlementService
{
    public function __construct(
        private readonly PubliciteMailService $publiciteMail,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function settleFromCallback(array $payload): ?PaiementPublicite
    {
        $billingId = $payload['billingId'] ?? null;
        $reference = $payload['reference'] ?? null;
        $status = $payload['status'] ?? null;

        if (! $status) {
            return null;
        }

        $paiement = PaiementPublicite::query()
            ->when($billingId, fn ($q) => $q->where('code', $billingId))
            ->when(! $billingId && $reference, fn ($q) => $q->where('bamboo_reference', $reference))
            ->first();

        if (! $paiement) {
            return null;
        }

        $this->syncBambooMetadata($paiement, [
            'bamboo_reference' => is_string($reference) ? $reference : null,
            'operateur' => PaymentSettlementService::operateurFromPayload($payload),
        ]);

        return $this->settleFromBambooStatus(
            $paiement->fresh() ?? $paiement,
            (string) $status,
            PaymentSettlementService::messageFromCallbackPayload($payload),
        );
    }

    public function settleFromBambooStatus(
        PaiementPublicite $paiement,
        string $bambooStatus,
        ?string $bambooMessage = null,
    ): PaiementPublicite {
        return DB::transaction(function () use ($paiement, $bambooStatus, $bambooMessage) {
            /** @var PaiementPublicite $locked */
            $locked = PaiementPublicite::query()
                ->whereKey($paiement->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->isFinalized()) {
                return $locked;
            }

            $normalized = strtolower($bambooStatus);

            if ($normalized === 'completed') {
                $this->markCompleted($locked, $bambooMessage);
            } elseif ($normalized === 'failed') {
                $this->markFailed($locked, $bambooMessage);
            } elseif ($bambooMessage !== null) {
                $locked->update(['bamboo_message' => $bambooMessage]);
            }

            return $locked->fresh(['publicite']);
        });
    }

    /**
     * @param  array{bamboo_reference?: ?string, operateur?: ?string}  $metadata
     */
    public function syncBambooMetadata(PaiementPublicite $paiement, array $metadata): void
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

    private function markCompleted(PaiementPublicite $paiement, ?string $bambooMessage = null): void
    {
        $updates = ['statut' => 'validé'];
        if ($bambooMessage !== null) {
            $updates['bamboo_message'] = $bambooMessage;
        }
        $paiement->update($updates);

        $publicite = Publicite::query()
            ->whereKey($paiement->publicite_id)
            ->lockForUpdate()
            ->firstOrFail();

        $statut = $publicite->date_fin->lt(now()->startOfDay())
            ? Publicite::STATUT_EXPIREE
            : Publicite::STATUT_PUBLIEE;

        $publicite->update([
            'statut' => $statut,
            'statut_paiement' => Publicite::PAIEMENT_PAYE,
        ]);

        $publicite = $publicite->fresh(['agence', 'client.user', 'offre']);

        $this->publiciteMail->notifyPubliee($publicite, $paiement->fresh());
    }

    private function markFailed(PaiementPublicite $paiement, ?string $bambooMessage = null): void
    {
        $updates = ['statut' => 'échec'];
        if ($bambooMessage !== null) {
            $updates['bamboo_message'] = $bambooMessage;
        }
        $paiement->update($updates);

        Publicite::query()
            ->whereKey($paiement->publicite_id)
            ->where('statut', Publicite::STATUT_VALIDEE)
            ->update(['statut_paiement' => Publicite::PAIEMENT_ECHEC]);

        $publicite = Publicite::query()
            ->whereKey($paiement->publicite_id)
            ->with(['agence', 'client.user', 'offre'])
            ->first();

        if ($publicite) {
            $this->publiciteMail->notifyPaiementEchec($publicite, $paiement->fresh());
        }
    }
}

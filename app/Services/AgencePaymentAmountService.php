<?php

namespace App\Services;

use App\Models\ConfigurationCommission;
use App\Models\Offre;

class AgencePaymentAmountService
{
    /**
     * @return array{montant_commission_agence: float, montant_agence: float}
     */
    public function calculate(float $montantSousTotal, ?Offre $offre = null): array
    {
        $montantSousTotal = max(0, round($montantSousTotal, 2));
        $commission = $this->resolveCommission($montantSousTotal, $offre);
        $commission = min($montantSousTotal, max(0, round($commission, 2)));

        return [
            'montant_commission_agence' => $commission,
            'montant_agence' => round($montantSousTotal - $commission, 2),
        ];
    }

    private function resolveCommission(float $montantSousTotal, ?Offre $offre): float
    {
        $offre?->loadMissing('destination');
        $destination = $offre?->destination;

        if ($destination?->hasConfigurationAppliquee()) {
            return round(
                $montantSousTotal * ((float) $destination->commission_pourcentage / 100),
                2,
            );
        }

        $configuration = ConfigurationCommission::pour('agence');

        return $configuration
            ? $configuration->calculerMontant($montantSousTotal)
            : 0.0;
    }
}

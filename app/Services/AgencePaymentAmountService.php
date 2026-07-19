<?php

namespace App\Services;

use App\Models\ConfigurationCommission;

class AgencePaymentAmountService
{
    /**
     * @return array{montant_commission_agence: float, montant_agence: float}
     */
    public function calculate(float $montantSousTotal): array
    {
        $montantSousTotal = max(0, round($montantSousTotal, 2));
        $configuration = ConfigurationCommission::pour('agence');
        $commission = $configuration
            ? $configuration->calculerMontant($montantSousTotal)
            : 0.0;
        $commission = min($montantSousTotal, max(0, round($commission, 2)));

        return [
            'montant_commission_agence' => $commission,
            'montant_agence' => round($montantSousTotal - $commission, 2),
        ];
    }
}

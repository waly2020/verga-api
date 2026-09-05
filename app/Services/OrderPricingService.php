<?php

namespace App\Services;

use App\Models\ConfigurationCommission;
use App\Models\Offre;

class OrderPricingService
{
    public function __construct(
        private readonly OffrePaliersService $paliers,
        private readonly CommissionPaliersService $commissionPaliers,
    ) {}

    /**
     * @return array{
     *     prix_unitaire: float,
     *     montant_sous_total: float,
     *     montant_commission_client: float,
     *     montant_total: float
     * }
     */
    public function calculate(Offre $offre, float $quantite, ?float $quantitePourPalier = null): array
    {
        $prixUnitaire = $this->paliers->unitPrice($offre, $quantitePourPalier ?? $quantite);
        $montantSousTotal = round($prixUnitaire * $quantite, 2);
        $montantCommissionClient = $this->clientCommissionAmount($montantSousTotal);

        return [
            'prix_unitaire' => $prixUnitaire,
            'montant_sous_total' => $montantSousTotal,
            'montant_commission_client' => $montantCommissionClient,
            'montant_total' => round($montantSousTotal + $montantCommissionClient, 2),
        ];
    }

    /**
     * Estimation complète pour affichage front (prix + commission active).
     *
     * @return array{
     *     offre_id: string,
     *     quantite: float,
     *     prix_unitaire: float,
     *     montant_sous_total: float,
     *     montant_commission_client: float,
     *     montant_total: float,
     *     capacite_disponible: float|null,
     *     stock_suffisant: bool,
     *     commission: array{type: string, valeur: float, libelle: string|null}|null
     * }
     */
    public function estimate(Offre $offre, float $quantite): array
    {
        $pricing = $this->calculate($offre, $quantite);
        $config = ConfigurationCommission::pour('client');
        $stockLimite = $offre->hasStockLimite();
        $resolved = $config
            ? $this->commissionPaliers->resolve($config, $pricing['montant_sous_total'])
            : null;

        return [
            'offre_id' => $offre->id,
            'quantite' => $quantite,
            'prix_unitaire' => $pricing['prix_unitaire'],
            'montant_sous_total' => $pricing['montant_sous_total'],
            'montant_commission_client' => $pricing['montant_commission_client'],
            'montant_total' => $pricing['montant_total'],
            'capacite_disponible' => $stockLimite ? (float) $offre->capacite_disponible : null,
            'stock_suffisant' => ! $stockLimite || (float) $offre->capacite_disponible >= $quantite,
            'commission' => $config ? [
                'type' => $config->type,
                'valeur' => $config->estGrille()
                    ? $resolved['montant']
                    : (float) $config->valeur,
                'libelle' => $config->estGrille()
                    ? ($resolved['libelle'] ?? null)
                    : $config->libelle,
            ] : null,
        ];
    }

    private function clientCommissionAmount(float $montantSousTotal): float
    {
        $config = ConfigurationCommission::pour('client');

        if (! $config) {
            return 0.0;
        }

        return $this->commissionPaliers->resolve($config, $montantSousTotal)['montant'];
    }
}

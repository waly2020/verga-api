<?php

namespace App\Services\Dashboard;

use App\Models\Paiement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ValidatedPaiementStats
{
    /**
     * @return array{total: float, sous_total: float, commissions_client: float}
     */
    public static function aggregate(Carbon $debut, Carbon $fin, ?callable $scope = null): array
    {
        $query = self::baseQuery($debut, $fin);

        if ($scope !== null) {
            $scope($query);
        }

        $row = $query
            ->selectRaw('COALESCE(SUM(paiements.montant), 0) as total')
            ->selectRaw('COALESCE(SUM(paiements.montant_sous_total), 0) as sous_total')
            ->selectRaw('COALESCE(SUM(paiements.montant_commission_client), 0) as commissions_client')
            ->first();

        return [
            'total' => (float) $row->total,
            'sous_total' => (float) $row->sous_total,
            'commissions_client' => (float) $row->commissions_client,
        ];
    }

    /**
     * @return array<int, array{nom: string, total: float, sous_total: float, commissions_client: float}>
     */
    public static function byAgence(Carbon $debut, Carbon $fin, int $limit = 10): array
    {
        return Paiement::query()
            ->join('commandes', 'paiements.commande_id', '=', 'commandes.id')
            ->join('agences', 'commandes.agence_id', '=', 'agences.id')
            ->where('paiements.statut', 'validé')
            ->whereBetween('paiements.created_at', [$debut, $fin])
            ->selectRaw('agences.nom')
            ->selectRaw('COALESCE(SUM(paiements.montant), 0) as total')
            ->selectRaw('COALESCE(SUM(paiements.montant_sous_total), 0) as sous_total')
            ->selectRaw('COALESCE(SUM(paiements.montant_commission_client), 0) as commissions_client')
            ->groupBy('agences.id', 'agences.nom')
            ->orderByDesc('sous_total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'nom' => $row->nom,
                'total' => (float) $row->total,
                'sous_total' => (float) $row->sous_total,
                'commissions_client' => (float) $row->commissions_client,
            ])
            ->values()
            ->all();
    }

    private static function baseQuery(Carbon $debut, Carbon $fin): Builder
    {
        return Paiement::query()
            ->where('paiements.statut', 'validé')
            ->whereBetween('paiements.created_at', [$debut, $fin]);
    }
}

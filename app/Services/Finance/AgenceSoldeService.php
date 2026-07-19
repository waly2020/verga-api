<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Reversement;
use Illuminate\Support\Facades\DB;

class AgenceSoldeService
{
    public static function soldeCourant(string $agenceId): float
    {
        return (float) (DB::table('vue_agences_soldes')
            ->where('agence_id', $agenceId)
            ->value('montant_solde') ?? 0);
    }

    public static function montantReversementsEnAttente(string $agenceId, ?string $excludeReversementId = null): float
    {
        return (float) Reversement::query()
            ->where('agence_id', $agenceId)
            ->where('statut', 'en_attente')
            ->when($excludeReversementId, fn ($query) => $query->where('id', '!=', $excludeReversementId))
            ->sum('montant');
    }

    public static function soldeDisponible(string $agenceId, ?string $excludeReversementId = null): float
    {
        $enAttente = self::montantReversementsEnAttente($agenceId, $excludeReversementId);

        return max(0, self::soldeCourant($agenceId) - $enAttente);
    }

    public static function peutReverser(string $agenceId, float $montant, ?string $excludeReversementId = null): bool
    {
        return $montant > 0 && $montant <= self::soldeDisponible($agenceId, $excludeReversementId) + 0.001;
    }

    /**
     * @return array{
     *     montant_paiements_valides: float,
     *     montant_reversements: float,
     *     montant_solde: float,
     *     montant_reversements_en_attente: float,
     *     montant_disponible: float,
     * }
     */
    public static function resume(string $agenceId): array
    {
        $paiements = DB::table('vue_agences_paiements_valides')
            ->where('agence_id', $agenceId)
            ->first();

        $reversements = DB::table('vue_agences_reversements')
            ->where('agence_id', $agenceId)
            ->first();

        $solde = DB::table('vue_agences_soldes')
            ->where('agence_id', $agenceId)
            ->first();

        $enAttente = self::montantReversementsEnAttente($agenceId);
        $montantSolde = (float) ($solde->montant_solde ?? 0);

        return [
            'montant_paiements_valides' => (float) ($paiements->montant_agence ?? 0),
            'montant_reversements' => (float) ($reversements->montant ?? 0),
            'montant_solde' => $montantSolde,
            'montant_reversements_en_attente' => $enAttente,
            'montant_disponible' => max(0, $montantSolde - $enAttente),
        ];
    }
}

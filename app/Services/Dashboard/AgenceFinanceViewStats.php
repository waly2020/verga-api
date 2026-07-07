<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\DB;

class AgenceFinanceViewStats
{
    /**
     * @return array<int, array{nom: string, total: float}>
     */
    public static function topSoldes(int $limit = 10): array
    {
        return DB::table('vue_agences_soldes as soldes')
            ->join('agences', 'agences.id', '=', 'soldes.agence_id')
            ->where('soldes.montant_solde', '>', 0)
            ->orderByDesc('soldes.montant_solde')
            ->limit($limit)
            ->get(['agences.nom', 'soldes.montant_solde'])
            ->map(fn ($row) => [
                'nom' => $row->nom,
                'total' => (float) $row->montant_solde,
            ])
            ->all();
    }

    /**
     * @return array<int, array{nom: string, total: float}>
     */
    public static function topReversements(int $limit = 10): array
    {
        return DB::table('vue_agences_reversements as reversements')
            ->join('agences', 'agences.id', '=', 'reversements.agence_id')
            ->where('reversements.montant', '>', 0)
            ->orderByDesc('reversements.montant')
            ->limit($limit)
            ->get(['agences.nom', 'reversements.montant'])
            ->map(fn ($row) => [
                'nom' => $row->nom,
                'total' => (float) $row->montant,
            ])
            ->all();
    }

    public static function totalSoldes(): float
    {
        return (float) DB::table('vue_agences_soldes')->sum('montant_solde');
    }
}

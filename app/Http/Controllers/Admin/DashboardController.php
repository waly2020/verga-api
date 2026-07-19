<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agence;
use App\Models\Commande;
use App\Models\Reclamation;
use App\Models\Reversement;
use App\Services\Dashboard\AgenceFinanceViewStats;
use App\Services\Dashboard\ValidatedPaiementStats;
use App\Support\PeriodeFilter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $periode = $request->get('periode', 'mois');
        [$debut, $fin] = PeriodeFilter::range($periode);

        $paiements = ValidatedPaiementStats::aggregate($debut, $fin);

        $stats = [
            'agences' => Agence::where('statut', 'actif')->count(),
            'commandes_total' => Commande::whereBetween('created_at', [$debut, $fin])->count(),
            'solde_paiements' => $paiements['total'],
            'solde_sous_total' => $paiements['sous_total'],
            'solde_commissions_client' => $paiements['commissions_client'],
            'solde_commissions_agence' => $paiements['commissions_agence'],
            'reclamations_ouvertes' => Reclamation::where('statut', 'ouverte')->count(),
            'reversements_attente' => (float) Reversement::where('statut', 'en_attente')->sum('montant'),
            'soldes_agences_total' => AgenceFinanceViewStats::totalSoldes(),
        ];

        $commandesParStatut = Commande::whereBetween('created_at', [$debut, $fin])
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->toArray();

        $parAgence = ValidatedPaiementStats::byAgence($debut, $fin);

        $paiementsParAgence = array_map(
            fn (array $row) => ['nom' => $row['nom'], 'total' => $row['montant_agence']],
            $parAgence,
        );

        $soldesParAgence = AgenceFinanceViewStats::topSoldes();
        $reversementsParAgence = AgenceFinanceViewStats::topReversements();

        return Inertia::render('admin/dashboard', [
            'stats' => $stats,
            'commandes_par_statut' => $commandesParStatut,
            'paiements_par_agence' => $paiementsParAgence,
            'soldes_par_agence' => $soldesParAgence,
            'reversements_par_agence' => $reversementsParAgence,
            'periode' => $periode,
        ]);
    }
}

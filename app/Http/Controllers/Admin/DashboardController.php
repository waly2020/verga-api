<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agence;
use App\Models\Commande;
use App\Models\Commission;
use App\Models\Paiement;
use App\Models\Reclamation;
use App\Models\Reversement;
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

        $soldeCommissions = (float) Commission::whereBetween('created_at', [$debut, $fin])
            ->sum('montant');

        $soldePaiements = (float) Paiement::where('statut', 'validé')
            ->whereBetween('created_at', [$debut, $fin])
            ->sum('montant');

        $stats = [
            'agences' => Agence::where('statut', 'actif')->count(),
            'commandes_total' => Commande::whereBetween('created_at', [$debut, $fin])->count(),
            'solde_commissions' => $soldeCommissions,
            'solde_paiements' => $soldePaiements,
            'reclamations_ouvertes' => Reclamation::where('statut', 'ouverte')->count(),
            'reversements_attente' => (float) Reversement::where('statut', 'en_attente')->sum('montant'),
        ];

        $commandesParStatut = Commande::whereBetween('created_at', [$debut, $fin])
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->toArray();

        $paiementsParAgence = Paiement::join('commandes', 'paiements.commande_id', '=', 'commandes.id')
            ->join('agences', 'commandes.agence_id', '=', 'agences.id')
            ->where('paiements.statut', 'valide')
            ->whereBetween('paiements.created_at', [$debut, $fin])
            ->selectRaw('agences.nom, SUM(paiements.montant) as total')
            ->groupBy('agences.id', 'agences.nom')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['nom' => $r->nom, 'total' => (float) $r->total])
            ->values()
            ->toArray();

        $commissionsParAgence = Commission::join('commandes', 'commissions.commande_id', '=', 'commandes.id')
            ->join('agences', 'commandes.agence_id', '=', 'agences.id')
            ->whereBetween('commissions.created_at', [$debut, $fin])
            ->selectRaw('agences.nom, SUM(commissions.montant) as total')
            ->groupBy('agences.id', 'agences.nom')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['nom' => $r->nom, 'total' => (float) $r->total])
            ->values()
            ->toArray();

        return Inertia::render('admin/dashboard', [
            'stats' => $stats,
            'commandes_par_statut' => $commandesParStatut,
            'paiements_par_agence' => $paiementsParAgence,
            'commissions_par_agence' => $commissionsParAgence,
            'periode' => $periode,
        ]);
    }
}

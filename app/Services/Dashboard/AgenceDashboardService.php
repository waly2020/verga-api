<?php

namespace App\Services\Dashboard;

use App\Models\Agence;
use App\Models\Commission;
use App\Support\PeriodeFilter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class AgenceDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function build(Agence $agence, string $periode = 'mois'): array
    {
        [$debut, $fin] = PeriodeFilter::range($periode);

        $offresBase = $agence->offres();
        $commandesQuery = $agence->commandes()->whereBetween('created_at', [$debut, $fin]);
        $colisQuery = $agence->colis()->whereBetween('created_at', [$debut, $fin]);
        $reclamationsQuery = $agence->reclamations()->whereBetween('created_at', [$debut, $fin]);

        $paiements = ValidatedPaiementStats::aggregate(
            $debut,
            $fin,
            fn (Builder $query) => $query
                ->join('commandes', 'paiements.commande_id', '=', 'commandes.id')
                ->where('commandes.agence_id', $agence->id),
        );

        $totalCommissionsAgence = (float) Commission::query()
            ->join('commandes', 'commissions.commande_id', '=', 'commandes.id')
            ->where('commandes.agence_id', $agence->id)
            ->whereBetween('commissions.created_at', [$debut, $fin])
            ->sum('commissions.montant');

        return [
            'periode' => $periode,
            'debut' => $debut->toIso8601String(),
            'fin' => $fin->toIso8601String(),
            'profil' => [
                'nom' => $agence->nom,
                'ville' => $agence->ville,
                'statut' => $agence->statut,
            ],
            'stats' => [
                'nb_offres' => (clone $offresBase)->count(),
                'nb_offres_actives' => (clone $offresBase)->where('statut', 'active')->count(),
                'capacite_disponible_totale' => (float) (clone $offresBase)->where('statut', 'active')->sum('capacite_disponible'),
                'nb_commandes' => (clone $commandesQuery)->count(),
                'nb_commandes_en_attente' => (clone $commandesQuery)->where('statut', 'en_attente')->count(),
                'nb_commandes_confirmees' => (clone $commandesQuery)->where('statut', 'confirmée')->count(),
                'total_paiements' => $paiements['total'],
                'total_sous_total' => $paiements['sous_total'],
                'total_commissions_client' => $paiements['commissions_client'],
                'total_commissions_agence' => $totalCommissionsAgence,
                'total_commissions' => $totalCommissionsAgence,
                'revenu_net_estime' => $paiements['sous_total'] - $totalCommissionsAgence,
                'reversements_en_attente' => (float) $agence->reversements()->where('statut', 'en_attente')->sum('montant'),
                'nb_colis' => (clone $colisQuery)->count(),
                'nb_colis_en_transit' => (clone $colisQuery)->where('statut', 'en_transit')->count(),
                'nb_reclamations_ouvertes' => (clone $reclamationsQuery)->whereIn('statut', ['ouverte', 'en_cours'])->count(),
            ],
            'commandes_par_statut' => $this->groupByStatut(clone $commandesQuery, 'statut'),
            'colis_par_statut' => $this->groupByStatut(clone $colisQuery, 'statut'),
            'top_offres' => $this->topOffres($agence, $debut, $fin),
            'dernieres_commandes' => $this->recentCommandes($agence),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function groupByStatut(Builder|Relation $query, string $column): array
    {
        return $query
            ->selectRaw("{$column}, COUNT(*) as total")
            ->groupBy($column)
            ->pluck('total', $column)
            ->map(fn ($total) => (int) $total)
            ->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topOffres(Agence $agence, Carbon $debut, Carbon $fin): array
    {
        return $agence->offres()
            ->withCount([
                'commandes as nb_commandes' => fn (Builder $query) => $query
                    ->whereBetween('created_at', [$debut, $fin]),
            ])
            ->orderByDesc('nb_commandes')
            ->limit(5)
            ->get(['id', 'titre', 'type', 'prix', 'statut', 'capacite_totale', 'capacite_disponible'])
            ->map(fn ($offre) => [
                'id' => $offre->id,
                'titre' => $offre->titre,
                'type' => $offre->type,
                'prix' => (float) $offre->prix,
                'statut' => $offre->statut,
                'capacite_totale' => (float) $offre->capacite_totale,
                'capacite_disponible' => (float) $offre->capacite_disponible,
                'nb_commandes' => (int) $offre->nb_commandes,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentCommandes(Agence $agence): array
    {
        return $agence->commandes()
            ->with(['client:id,nom,prenom'])
            ->latest()
            ->limit(5)
            ->get([
                'id',
                'code',
                'client_id',
                'nom',
                'prenom',
                'montant_sous_total',
                'montant_commission_client',
                'montant_total',
                'statut',
                'created_at',
            ])
            ->map(fn ($commande) => [
                'id' => $commande->id,
                'code' => $commande->code,
                'montant_sous_total' => $commande->montant_sous_total,
                'montant_commission_client' => $commande->montant_commission_client,
                'montant_total' => $commande->montant_total,
                'statut' => $commande->statut,
                'created_at' => $commande->created_at?->toIso8601String(),
                'client' => $commande->client ? [
                    'id' => $commande->client->id,
                    'nom' => $commande->client->nom,
                    'prenom' => $commande->client->prenom,
                ] : ($commande->nom && $commande->prenom ? [
                    'nom' => $commande->nom,
                    'prenom' => $commande->prenom,
                    'invite' => true,
                ] : null),
            ])
            ->values()
            ->all();
    }
}
